<?php

namespace App\DTO\article;


use Symfony\Component\Validator\Constraints as Assert;
class UpdateArticleDTO
{

    #[Assert\NotNull]
    public int $id;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $title;

    #[Assert\NotBlank]
    public string $content;

    /** @var int[] */
    #[Assert\All([
        new Assert\Type(type: 'integer')
    ])]
    public array $categoryIds = [];


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

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