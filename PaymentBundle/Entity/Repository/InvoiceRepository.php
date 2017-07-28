<?php

namespace PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * Class InvoiceRepository
 */
class InvoiceRepository extends EntityRepository
{
    /**
     * Count the total of rows
     *
     * @param int|null $actorId The actor ID
     *
     * @return int
     */
    public function countTotal($actorId = null)
    {
        $qb = $this->getQueryBuilder()
            ->select('COUNT(i)');

        if (!is_null($actorId)) {
            $qb->where('i.actor = :actor_id')
                ->setParameter('actor_id', $actorId);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
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
            ->select('i.id, i.invoiceNumber, i.created, IDENTITY(t.actor) actorId, a.name actorName, a.lastname actorLastname, a.email actorEmail, COUNT(oi) nItems');

        // join
        $qb->leftJoin('i.transaction', 't')
            ->leftJoin('t.items', 'oi')
            ->leftJoin('t.actor', 'a')
                ;

        // search
        if (!empty($search)) {
            $qb->andWhere('i.invoiceNumber = :search')
                ->setParameter('search', $search);
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('i.invoiceNumber', $sortDirection);
                break;
            case 1:
                $qb->orderBy('i.created', $sortDirection);
                break;
        }

        // group by
        $qb->groupBy('i.id');

        return $qb->getQuery();
    }

    /**
     * Get next invoice number
     *
     * @return int
     */
    public function getNextNumber()
    {
        if (0 === (int) $this->countTotal()) {
            return 1;
        }

        $qb = $this->getQueryBuilder()
            ->select('MAX(i.invoiceNumber) + 1');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    private function getQueryBuilder()
    {
        $em = $this->getEntityManager();

        $qb = $em->getRepository('PaymentBundle:Invoice')
            ->createQueryBuilder('i');

        return $qb;
    }
}