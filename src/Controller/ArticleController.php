<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleForm;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/article')]
class ArticleController extends AbstractController
{
    #[Route(name: 'app_article_index', methods: ['GET', 'POST'])]
    public function index(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $query = $articleRepository->createQueryBuilder('a')
            ->where('a.isDeleted = false')
            ->leftJoin('a.comments', 'c')
            ->addSelect('c')
            ->leftJoin('a.categories', 'cat')
            ->addSelect('cat')
            ->leftJoin('a.author', 'auth')
            ->addSelect('auth')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
             6
        );

        // Création d'un nouveau commentaire si nécessaire
        if ($articleId = $request->request->get('article_id')) {
            $article = $articleRepository->find($articleId);
            if ($article) {
                $comment = new Comment();
                $comment->setArticle($article);
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setAuthor($this->getUser());
                $comment->setContent($request->request->get('content'));
                
                $entityManager->persist($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Votre commentaire a été ajouté avec succès !');
                return $this->redirectToRoute('app_article_index', ['_fragment' => 'article-' . $articleId]);
            }
        }

        // Création d'un article
        $article = new Article();
        $articleForm = $this->createForm(ArticleForm::class, $article, [
            'action' => $this->generateUrl('app_article_new'),
            'method' => 'POST',
        ]);

        return $this->render('article/index.html.twig', [
            'articles' => $pagination,
            'article_form' => $articleForm,
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $article->setAuthor($this->getUser());
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $article->setIsDeleted(true);
            $entityManager->persist($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit',name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour des champs updated
            $article->setUpdatedBy($this->getUser());
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/api/articles', name: 'app_article_api', methods: ['POST'])]
    public function apiIndex(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $draw = $request->request->get('draw');
        $start = $request->request->get('start', 0);
        $length = $request->request->get('length', 10);
        $search = $request->request->get('search')['value'] ?? '';
        $orders = $request->request->get('order', []);

        $qb = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->leftJoin('a.author', 'u')
            ->select('a', 'c', 'u');

        // Recherche
        if (!empty($search)) {
            $qb->andWhere('a.title LIKE :search OR a.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri
        if (!empty($orders)) {
            $column = $request->request->get('columns')[$orders[0]['column']];
            $dir = $orders[0]['dir'];

            switch ($column['data']) {
                case 'id':
                    $qb->orderBy('a.id', $dir);
                    break;
                case 'title':
                    $qb->orderBy('a.title', $dir);
                    break;
                case 'categories':
                    $qb->orderBy('c.name', $dir);
                    break;
                case 'commentsCount':
                    $qb->leftJoin('a.comments', 'co')
                       ->orderBy('COUNT(co.id)', $dir)
                       ->groupBy('a.id');
                    break;
                case 'createdAt':
                    $qb->orderBy('a.createdAt', $dir);
                    break;
                default:
                    $qb->orderBy('a.createdAt', 'DESC');
            }
        }

        // Compter le total
        $totalRecords = count($qb->getQuery()->getResult());

        // Pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        $articles = $qb->getQuery()->getResult();

        $data = [];
        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'categories' => $article->getCategories()->map(function($cat) {
                    return $cat->getName();
                })->toArray(),
                'commentsCount' => count($article->getComments()),
                'createdAt' => $article->getCreatedAt()?->format('Y-m-d H:i:s'),
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article
                ])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

}