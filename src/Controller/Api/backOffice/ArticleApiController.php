<?php

namespace App\Controller\Api\backOffice;

use App\DTO\article\ArticleDTO;
use App\DTO\article\CreateArticleDTO;
use App\DTO\article\UpdateArticleDTO;
use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Repository\ArticleLikeRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/article')]
class ArticleApiController extends AbstractController
{

    #[Route('', methods: ['GET'])]
    public function index(ApiResponseService $apiResponseService, Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $all = $request->query->get('all', false);
        $offset = ($page - 1) * $limit;

        $total = $articleRepository->count([]);
        if ($all) {
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


    #[Route('/new', methods: ['POST'])]
    public function  new(
        ApiResponseService     $apiResponseService,
        Request                $request,
        EntityManagerInterface $entityManager,
        CategoryRepository     $categoryRepository,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        $createArticleDTO = $serializer->deserialize(
            $request->getContent(),
            CreateArticleDTO::class,
            'json'
        );
        $errors = $validator->validate($createArticleDTO);
        if (count($errors) > 0) {
            return $apiResponseService->error(
                "Validation failed",
                errors: ['errors' => $this->json([$errors])]
            );
        }
        $article = new Article();
        $article->setTitle($createArticleDTO->getTitle());
        $article->setContent($createArticleDTO->getContent());
        $article->setCreatedAt(new \DateTimeImmutable());
        $article->setUpdatedAt(new \DateTimeImmutable());
        //$article->setUpdatedBy($this->getUser());
        $article->setAuthor($this->getUser());

        foreach ($createArticleDTO->getCategories() as $categoryId) {
            $category = $categoryRepository->find($categoryId);
            if ($category) {
                $article->addCategory($category);
            }
        }

        $entityManager->persist($article);
        $entityManager->flush();

        return $apiResponseService->success(
            new ArticleDTO($article),
            "Article created successfully",
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function edit(
        int                    $id,
        ApiResponseService     $apiResponseService,
        Request                $request,
        EntityManagerInterface $entityManager,
        CategoryRepository     $categoryRepository,
        ArticleRepository      $articleRepository,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        $updateArticleDTO = $serializer->deserialize(
            $request->getContent(),
            UpdateArticleDTO::class,
            'json'
        );
        $errors = $validator->validate($updateArticleDTO);
        if (count($errors) > 0) {
            return $apiResponseService->error(
                "Validation failed",
                errors: ['errors' => $this->json([$errors])]
            );
        }
        $article = $articleRepository->find($id);
        if (!$article) {
            return $apiResponseService->error("Article not found", Response::HTTP_NOT_FOUND);
        }

        // Mettre à jour les propriétés de l'entité seulement si elles sont présentes dans le DTO
        if ($updateArticleDTO->getTitle() !== null) {
            $article->setTitle($updateArticleDTO->getTitle());
        }
        if ($updateArticleDTO->getContent() !== null) {
            $article->setContent($updateArticleDTO->getContent());
        }

        // Mise à jour de la date et de l'utilisateur (optionnel)
        $article->setUpdatedAt(new \DateTimeImmutable());
        $article->setUpdatedBy($this->getUser());

        if ($updateArticleDTO->getCategories() !== null) {
            $article->getCategories()->clear();
            foreach ($updateArticleDTO->getCategories() as $categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $article->addCategory($category);
                }
            }
        }


        $entityManager->persist($article);
        $entityManager->flush();

        return $apiResponseService->success(
            new ArticleDTO($article),
            "Article updated successfully",
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, ApiResponseService $apiResponseService, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $article = $articleRepository->find($id);
        if (!$article) {
            return $apiResponseService->error("Article not found", Response::HTTP_NOT_FOUND);
        }
        $article->setIsDeleted(true);
        $entityManager->flush();
        return $apiResponseService->success(
            null,
            "Article deleted successfully"
        );
    }

    #[Route('/{id}/like', methods: ['POST'])]
    public function toggleLike(ApiResponseService $apiResponseService,Article $article, EntityManagerInterface $entityManager, ArticleLikeRepository $likeRepository): JsonResponse
    {
        $user = $this->getUser();
        // On vérifie si l'utilisateur a déjà liké
        $like = $likeRepository->findOneBy([
            'article' => $article,
            'author' => $user
        ]);

        if ($like) {
            // Si oui, on retire le like
            $entityManager->remove($like);
            $entityManager->flush();
            return $apiResponseService->success(new ArticleDTO($article),"like retirée");

        }

        // Si non, on ajoute le like
        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setAuthor($user);
        $like->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($like);
        $entityManager->flush();

        return $apiResponseService->success(new ArticleDTO($article),"article like");

    }


}