<?php

namespace App\Controller\Api\frontOffice;

use App\DTO\article\ArticleDTO;
use App\Repository\ArticleLikeRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Service\ApiResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/blog')]
class BlogApiController extends AbstractController
{
    private ArticleRepository $articleRepository;
    private CommentRepository $commentRepository;
    private CategoryRepository $categoryRepository;
    private ArticleLikeRepository $articleLikeRepository;

    public function __construct(
        ArticleRepository $articleRepository,
        CommentRepository $commentRepository,
        CategoryRepository $categoryRepository,
        ArticleLikeRepository $articleLikeRepository
    ) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->categoryRepository = $categoryRepository;
        $this->articleLikeRepository = $articleLikeRepository;
    }

    #[Route('/articles', name: 'api_blog_articles', methods: ['GET'])]
    public function index(ApiResponseService $apiResponseService, Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $all = $request->query->get('all', false);
        $offset = ($page - 1) * $limit;

        $total = $articleRepository->count([]);
        if ($all){
            $articles = $articleRepository->findBy([], ['createdAt' => 'DESC']);
        } else {
            $articles = $articleRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        }

        $articlesDTO = array_map(fn($article) => new ArticleDTO($article), $articles);

        return $apiResponseService->success(
            $articlesDTO,
            "",
            [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, ArticleRepository $articleRepository, ApiResponseService $apiResponseService): JsonResponse
    {
        $article = $articleRepository->findByIdWithRelations($id);

        if (!$article) {
            return $apiResponseService->error("Article not found", Response::HTTP_NOT_FOUND);
        }

        return $apiResponseService->success(new ArticleDTO($article));
    }





}