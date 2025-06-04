<?php

namespace App\Controller\Api;

use App\DTO\comment\CommentDTO;
use App\DTO\comment\CreateCommentDTO;
use App\DTO\comment\UpdateCommentDTO;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comment')]
class CommentApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ApiResponseService $apiResponseService, Request $request, CommentRepository $commentRepository): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $offset = ($page - 1) * $limit;

        $total = $commentRepository->count([]);
        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

        $commentsDTO = array_map(fn($comment) => new CommentDTO($comment), $comments);

        return $apiResponseService->success(
            $commentsDTO,
            "",
            [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, CommentRepository $commentRepository, ApiResponseService $apiResponseService): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            return $apiResponseService->error("Comment not found", 404);
        }

        return $apiResponseService->success(new CommentDTO($comment));
    }

    #[Route('/new', methods: ['POST'])]
    public function new(
        ApiResponseService     $apiResponseService,
        Request                $request,
        EntityManagerInterface $entityManager,
        ArticleRepository      $articleRepository,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        $createCommentDTO = $serializer->deserialize(
            $request->getContent(),
            CreateCommentDTO::class,
            'json'
        );

        $errors = $validator->validate($createCommentDTO);
        if (count($errors) > 0) {
            return $apiResponseService->error(
                "Validation failed",
                errors: ['errors' => $errors]
            );
        }

        $article = $articleRepository->find($createCommentDTO->getArticleId());
        if (!$article) {
            return $apiResponseService->error("Article not found", Response::HTTP_NOT_FOUND);
        }

        $comment = new Comment();
        $comment->setContent($createCommentDTO->getContent());
        $comment->setArticle($article);
        //$comment->setAuthor($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($comment);
        $entityManager->flush();

        return $apiResponseService->success(
            new CommentDTO($comment),
            "Comment created successfully"
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function edit(
        int                    $id,
        ApiResponseService     $apiResponseService,
        Request                $request,
        EntityManagerInterface $entityManager,
        CommentRepository      $commentRepository,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        ArticleRepository      $articleRepository
    ): JsonResponse
    {
        $updateCommentDTO = $serializer->deserialize(
            $request->getContent(),
            UpdateCommentDTO::class,
            'json'
        );

        $errors = $validator->validate($updateCommentDTO);
        if (count($errors) > 0) {
            return $apiResponseService->error(
                "Validation failed",
                errors: ['errors' => $errors]
            );
        }


        $comment = $commentRepository->find($id);
        if (!$comment) {
            return $apiResponseService->error("Comment not found", Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est l'auteur du commentaire
        if ($comment->getAuthor() !== $this->getUser()) {
            return $apiResponseService->error("You are not allowed to edit this comment", Response::HTTP_FORBIDDEN);
        }
        $article = $articleRepository->find($updateCommentDTO->getArticleId());
        if ($article !== null) {
            $comment->setArticle($article);
        }

        if ($updateCommentDTO->getContent() !== null) {
            $comment->setContent($updateCommentDTO->getContent());
        }

        $comment->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($comment);
        $entityManager->flush();

        return $apiResponseService->success(
            new CommentDTO($comment),
            "Comment updated successfully"
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int                    $id,
        ApiResponseService     $apiResponseService,
        CommentRepository      $commentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $comment = $commentRepository->find($id);
        if (!$comment) {
            return $apiResponseService->error("Comment not found", Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est l'auteur du commentaire
        if ($comment->getAuthor() !== $this->getUser()) {
            return $apiResponseService->error("You are not allowed to delete this comment", Response::HTTP_FORBIDDEN);
        }
        $entityManager->remove($comment);
        $entityManager->flush();

        return $apiResponseService->success(
            null,
            "Comment deleted successfully"
        );
    }
}
