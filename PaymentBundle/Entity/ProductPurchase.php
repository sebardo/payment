<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use CoreBundle\Entity\Timestampable;

/**
 * ProductPurchase Entity class
 *
 * @ORM\Table(name="product_purchase")
 * @ORM\Entity
 */
class ProductPurchase extends Timestampable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var float
     *
     * @ORM\Column(name="base_price", type="float")
     * @Assert\NotBlank
     */
    private $basePrice;

    /**
     * @var float
     *
     * @ORM\Column(name="total_price", type="float")
     * @Assert\NotBlank
     */
    private $totalPrice;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     * @Assert\NotBlank
     */
    private $quantity;

    /**
     * @var boolean
     *
     * @ORM\Column(name="discount", type="integer", nullable=true)
     */
    private $discount;

    /**
     * @var boolean
     *
     * @ORM\Column(name="returned", type="boolean")
     * @Assert\NotBlank
     */
    private $returned;
    
    /**
     * Unit price.
     *
     * @var float
     * 
     * @ORM\Column(name="delivery_expenses", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $deliveryExpenses;

    /**
     * @var Transaction
     *
     * @ORM\ManyToOne(targetEntity="Transaction", inversedBy="items")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $transaction;

    /**
     * Dynamic
     */
    private $product;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set basePrice
     *
     * @param float $basePrice
     *
     * @return ProductPurchase
     */
    public function setBasePrice($basePrice)
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    /**
     * Get basePrice
     *
     * @return float 
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    /**
     * Set totalPrice
     *
     * @param float $totalPrice
     *
     * @return ProductPurchase
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * Get totalPrice
     *
     * @return float 
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return ProductPurchase
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Get discount
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Get formatted discount with symbol
     *
     * @return string
     */
    public function getFormattedDiscount()
    {
        if (is_null($this->discount)) {
            return '-';
        }

        return $this->discount.'%';
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return Product
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Set returned
     *
     * @param boolean $returned
     *
     * @return ProductPurchase
     */
    public function setReturned($returned)
    {
        $this->returned = $returned;

        return $this;
    }

    /**
     * Is returned?
     *
     * @return boolean 
     */
    public function isReturned()
    {
        return $this->returned;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryExpenses()
    {
        return $this->deliveryExpenses;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryExpenses($deliveryExpenses)
    {
        $this->deliveryExpenses = $deliveryExpenses;

        return $this;
    }


    /**
     * Set transaction
     *
     * @param Order $transaction
     *
     * @return ProductPurchase
     */
    public function setTransaction(Transaction $transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transaction
     *
     * @return Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Set product
     *
     * @param Product $product
     *
     * @return TransactionItem
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
    
   
    
}