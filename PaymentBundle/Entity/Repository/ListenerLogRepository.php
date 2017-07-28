<?php

namespace PaymentBundle\Entity\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;


class ListenerLogRepository  extends EntityRepository
{

    /**
     * Count the total of rows
     *
     * @return int
     */
    public function countTotal()
    {
        $qb = $this->getQueryBuilder()
            ->select('COUNT(l)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find all rows filtered for DataTables
     *
     * @param string $search        The search string
     * @param int    $sortColumn    The column to sort by
     * @param string $sortDirection The direction to sort the column
     *
     * @return \Doctrine\ORM\Query
     */
    public function findAllForDataTables($search, $sortColumn, $sortDirection)
    {
        // select
        $qb = $this->getQueryBuilder()
            ->select('l.id, l.type, l.valid, l.created, l.input');

        // search
        if (!empty($search)) {
            $qb->where('l.input LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('l.id', $sortDirection);
                break;
            case 1:
                $qb->orderBy('l.type', $sortDirection);
                break;
            case 2:
                $qb->orderBy('l.verified', $sortDirection);
                break;
            case 3:
                $qb->orderBy('l.created', $sortDirection);
                break;
        }

        return $qb->getQuery();
    }
    
    private function getQueryBuilder()
    {
        $em = $this->getEntityManager();

        $qb = $em->getRepository('PaymentBundle:ListenerLog')
            ->createQueryBuilder('l');
            
        return $qb;
    }

}
