<?php

namespace App\DTO\category;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCategoryDTO
{
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le nom doit faire au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[Assert\Length(max: 1000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    private ?string $description;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
