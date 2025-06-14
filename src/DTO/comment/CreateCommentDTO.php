<?php

namespace App\DTO\comment;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCommentDTO
{
    #[Assert\NotBlank(message: 'Le contenu est obligatoire')]
    #[Assert\Length(min: 0, max: 1000, minMessage: 'Le contenu doit faire au moins {{ limit }} caractères', maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères')]
    private string $content;

    #[Assert\NotBlank(message: "L'article est obligatoire")]
    #[Assert\Positive(message: "L'ID de l'article doit être un nombre positif")]
    private int $articleId;

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function setArticleId(int $articleId): self
    {
        $this->articleId = $articleId;
        return $this;
    }
}
