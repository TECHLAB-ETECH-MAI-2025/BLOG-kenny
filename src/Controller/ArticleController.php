<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleForm;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            $entityManager->remove($article);
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
}