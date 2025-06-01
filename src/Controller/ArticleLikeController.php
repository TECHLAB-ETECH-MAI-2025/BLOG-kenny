<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Repository\ArticleLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
#[Route('/article/like')]
class ArticleLikeController extends AbstractController
{
    #[Route('/{id}', name: 'app_article_like_toggle', methods: ['POST'])]
    public function toggle(Request $request, Article $article, EntityManagerInterface $entityManager, ArticleLikeRepository $likeRepository): JsonResponse
    {
        if (!$this->isCsrfTokenValid('like' . $article->getId(), $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], Response::HTTP_BAD_REQUEST);
        }

        $this->denyAccessUnlessGranted('ROLE_USER');
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
            return new JsonResponse([
                'liked' => false,
                'count' => count($article->getArticleLikes())
            ]);
        }

        // Si non, on ajoute le like
        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setAuthor($user);
        $like->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($like);
        $entityManager->flush();

        return new JsonResponse([
            'liked' => true,
            'count' => count($article->getArticleLikes())
        ]);
    }
}
