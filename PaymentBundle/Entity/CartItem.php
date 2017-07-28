<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CartItem Entity class
 *
 * 
 * @ORM\Table(name="cart_item")
 * @ORM\Entity()
 */
class CartItem
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
     * @var Cart
     *
     * @ORM\ManyToOne(targetEntity="Cart", inversedBy="items")
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank
     */
    protected $cart;

    /**
     * Dynamic
     */
    private $product;
    
    /**
     * Quantity.
     *
     * @var integer
     * 
     * @ORM\Column(name="quantity", type="integer")
     */
    protected $quantity;

    /**
     * Unit price.
     *
     * @var float
     * 
     * @ORM\Column(name="unit_price", type="decimal", precision=10, scale=2)
     */
    protected $unitPrice;
    
    /**
     * Unit price.
     *
     * @var float
     * 
     * @ORM\Column(name="shipping_cost", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $shippingCost;

    /**
     * @var boolean
     *
     * @ORM\Column(name="store_pickup", type="boolean", nullable=true)
     */
    private $storePickup; 
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="free_transport", type="boolean")
     */
    private $freeTransport;
    
    /**
     * Total value.
     *
     * @var float
     * 
     * @ORM\Column(name="total", type="decimal", precision=10, scale=2)
     */
    protected $total;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->quantity = 1;
        $this->unitPrice = 0;
        $this->total = 0;
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
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * {@inheritdoc}
     */
    public function setCart(Cart $cart = null)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Set product
     *
     * @param Product $product
     *
     * @return CartItem
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

    /**
     * {@inheritdoc}
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * {@inheritdoc}
     */
    public function setQuantity($quantity)
    {
        if (!is_integer($quantity)) {
            throw new \InvalidArgumentException(
                sprintf('Cart item accepts only integer as quantity, "%s" given.', gettype($quantity))
            );
        }

        $this->quantity = $quantity;

        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * {@inheritdoc}
     */
    public function setUnitPrice($unitPrice)
    {
        /*
        if (!is_integer($unitPrice)) {
            throw new \InvalidArgumentException(
                sprintf('Cart item accepts only integer as unit price, "%s" given.', gettype($unitPrice))
            );
        }
        */

        $this->unitPrice = $unitPrice;

        return $this;
    }

    
    /**
     * {@inheritdoc}
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    /**
     * Get storePickup
     *
     * @return float
     */
    public function getStorePickup()
    {
        return $this->storePickup;
    }

    /**
     * Set storePickup
     *
     * @param float $storePickup
     *
     * @return Product
     */
    public function setStorePickup($storePickup)
    {
        $this->storePickup = $storePickup;

        return $this;
    }    
    
    /**
     * Is free Transport?
     *
     * @return boolean
     */
    public function isFreeTransport()
    {
        return $this->freeTransport;
    }

    /**
     * Set Free Transport
     *
     * @param boolean $freeTransport
     *
     * @return Product
     */
    public function setFreeTransport($freeTransport)
    {
        $this->freeTransport = $freeTransport;

        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * {@inheritdoc}
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateTotal()
    {
        $this->total = $this->quantity * $this->unitPrice;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(CartItem $cartItem)
    {
        return $this->getId() === $cartItem->getId();
    }
    
    /**
     * {@inheritdoc}
     */
    public function equalsProduct(CartItem $cartItem)
    {
        return $this->getProduct()->getId() === $cartItem->getProduct()->getId();
    }
    
}
