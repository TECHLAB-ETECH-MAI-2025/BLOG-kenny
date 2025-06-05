<?php

namespace App\DTO\article;


use Symfony\Component\Validator\Constraints as Assert;
class UpdateArticleDTO
{


    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $title;

    #[Assert\NotBlank]
    public string $content;

    /** @var int[] */
    #[Assert\All([
        new Assert\Type(type: 'integer')
    ])]
    public array $categories = [];


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

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }






}