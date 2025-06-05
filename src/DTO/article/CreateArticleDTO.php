<?php

namespace App\DTO\article;

use Symfony\Component\Validator\Constraints as Assert;

class CreateArticleDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $title;

    #[Assert\NotBlank]
    private string $content;

    /** @var int[] */
    #[Assert\All([
        new Assert\Type(type: 'integer')
    ])]
    private array $categoryIds = [];


    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    public function setCategoryIds(array $categoryIds): void
    {
        $this->categoryIds = $categoryIds;
    }


}
