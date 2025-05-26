<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentForm;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/comment')]
class CommentController extends AbstractController
{
    #[Route('/api/comments', name: 'app_comment_api', methods: ['POST'])]
    public function apiIndex(Request $request, CommentRepository $commentRepository): JsonResponse
    {
        $draw = $request->request->get('draw');
        $start = $request->request->get('start', 0);
        $length = $request->request->get('length', 10);
        $search = $request->request->get('search')['value'] ?? '';
        $orders = $request->request->get('order', []);

        $qb = $commentRepository->createQueryBuilder('c')
            ->leftJoin('c.article', 'a')
            ->leftJoin('c.author', 'u')
            ->select('c', 'a', 'u');

        // Recherche
        if (!empty($search)) {
            $qb->andWhere('c.content LIKE :search OR a.title LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri
        if (!empty($orders)) {
            $column = $request->request->get('columns')[$orders[0]['column']];
            $dir = $orders[0]['dir'];

            switch ($column['data']) {
                case 'id':
                    $qb->orderBy('c.id', $dir);
                    break;
                case 'content':
                    $qb->orderBy('c.content', $dir);
                    break;
                case 'article':
                    $qb->orderBy('a.title', $dir);
                    break;
                case 'author':
                    $qb->orderBy('u.email', $dir);
                    break;
                case 'createdAt':
                    $qb->orderBy('c.createdAt', $dir);
                    break;
                default:
                    $qb->orderBy('c.createdAt', 'DESC');
            }
        }

        // Compter le total
        $totalRecords = count($qb->getQuery()->getResult());

        // Pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        $comments = $qb->getQuery()->getResult();

        $data = [];
        foreach ($comments as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'article' => [
                    'title' => $comment->getArticle() ? $comment->getArticle()->getTitle() : 'N/A'
                ],
                'author' => [
                    'email' => $comment->getAuthor() ? $comment->getAuthor()->getEmail() : 'N/A'
                ],
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'actions' => $this->renderView('comment/_actions.html.twig', ['comment' => $comment])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    #[Route('/', name: 'app_comment_index', methods: ['GET'])]
    public function index(Request $request,CommentRepository $commentRepository,PaginatorInterface $paginator): Response
    {
        $query = $commentRepository->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page',1),
            10
        );
        return $this->render('comment/index.html.twig', [
            'comments' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setAuthor($this->getUser());
        
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire créé avec succès');
            return $this->redirectToRoute('app_comment_index');
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUpdatedBy($this->getUser());
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Commentaire supprimé avec succès');
        }

        return $this->redirectToRoute('app_comment_index');
    }
}