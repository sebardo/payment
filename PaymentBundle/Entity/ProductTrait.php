<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * ProductTrait trait class to define product relaction with purchases
 *
 */
trait ProductTrait 
{
    
    /**
     * @var Dinamyc
     */
    protected $purchases;
    
    /**
     * @var Dinamyc
     */
    protected $carts;
    
    /**
     * @var float
     *
     * @ORM\Column(name="init_price", type="float", nullable=true)
     */
    private $initPrice;
    
    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     * @Assert\NotBlank
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="price_type", type="boolean")
     */
    private $priceType;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="discount", type="integer", nullable=true)
     */
    private $discount;
    
    /**
     * @var float
     *
     * @ORM\Column(name="discounted_price", type="float", nullable=true)
     */
    private $discountType;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="stock", type="integer")
     * @Assert\NotBlank
     */
    private $stock;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", nullable=true)
     */
    private $weight;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="store_pickup", type="boolean")
     */
    private $storePickup;
    
    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    private $reference;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="freeTransport", type="boolean")
     */
    private $freeTransport=false;
    
    private $publishDateRange;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publish_date_from", type="datetime", nullable=true)
     */
    private $publishDateFrom;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publish_date_to", type="datetime", nullable=true)
     */
    private $publishDateTo;
    
    /**
     * @var Dinamyc
     */
    private $relatedProducts;
     
    /**
     * Add purchase
     *
     * @param ProductPurchase $purchase
     *
     * @return Typography
     */
    public function addPurchase($purchase)
    {
        $this->purchases->add($purchase);

        return $this;
    }

    /**
     * Remove purchase
     *
     * @param ProductPurchase $purchase
     */
    public function removePurchase($purchase)
    {
        $this->purchases->removeElement($purchase);
    }

    /**
     * Get purchases
     *
     * @return ArrayCollection
     */
    public function getPurchases()
    {
        return $this->purchases;
    }
    
    /**
     * Add cart
     *
     * @param CartItem $cart
     *
     * @return Typography
     */
    public function addCart($cart)
    {
        $this->carts->add($cart);

        return $this;
    }

    /**
     * Remove cart
     *
     * @param CartItem $cart
     */
    public function removeCart($cart)
    {
        $this->carts->removeElement($cart);
    }

    /**
     * Get carts
     *
     * @return ArrayCollection
     */
    public function getCarts()
    {
        return $this->carts;
    }
    
    /**
     * Get initPrice
     *
     * @return float
     */
    public function getInitPrice()
    {
        $initPrice = $this->initPrice;

        return $initPrice;
    }

    /**
     * Set initPrice
     *
     * @param float $initPrice
     *
     * @return Product
     */
    public function setInitPrice($initPrice)
    {
        $this->initPrice = $initPrice;

        return $this;
    }
    
    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        $price = $this->price;

        return $price;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Product
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }
    
    /**
     * Get priceType
     *
     * @return float
     */
    public function getPriceType()
    {
        $priceType = $this->priceType;

        return $priceType;
    }

    /**
     * Set priceType
     *
     * @param float $priceType
     *
     * @return Product
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;

        return $this;
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
     * Get discountType
     *
     * @return integer
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * Set $discountType
     *
     * @param integer $discountType
     *
     * @return Product
     */
    public function setDiscountType($discountType)
    {
        $this->discountType = $discountType;

        return $this;
    }
    
    /**
     * Get stock
     *
     * @return integer
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Set stock
     *
     * @param integer $stock
     *
     * @return Product
     */
    public function setStock($stock)
    {
        $this->stock = $stock;

        return $this;
    }
    
    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set weight
     *
     * @param float $weight
     *
     * @return Product
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

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
     * Get reference
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set reference
     *
     * @param string $reference
     *
     * @return Product
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

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
     * @return string
     */
    public function getPublishDateRange()
    {
        $from = new \DateTime();
        $to = clone $from;
//        $dateString = $from->format('d/m/Y').' '.$to->format('d/m/Y');
        $dateString = '';
        if($this->publishDateFrom != '' && $this->publishDateTo != '')
        $dateString = $this->publishDateFrom->format('d/m/Y').' '.$this->publishDateTo->format('d/m/Y');
        return $dateString;
    }

    /**
     * @param string $publishDateRange
     */
    public function setPublishDateRange($publishDateRange)
    {
        if($publishDateRange != ''){
            $arr = explode(' ', $publishDateRange);
            $this->publishDateFrom = \DateTime::createFromFormat('d/m/Y', $arr[0]);
            $this->publishDateTo = \DateTime::createFromFormat('d/m/Y', $arr[1]);
        }
    }
    
    /**
     * @return \DateTime
     */
    public function getPublishDateFrom()
    {
        return $this->publishDateFrom;
    }

    /**
     * @param \DateTime $publishDateFrom
     */
    public function setPublishDateFrom($publishDateFrom)
    {
        $this->publishDateFrom = $publishDateFrom;
    }
    
    /**
     * @return \DateTime
     */
    public function getPublishDateTo()
    {
        return $this->publishDateTo;
    }

    /**
     * @param \DateTime $publishDateTo
     */
    public function setPublishDateTo($publishDateTo)
    {
        $this->publishDateTo = $publishDateTo;
    }
    
    /**
     * Add related Product
     *
     * @param Product $relatedProduct
     *
     * @return Product
     */
    public function addRelatedProduct($relatedProduct)
    {
        $this->relatedProducts->add($relatedProduct);

        return $this;
    }

    /**
     * Remove relatedProduct
     *
     * @param Product $relatedProduct
     */
    public function removeRelatedProduct($relatedProduct)
    {
        $this->relatedProducts->removeElement($relatedProduct);
    }

    /**
     * Get relatedProducts
     *
     * @return ArrayCollection
     */
    public function getRelatedProducts()
    {
        return $this->relatedProducts;
    }
    
    static function PRICE_TYPE_FIXED()
    {
        return 0;
    }
    
    static function PRICE_TYPE_PERCENT()
    {
        return 1;
    }
}