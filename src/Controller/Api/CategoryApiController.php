<?php

namespace App\Controller\Api;

use App\DTO\category\CategoryDTO;
use App\DTO\category\CreateCategoryDTO;
use App\DTO\category\UpdateCategoryDTO;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/category')]
class CategoryApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ApiResponseService $apiResponseService, Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $all = $request->query->get('all', false);
        $offset = ($page - 1) * $limit;

        $total = $categoryRepository->count([]);
        if ($all) {
            $categories = $categoryRepository->findBy([], ['createdAt' => 'DESC']);
        } else {
            $categories = $categoryRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        }
        $categoriesDTO = array_map(fn($category) => new CategoryDTO($category), $categories);

        return $apiResponseService->success(
            $categoriesDTO,
            "",
            [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, CategoryRepository $categoryRepository, ApiResponseService $apiResponseService): JsonResponse
    {
        $category = $categoryRepository->find($id);

        if (!$category) {
            return $apiResponseService->error("Category not found", Response::HTTP_NOT_FOUND);
        }

        return $apiResponseService->success(new CategoryDTO($category));
    }

    #[Route('/new', methods: ['POST'])]
    public function new(
        ApiResponseService $apiResponseService,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $createCategoryDTO = $serializer->deserialize(
            $request->getContent(),
            CreateCategoryDTO::class,
            'json'
        );
        
        $errors = $validator->validate($createCategoryDTO);
        if (count($errors) > 0) {
            return $apiResponseService->error(
                "Validation failed",
                errors: ['errors' => $errors]
            );
        }

        $category = new Category();
        $category->setName($createCategoryDTO->getName());
        $category->setDescription($createCategoryDTO->getDescription());
        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($category);
        $entityManager->flush();

        return $apiResponseService->success(
            new CategoryDTO($category),
            "Category created successfully"
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function edit(
        int $id,
        ApiResponseService $apiResponseService,
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        $updateCategoryDTO = $serializer->deserialize(
            $request->getContent(),
            UpdateCategoryDTO::class,
            'json'
        );

        $errors = $validator->validate($updateCategoryDTO);
        if (count($errors) > 0) {
            return $apiResponseService->error(
                "Validation failed",
                errors: ['errors' => $errors]
            );
        }

        $category = $categoryRepository->find($id);
        if (!$category) {
            return $apiResponseService->error("Category not found", Response::HTTP_NOT_FOUND);
        }

        if ($updateCategoryDTO->getName() !== null) {
            $category->setName($updateCategoryDTO->getName());
        }

        if ($updateCategoryDTO->getDescription() !== null) {
            $category->setDescription($updateCategoryDTO->getDescription());
        }

        $category->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($category);
        $entityManager->flush();

        return $apiResponseService->success(
            new CategoryDTO($category),
            "Category updated successfully"
        );
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        ApiResponseService $apiResponseService,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $category = $categoryRepository->find($id);
        if (!$category) {
            return $apiResponseService->error("Category not found", Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return $apiResponseService->success(
            null,
            "Category deleted successfully"
        );
    }
}
