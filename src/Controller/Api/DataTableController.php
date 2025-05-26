<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

#[Route('/api')]
class DataTableController extends AbstractController
{
    #[Route('/articles', name: 'api_articles_datatable', methods: ['POST'])]
    public function articles(Request $request, ArticleRepository $repository): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? '';
        $filters = $request->request->all('filters') ?? [];
        $order = $request->request->all('order')[0] ?? [];
        
        $qb = $repository->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->leftJoin('a.comments', 'com');
            
        // Recherche
        if (!empty($search)) {
            $qb->andWhere('a.title LIKE :search OR c.name LIKE :search OR a.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Tri
        $sortColumn = $order['column'] ?? 4; // Par défaut, tri par date
        $sortDir = $order['dir'] ?? 'desc';
        
        switch ($sortColumn) {
            case 0: // ID
                $qb->orderBy('a.id', $sortDir);
                break;
            case 1: // Titre
                $qb->orderBy('a.title', $sortDir);
                break;
            case 2: // Catégories
                $qb->orderBy('c.name', $sortDir);
                break;
            case 3: // Nombre de commentaires
                $qb->orderBy('SIZE(a.comments)', $sortDir);
                break;
            case 4: // Date
                $qb->orderBy('a.createdAt', $sortDir);
                break;
            default:
                $qb->orderBy('a.createdAt', 'DESC');
        }

        // Compte total
        $totalQb = clone $qb;
        $total = count($totalQb->getQuery()->getResult());
        
        // Pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);
           
        $paginator = new Paginator($qb);
        $data = [];
        
        foreach ($paginator as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'excerpt' => mb_substr(strip_tags($article->getContent()), 0, 100) . '...',
                'categories' => $article->getCategories()->map(fn($c) => ['name' => $c->getName()])->toArray(),
                'commentsCount' => $article->getComments()->count(),
                'createdAt' => $article->getCreatedAt()->format('Y-m-d H:i:s'),
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article
                ])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $paginator->count(),
            'data' => $data
        ]);
    }

    #[Route('/categories', name: 'api_categories_datatable', methods: ['POST'])]
    public function categories(Request $request, CategoryRepository $repository): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? '';
        $filters = $request->request->all('filters') ?? [];
        $order = $request->request->all('order')[0] ?? [];

        $qb = $repository->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a');

        // Recherche
        if (!empty($search)) {
            $qb->andWhere('c.name LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri
        $sortColumn = $order['column'] ?? 1; // Par défaut, tri par nom
        $sortDir = $order['dir'] ?? 'asc';

        switch ($sortColumn) {
            case 0: // ID
                $qb->orderBy('c.id', $sortDir);
                break;
            case 1: // Nom
                $qb->orderBy('c.name', $sortDir);
                break;
            case 2: // Description
                $qb->orderBy('c.description', $sortDir);
                break;
            case 3: // Nombre d'articles
                $qb->orderBy('SIZE(c.articles)', $sortDir);
                break;
            case 4: // Mise à jour
                $qb->orderBy('c.updatedAt', $sortDir);
                break;
            default:
                $qb->orderBy('c.name', 'ASC');
        }

        // Compte total
        $totalQb = clone $qb;
        $total = count($totalQb->getQuery()->getResult());

        // Pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        $paginator = new Paginator($qb);
        $data = [];

        foreach ($paginator as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription() ?? '',
                'articlesCount' => $category->getArticles()->count(),
                'updatedAt' => $category->getUpdatedAt() ? $category->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                'actions' => $this->renderView('category/_actions.html.twig', [
                    'category' => $category
                ])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $paginator->count(),
            'data' => $data
        ]);
    }

    #[Route('/comments', name: 'api_comments_datatable', methods: ['POST'])]
    public function comments(Request $request, CommentRepository $repository): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? '';
        $filters = $request->request->all('filters') ?? [];
        $order = $request->request->all('order')[0] ?? [];

        $qb = $repository->createQueryBuilder('c')
            ->leftJoin('c.article', 'a')
            ->leftJoin('c.author', 'u');

        // Recherche
        if (!empty($search)) {
            $qb->andWhere('c.content LIKE :search OR a.title LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri
        $sortColumn = $order['column'] ?? 4; // Par défaut, tri par date
        $sortDir = $order['dir'] ?? 'desc';

        switch ($sortColumn) {
            case 0: // ID
                $qb->orderBy('c.id', $sortDir);
                break;
            case 1: // Contenu
                $qb->orderBy('c.content', $sortDir);
                break;
            case 2: // Article
                $qb->orderBy('a.title', $sortDir);
                break;
            case 3: // Auteur
                $qb->orderBy('u.email', $sortDir);
                break;
            case 4: // Date
                $qb->orderBy('c.createdAt', $sortDir);
                break;
            default:
                $qb->orderBy('c.createdAt', 'DESC');
        }

        // Compte total
        $totalQb = clone $qb;
        $total = count($totalQb->getQuery()->getResult());

        // Pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        $paginator = new Paginator($qb);
        $data = [];

        foreach ($paginator as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'article' => [
                    'id' => $comment->getArticle()->getId(),
                    'title' => $comment->getArticle()->getTitle()
                ],
                'author' => $comment->getAuthor() ? [
                    'email' => $comment->getAuthor()->getEmail()
                ] : null,
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'actions' => $this->renderView('comment/_actions.html.twig', [
                    'comment' => $comment
                ])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $paginator->count(),
            'data' => $data
        ]);
    }
}
