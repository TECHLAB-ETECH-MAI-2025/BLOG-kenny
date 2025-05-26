<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return array{data: Category[], total: int, filtered: int}
     */
    public function findForDataTable(int $start, int $length, string $search): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a');

        // Total count
        $total = $this->count([]);

        // Apply search
        if ($search) {
            $qb->andWhere('c.name LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Get filtered count
        $filtered = (clone $qb)->select('COUNT(DISTINCT c.id)')->getQuery()->getSingleScalarResult();

        // Get results
        $qb->setFirstResult($start)
            ->setMaxResults($length)
            ->orderBy('c.id', 'DESC');

        return [
            'data' => $qb->getQuery()->getResult(),
            'total' => $total,
            'filtered' => $filtered
        ];
    }

    /**
     * @return Category[]
     */
    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
