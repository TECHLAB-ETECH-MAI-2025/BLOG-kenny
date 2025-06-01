<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    //    /**
    //     * @return Comment[] Returns an array of Comment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Comment
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return array{data: Comment[], total: int, filtered: int}
     */
    public function findForDataTable(int $start, int $length, string $search): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.article', 'a');

        // Total count
        $total = $this->count([]);

        // Apply search
        if ($search) {
            $qb->andWhere('c.content LIKE :search OR c.author LIKE :search OR a.title LIKE :search')
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
}
