<?php

namespace App\DTO\article;

use App\Entity\Article;

class ArticleDTO
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $content = null;
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;
    private array $categories = [];
    private array $comments = [];

    private array $likes = [];
    private ?int $authorId = null;
    private ?int $updatedById = null;

    public function __construct(?Article $article = null)
    {
        if ($article) {
            $this->setId($article->getId());
            $this->setTitle($article->getTitle());
            $this->setContent($article->getContent());
            $this->setCreatedAt($article->getCreatedAt());
            $this->setUpdatedAt($article->getUpdatedAt());
            $this->setAuthorId($article->getAuthor()?->getId());
            $this->setUpdatedById($article->getUpdatedBy()?->getId());

            $categories = [];
            foreach ($article->getCategories() as $category) {
                $categories[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                ];
            }
            $this->setCategories($categories);

            $comments = [];
            foreach ($article->getComments() as $comment) {
                $comments[] = [
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'author' => $comment->getAuthor()?->getEmail(),
                    'createdAt' => $comment->getCreatedAt()->format('c'),
                ];
            }
            $this->setComments($comments);

            $reactions = [];
            foreach ($article->getArticleLikes() as $like) {
                $reactions[] = [
                    'id' => $like->getId(),
                ];
            }
            $this->setLikes($reactions);
        }
    }



        public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function setComments(array $comments): void
    {
        $this->comments = $comments;
    }

    public function getLikes(): array
    {
        return $this->likes;
    }
    public function setLikes(array $likes): void
    {
        $this->likes = $likes;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function setAuthorId(?int $authorId): void
    {
        $this->authorId = $authorId;
    }

    public function getUpdatedById(): ?int
    {
        return $this->updatedById;
    }

    public function setUpdatedById(?int $updatedById): void
    {
        $this->updatedById = $updatedById;
    }





}