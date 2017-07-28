<?php
namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="payment_method");
 */
class PaymentMethod
{

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
    * @ORM\Column(type="string", length=255)
    */
    private $name;

    /**
     * @var Invoice
     *
     * @ORM\OneToOne(targetEntity="PaymentServiceProvider", mappedBy="paymentMethod", cascade={"persist", "remove"})
     */
    private $paymentServiceProvider;
    
    /**
     * @var string
     *
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=255, unique=true)
     */
    private $slug;

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
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set paymentServiceProvider
     *
     * @param string $paymentServiceProvider
     */
    public function setPaymentServiceProvider($paymentServiceProvider)
    {
        $this->paymentServiceProvider = $paymentServiceProvider;
    }

    /**
     * Get paymentServiceProvider
     *
     * @return string
     */
    public function getPaymentServiceProvider()
    {
        return $this->paymentServiceProvider;
    }
    
    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return MenuItem
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }
    
    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }
    
}
