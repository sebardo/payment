<?php

namespace PaymentBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use PaymentBundle\Entity\Transaction;
use CoreBundle\Entity\Actor;


/**
 * Class TransactionRepository
 */
class TransactionRepository extends EntityRepository
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
            ->select('COUNT(t)');

        if (!is_null($actorId)) {
            $qb->where('t.actor = :actor_id')
                ->setParameter('actor_id', $actorId);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Count the total of rows
     *
     * @param int|null $actorId The actor ID
     *
     * @return int
     */
    public function totalAmountCharged()
    {
        $qb = $this->getQueryBuilder()
            ->select('SUM(t.totalPrice)')
            ->innerJoin('t.items', 'i')
            ->innerJoin('i.product', 'p')
            ->where('p.priceType = 0')
            ->andWhere('t.status = :status OR t.status = :status2 OR t.status = :status3')
            ->setParameter('status', Transaction::STATUS_PAID)
            ->setParameter('status2', Transaction::STATUS_COMPLETED)
            ->setParameter('status3', Transaction::STATUS_DELIVERED)
                ;
                
        if(is_null($qb->getQuery()->getSingleScalarResult())) return '0';
                
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get month income between two dates
     *
     * @param array $dates
     *
     * @return int
     */
    public function getTotalBetweenDates($dates)
    {
        $date1 = new \DateTime($dates['date1']);
        $date2 = new \DateTime($dates['date2']);

        $qb = $this->getQueryBuilder()
            ->select('SUM(i.totalPrice)')
            ->innerJoin('t.items', 'i')
            ->where('t.status = :status')
            ->andWhere('t.created BETWEEN :date1 AND :date2')
            ->setParameter('status', Transaction::STATUS_PAID)
            ->setParameter('date1', $date1)
            ->setParameter('date2', $date2);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all income
     *
     * @return int
     */
    public function getAll()
    {

        $qb = $this->getQueryBuilder()
            ->select('SUM(i.totalPrice)')
            ->innerJoin('t.items', 'i')
            ->where('t.status = :status')
            ->setParameter('status', Transaction::STATUS_PAID);

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get best seller products
     *
     * @param int|null $limit
     *
     * @return ArrayCollection
     */
    public function getBestSellerProducts($limit = null)
    {
        $qb = $this->getItemQueryBuilder()
            ->select('p.id, p.name, COUNT(p.id) totalSellings')
            ->innerJoin('i.product', 'p')
            ->groupBy('p.id')
            ->orderBy('totalSellings', 'desc');

        if (!is_null($limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()
            ->getSingleResult();
    }
    
    /**
     * Get best seller products
     *
     * @param int|null $limit
     *
     * @return ArrayCollection
     */
    public function getCountCupon()
    {
        $qb = $this->getItemQueryBuilder()
            ->select('COUNT(p.id) total')
            ->innerJoin('i.product', 'p')
            ->where('p.priceType = 1');

       
        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all rows filtered for DataTables
     *
     * @param string $search        The search string
     * @param int    $sortColumn    The column to sort by
     * @param string $sortDirection The direction to sort the column
     * @param int    $actorId        The actor ID
     *
     * @return \Doctrine\ORM\Query
     */
    public function findAllForDataTables($search, $sortColumn, $sortDirection, $actorId = null)
    {
        // select
        $qb = $this->getQueryBuilder()
            ->select('t.id, t.transactionKey, t.totalPrice, t.tax, t.status, t.created, IDENTITY(t.actor) actorId, a.name actorName, a.lastname actorSurname, COUNT(i) nItems');

   
        // join
        $qb->leftJoin('t.items', 'i')
            ->leftJoin('t.actor', 'a')
                ;

         $qb->where('t.status != :status')
            ->setParameter('status', 'created');
        // where
        if (!is_null($actorId)) {
            $qb->andWhere('t.actor = :actor_id')
                ->setParameter('actor_id', $actorId);
        }

        // search
        if (!empty($search)) {
            $qb->andWhere('t.transactionKey = :search')
                ->setParameter('search', $search);
        }

        // sort by column
        if (is_null($actorId)) {
            switch($sortColumn) {
                case 0:
                    $qb->orderBy('t.transactionKey', $sortDirection);
                    break;
                case 1:
                    $qb->orderBy('t.created', $sortDirection);
                    break;
                case 4:
                    $qb->orderBy('t.status', $sortDirection);
                    break;
            }
        } else {
            switch($sortColumn) {
                case 0:
                    $qb->orderBy('t.transactionKey', $sortDirection);
                    break;
                case 1:
                    $qb->orderBy('t.created', $sortDirection);
                    break;
                case 3:
                    $qb->orderBy('t.status', $sortDirection);
                    break;
            }
        }

        // group by
        $qb->groupBy('t.id');

        return $qb->getQuery();
    }
    
    /**
     * Find all rows filtered for DataTables
     *
     * @param string $search        The search string
     * @param int    $sortColumn    The column to sort by
     * @param string $sortDirection The direction to sort the column
     * @param int    $actorId        The actor ID
     *
     * @return \Doctrine\ORM\Query
     */
    public function findAllForDataTablesByActor($search, $sortColumn, $sortDirection, Actor $actor)
    {
        // select
        $qb = $this->getQueryBuilder()
            ->select('t.id, t.transactionKey, t.paymentMethod, t.totalPrice, t.tax, t.status, t.created, IDENTITY(t.actor) actorId, a.name actorName, a.lastname actorSurname, COUNT(i) nItems');
   
        // join
        $qb->leftJoin('t.items', 'i')
            ->leftJoin('t.actor', 'a')
                ;

        // where
        $qb->where('t.status != :status')
            ->andWhere('a.id = :actor')
            ->setParameter('status', 'created')
            ->setParameter('actor', $actor);

        // search
        if (!empty($search)) {
            $qb->andWhere('t.transactionKey = :search')
                ->setParameter('search', $search);
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('t.transactionKey', $sortDirection);
                break;
            case 1:
                $qb->orderBy('t.created', $sortDirection);
                break;
            case 4:
                $qb->orderBy('t.status', $sortDirection);
                break;
        }

        // group by
        $qb->groupBy('t.id');

        return $qb->getQuery();
    }
    
    /**
     * Find all rows filtered for DataTables
     *
     * @param string $search        The search string
     * @param int    $sortColumn    The column to sort by
     * @param string $sortDirection The direction to sort the column
     * @param int    $agreementId   The Agreement ID
     *
     * @return \Doctrine\ORM\Query
     */
    public function findByAgreementForDataTables($search, $sortColumn, $sortDirection, $agreementId = null)
    {
        // select
        $qb = $this->getQueryBuilder()
            ->select('t.id, t.transactionKey, t.totalPrice, t.tax,  t.status, t.created, IDENTITY(t.actor) actorId, COUNT(i) nItems');

        // join
        $qb->leftJoin('t.items', 'i')
            ->leftJoin('t.actor', 'a')
            ->leftJoin('t.agreement', 'agree')
                 ;

         $qb->where('t.status != :status')
            ->setParameter('status', 'created');
        // where
        if (!is_null($agreementId)) {
            $qb->andWhere('t.agreement = :agreementId')
                ->setParameter('agreementId', $agreementId);
        }

        // search
        if (!empty($search)) {
            $qb->andWhere('t.transactionKey = :search')
                ->setParameter('search', $search);
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('t.transactionKey', $sortDirection);
                break;
            case 1:
                $qb->orderBy('t.created', $sortDirection);
                break;
            case 3:
                $qb->orderBy('t.status', $sortDirection);
                break;
        }
                

        // group by
        $qb->groupBy('t.id');

        return $qb->getQuery();
    }
    
    /**
     * Find all rows filtered for DataTables
     *
     * @param string $search        The search string
     * @param int    $sortColumn    The column to sort by
     * @param string $sortDirection The direction to sort the column
     * @param int    $actorId        The actor ID
     *
     * @return \Doctrine\ORM\Query
     */
    public function findByAdvertForDataTables($search, $sortColumn, $sortDirection, $advertId = null)
    {
        // select
        $qb = $this->getQueryBuilder()
            ->select('t.id, t.transactionKey, t.totalPrice, t.tax,  t.status, t.created, IDENTITY(t.actor) actorId, COUNT(i) nItems');

        // join
        $qb->leftJoin('t.items', 'i')
            ->leftJoin('t.actor', 'a')
            ->leftJoin('i.advert', 'adv')
                 ;

         $qb->where('t.status != :status')
            ->setParameter('status', 'created');
        // where
        if (!is_null($advertId)) {
            $qb->andWhere('i.advert = :advertId')
                ->setParameter('advertId', $advertId);
        }

        // search
        if (!empty($search)) {
            $qb->andWhere('t.transactionKey = :search')
                ->setParameter('search', $search);
        }

        // sort by column
        switch($sortColumn) {
            case 0:
                $qb->orderBy('t.transactionKey', $sortDirection);
                break;
            case 1:
                $qb->orderBy('t.created', $sortDirection);
                break;
            case 3:
                $qb->orderBy('t.status', $sortDirection);
                break;
        }
                

        // group by
        $qb->groupBy('t.id');

        return $qb->getQuery();
    }

    /**
     * Find all rows with related actors
     *
     * @param int|null $limit
     *
     * @return ArrayCollection
     */
    public function findLatestWithUsers($limit = null)
    {
        $qb = $this->getQueryBuilder()
            ->select('t.id, t.transactionKey, t.status, t.created, o.id userId, o.name userName, SUM(i.totalPrice) totalPrice')
            ->leftJoin('t.actor', 'o')
            ->leftJoin('t.items', 'i')
            ->orderBy('t.id', 'desc');

        if (!is_null($limit)) {
            $qb->setMaxResults($limit);
        }

        $qb->groupBy('t.id');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Find all finished orders by the given actor
     *
     * @param Owner $actor
     *
     * @return ArrayCollection
     */
    public function findAllFinished($actor)
    {
        $qb = $this->getQueryBuilder()
            ->where('t.actor = :actor')
            ->andWhere('t.status != :status')
            ->orderBy('t.created', 'desc')
            ->setParameter('actor', $actor)
            ->setParameter('status', 'Unfinished');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Get next order number
     *
     * @return int
     */
    public function getNextNumber()
    {
        return uniqid();
//        if (0 === $this->countTotal()) {
//            return 1;
//        }
//
//        $qb = $this->getQueryBuilder()
//            ->select('MAX(t.transactionKey) + 1');
//
//        return $qb->getQuery()
//            ->getSingleScalarResult();
    }

    /**
     * Remove all product purchase items
     *
     * @param Transaction $transaction
     */
    public function removeItems(Transaction $transaction)
    {
        $qb = $this->getItemQueryBuilder()
            ->delete()
            ->where('i.transaction = :transaction')
            ->setParameter('transaction', $transaction);

        $qb->getQuery()->execute();
    }

    /**
     * Find all finished orders by the given actor
     *
     * @param string $search
     *
     * @return ArrayCollection
     */
    public function findOnPaymentDetails($search)
    {
        $qb = $this->getQueryBuilder()
            ->select('t')
            ->where('t.paymentDetails LIKE :search')
            ->setParameter('search', '%'.$search.'%');
         
        return $qb->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Find all finished orders by the given actor
     *
     * @param string $search, $transactionKey
     *
     * @return ArrayCollection
     */
    public function findByPaymentDetailsAndTransactionKey($search, $transactionKey)
    {
        $qb = $this->getQueryBuilder()
            ->select('t')
            ->where('t.paymentDetails LIKE :search')
            ->andWhere('t.transactionKey = :transactionKey')
            ->setParameters(array('search' => '%'.$search.'%', 'transactionKey' => $transactionKey));
         
        return $qb->getQuery()
            ->getOneOrNullResult();
    }
    
    private function getQueryBuilder()
    {
        $em = $this->getEntityManager();

        $qb = $em->getRepository('PaymentBundle:Transaction')
            ->createQueryBuilder('t');

        return $qb;
    }

    private function getItemQueryBuilder()
    {
        $em = $this->getEntityManager();

        $qb = $em->getRepository('PaymentBundle:ProductPurchase')
            ->createQueryBuilder('i');

        return $qb;
    }
}