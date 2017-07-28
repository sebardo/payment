<?php

namespace PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * Class AddressRepository
 */
class AddressRepository extends EntityRepository
{
    /**
     * Count the total of rows
     *
     * @param integer $actorId         The actor ID
     * @param boolean $includeBilling Include billing address or not
     *
     * @return integer
     */
    public function countTotal($actorId=null, $includeBilling = true)
    {
        $qb = $this->getQueryBuilder()
            ->select('COUNT(a)');

        if (!is_null($actorId)) {
            $qb->where('a.actor = :actor_id')
                ->setParameter('actor_id', $actorId);
        }

        $qb->andWhere('a.forBilling = :includeBilling')
            ->setParameter('includeBilling', $includeBilling);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find all rows filtered for DataTables
     *
     * @param string  $search        The search string
     * @param integer $sortColumn    The column to sort by
     * @param string  $sortDirection The direction to sort the column
     * @param integer $actorId        The actor ID
     *
     * @return \Doctrine\ORM\Query
     */
    public function findAllForDataTables($search, $sortColumn, $sortDirection, $actorId)
    {
        // select
        $qb = $this->getQueryBuilder()
            ->select('a.id, a.dni, a.phone, a.address,  a.city, s.name stateName, c.name countryName, a.postalCode, a.forBilling, IDENTITY(a.actor) actorId')
            ->join('a.state', 's')
            ->join('a.country', 'c');
        // where
        $qb->where('a.actor = :actor_id')
            ->setParameter('actor_id', $actorId);

        // search
        if (!empty($search)) {
            $qb->andWhere('(a.dni LIKE :search OR
                            a.address LIKE :search OR
                            a.postalCode LIKE :search OR
                            a.city LIKE :search)')
                ->setParameter('search', '%'.$search.'%');
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('a.id', $sortDirection);
                break;
            case 1:
                $qb->orderBy('a.dni', $sortDirection);
                break;
            case 2:
                $qb->orderBy('a.address', $sortDirection);
                break;
            case 3:
                $qb->orderBy('a.postalCode', $sortDirection);
                break;
            case 4:
                $qb->orderBy('a.city', $sortDirection);
                break;
        }

        return $qb->getQuery();
    }

    /**
     * Remove the forBilling field to all addresses of the given actor
     *
     * @param integer $actorId
     */
    public function removeForBillingToAllAddresses($actorId)
    {
        $qb = $this->getQueryBuilder()
            ->update()
            ->set('a.forBilling', 0)
            ->where('a.actor = :actor')
            ->setParameter('actor', $actorId);

        $qb->getQuery()->execute();
    }

    private function getQueryBuilder()
    {
        $em = $this->getEntityManager();

        $qb = $em->getRepository('PaymentBundle:Address')
            ->createQueryBuilder('a');

        return $qb;
    }
}