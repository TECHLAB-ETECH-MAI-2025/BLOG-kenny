<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryForm;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/category')]
final class CategoryController extends AbstractController
{
    #[Route('/api/categories', name: 'app_category_api', methods: ['POST'])]
    public function apiIndex(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        $draw = $request->request->get('draw');
        $start = $request->request->get('start', 0);
        $length = $request->request->get('length', 10);
        $search = $request->request->get('search')['value'] ?? '';
        $orders = $request->request->get('order', []);

        $qb = $categoryRepository->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a')
            ->select('c');

        // Recherche
        if (!empty($search)) {
            $qb->andWhere('c.name LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Tri
        if (!empty($orders)) {
            $column = $request->request->get('columns')[$orders[0]['column']];
            $dir = $orders[0]['dir'];

            switch ($column['data']) {
                case 'id':
                    $qb->orderBy('c.id', $dir);
                    break;
                case 'name':
                    $qb->orderBy('c.name', $dir);
                    break;
                case 'updatedAt':
                    $qb->orderBy('c.updatedAt', $dir);
                    break;
                default:
                    $qb->orderBy('c.name', 'ASC');
            }
        }

        // Compter le total
        $totalRecords = count($qb->getQuery()->getResult());

        // Pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);

        $categories = $qb->getQuery()->getResult();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'articlesCount' => count($category->getArticles()),
                'updatedAt' => $category->getUpdatedAt() ? $category->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                'actions' => $this->renderView('category/_actions.html.twig', ['category' => $category])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository, PaginatorInterface $paginator): Response
    {
        $query = $categoryRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );

        return $this->render('category/index.html.twig', [
            'categories' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setUpdatedBy($this->getUser());
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }
}
