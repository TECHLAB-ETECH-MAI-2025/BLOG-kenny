<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
   }

//    /**
//     * @return Article[] Returns an array of Article objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Article
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
    * @return array{data: Article[], total: int, filtered: int}
    */
    public function findForDataTable(int $start, int $length, string $search): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->leftJoin('a.comments', 'com');

        // Total count
        $total = $this->count([]);

        // Apply search
        if ($search) {
            $qb->andWhere('a.title LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Get filtered count
        $filtered = (clone $qb)->select('COUNT(DISTINCT a.id)')->getQuery()->getSingleScalarResult();

        // Get results
        $qb->setFirstResult($start)
            ->setMaxResults($length)
            ->orderBy('a.id', 'DESC');

        return [
            'data' => $qb->getQuery()->getResult(),
            'total' => $total,
            'filtered' => $filtered
        ];
    }

    /**
     * @return Article[]
     */
    public function searchByTitle(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
