<?php

namespace PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class PaymentServiceProviderRepository
 */
class PaymentServiceProviderRepository extends EntityRepository
{
    /**
     * Count the total of rows
     *
     * @return int
     */
    public function countTotal()
    {
        $qb = $this->getQueryBuilder()
            ->select('COUNT(p)');

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
            ->select('p.id, p.recurring, p.active, p.isTestingAccount test, pm.id pmId, pm.name pmName, pm.slug pmSlug');

        //join
        $qb->leftJoin('p.paymentMethod', 'pm');
  
        // search
        if (!empty($search)) {
            $qb->where('pm.name LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('p.id', $sortDirection);
                break;
            case 1:
                $qb->orderBy('pm.name', $sortDirection);
                break;
            case 2:
                $qb->orderBy('p.recurring', $sortDirection);
                break;
            case 3:
                $qb->orderBy('test', $sortDirection);
                break;
            case 4:
                $qb->orderBy('p.active', $sortDirection);
                break;
        }

        if($sortColumn=='') $qb->orderBy('p.id', 'ASC');
        return $qb->getQuery();
    }

    private function getQueryBuilder()
    {
        $em = $this->getEntityManager();

        $qb = $em->getRepository('PaymentBundle:PaymentServiceProvider')
            ->createQueryBuilder('p');

        return $qb;
    }
}