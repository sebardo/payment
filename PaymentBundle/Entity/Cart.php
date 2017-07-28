<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Cart Entity class
 *
 * 
 * @ORM\Table(name="cart")
 * @ORM\Entity(repositoryClass="PaymentBundle\Entity\Repository\CartRepository")
 */
class Cart 
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Items in cart.
     *
     * @var Collection
     * 
     * @ORM\OneToMany(targetEntity="PaymentBundle\Entity\CartItem", mappedBy="cart",  orphanRemoval=true, cascade={"all"})
     */
    protected $items;

    /**
     * Total items count.
     *
     * @var integer
     * 
     * @ORM\Column(name="total_items", type="integer")
     */
    protected $totalItems;

    /**
     * Total value.
     *
     * @var float
     * 
     * @ORM\Column(name="total", type="decimal", precision=10, scale=2)
     */
    protected $total;

    /**
     * Is cart locked?
     * Locked carts should not be removed
     * even if expired.
     *
     * @var Boolean
     * 
     * @ORM\Column(name="available", type="boolean")
     */
    protected $locked;

    /**
     * Expiration time.
     *
     * @var \DateTime
     * 
     * @ORM\Column(name="expires_at", type="datetime")
     */
    protected $expiresAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->totalItems = 0;
        $this->total = 0;
        $this->locked = false;
        $this->incrementExpiresAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalItems($totalItems)
    {
        if (0 > $totalItems) {
            throw new \OutOfRangeException('Total items must not be less than 0');
        }

        $this->totalItems = $totalItems;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeTotalItems($amount)
    {
        $this->totalItems += $amount;

        if (0 > $this->totalItems) {
            $this->totalItems = 0;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return 0 === $this->countItems();
    }

    /**
     * {@inheritdoc}
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function setItems(Collection $items)
    {
        foreach($this->items as $item){
            $this->removeItem($item);
        }

        foreach($items as $item){
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearItems()
    {
        $this->items->clear();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countItems()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function addItem(CartItem $item)
    {
        
        if ($this->items->contains($item)) {
            return $this;
        }

        foreach ($this->items as $existingItem) {
            if ($item->equals($existingItem)) {
                $existingItem->setQuantity($existingItem->getQuantity() + $item->getQuantity());

                return $this;
            }
            if ($item->equalsProduct($existingItem)) {
                $existingItem->setQuantity($existingItem->getQuantity() + $item->getQuantity());

                return $this;
            }
        }

        $this->items->add($item);
        $item->setCart($this);

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function removeItem(CartItem $item)
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);

            $item->setCart(null);
        }

        return $this;
    }

    public function hasItem(CartItem $item)
    {
        return $this->items->contains($item);
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    public function calculateTotal()
    {
        // Reset total.
        $this->total = 0;

        foreach ($this->items as $item) {
            $item->calculateTotal();

            $this->total += $item->getTotal();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        return $this->getExpiresAt() < new \DateTime('now');
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpiresAt(\DateTime $expiresAt = null)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementExpiresAt()
    {
        $expiresAt = new \DateTime();
        $expiresAt->add(new \DateInterval('PT3H'));

        $this->expiresAt = $expiresAt;

        return $this;
    }
}
