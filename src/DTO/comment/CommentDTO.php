<?php

namespace App\DTO\comment;

use App\Entity\Comment;

class CommentDTO
{
    private int $id;
    private string $content;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private array $author;
    private array $article;

    public function __construct(Comment $comment)
    {
        $this->id = $comment->getId();
        $this->content = $comment->getContent();
        $this->createdAt = $comment->getCreatedAt();
        $this->updatedAt = $comment->getUpdatedAt();
        $this->author = [
            'id' => $comment->getAuthor()->getId(),
            'email' => $comment->getAuthor()->getEmail(),
            'username' => $comment->getAuthor()->getEmail()
        ];
        $this->article = [
            'id' => $comment->getArticle()->getId(),
            'title' => $comment->getArticle()->getTitle()
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getAuthor(): array
    {
        return $this->author;
    }

    public function getArticle(): array
    {
        return $this->article;
    }
}
