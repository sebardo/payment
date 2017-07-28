<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * CheckoutAddressable abstract class to define billing and delivery address fields
 *
 * @ORM\MappedSuperclass
 * @Assert\Callback("validateDeliveryDni")
 */
abstract class CheckoutAddressable extends Addressable
{
    /**
     * @var string
     *
     * @ORM\Column(name="delivery_dni", type="string", length=9)
     */
    protected $deliveryDni;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_address", type="string", length=255)
     */
    protected $deliveryAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_postal_code", type="string", length=5)
     */
    protected $deliveryPostalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_city", type="string", length=100)
     */
    protected $deliveryCity;

    
    /**
     * @var string
     *
     * @ORM\Column(name="delivery_state", type="string", length=100, nullable=true)
     */
    private $deliveryState;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="CoreBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=true, onDelete="set null")
     */
    private $deliveryCountry;


    /**
     * Set delivery dni
     *
     * @param string $dni
     *
     * @return Address
     */
    public function setDeliveryDni($dni)
    {
        $this->deliveryDni = $dni;

        return $this;
    }

    /**
     * Get delivery dni
     *
     * @return string
     */
    public function getDeliveryDni()
    {
        return $this->deliveryDni;
    }

    /**
     * Set delivery address information
     *
     * @param Address $address
     */
    public function setDeliveryAddressInfo(Address $address)
    {
        $this->setDeliveryAddress($address->getAddress());
        $this->setDeliveryPostalCode($address->getPostalCode());
        $this->setDeliveryCity($address->getCity());
        $this->setDeliveryState($address->getState());
        $this->setDeliveryCountry($address->getCountry());
    }

    /**
     * Get delivery address information
     *
     * @return Address
     */
    public function getDeliveryAddressInfo()
    {
        $address = new Address();

        $address->setAddress($this->getDeliveryAddress());
        $address->setPostalCode($this->getDeliveryPostalCode());
        $address->setCity($this->getDeliveryCity());
        $address->setState($this->getDeliveryState());
        $address->setCountry($this->getDeliveryCountry());

        return $address;
    }

    /**
     * Set delivery address
     *
     * @param string $address
     *
     * @return Address
     */
    public function setDeliveryAddress($address)
    {
        $this->deliveryAddress = $address;

        return $this;
    }

    /**
     * Get delivery address
     *
     * @return string
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    /**
     * Set delivery postal code
     *
     * @param string $postalCode
     *
     * @return Address
     */
    public function setDeliveryPostalCode($postalCode)
    {
        $this->deliveryPostalCode = $postalCode;

        return $this;
    }

    /**
     * Get delivery postal code
     *
     * @return string
     */
    public function getDeliveryPostalCode()
    {
        return $this->deliveryPostalCode;
    }

    /**
     * Set delivery city
     *
     * @param string $city
     *
     * @return Address
     */
    public function setDeliveryCity($city)
    {
        $this->deliveryCity = $city;

        return $this;
    }

    /**
     * Get delivery city
     *
     * @return string
     */
    public function getDeliveryCity()
    {
        return $this->deliveryCity;
    }

    /**
     * Set delivery province
     *
     * @param integer $state
     *
     * @return Address
     */
    public function setDeliveryState($state)
    {
        $this->deliveryState = $state;

        return $this;
    }

    /**
     * Get delivery state
     *
     * @return integer
     */
    public function getDeliveryState()
    {
        return $this->deliveryState;
    }

    /**
     * Set delivery country
     *
     * @param integer $country
     *
     * @return Address
     */
    public function setDeliveryCountry($country)
    {
        $this->deliveryCountry = $country;

        return $this;
    }

    /**
     * Get delivery country
     *
     * @return integer
     */
    public function getDeliveryCountry()
    {
        return $this->deliveryCountry;
    }

    /**
     * Custom validator to check delivery Dni
     *
     * @param ExecutionContextInterface $context
     */
    public function validateDeliveryDni(ExecutionContextInterface $context)
    {
        $dni = $this->getDeliveryDni();

        if (is_null($dni)) {
            return;
        }

        // check format
        if (0 === preg_match("/\d{1,8}[a-z]/i", $dni)) {
            $context->addViolationAt('dni', 'Invalid DNI number');

            return;
        }

        // check letter
        $number = substr($dni, 0, -1);
        $letter  = strtoupper(substr($dni, -1));
        if ($letter != substr("TRWAGMYFPDXBNJZSQVHLCKE", strtr($number, "XYZ", "012")%23, 1)) {
            $context->addViolationAt('dni', 'Invalid DNI letter');
        }
    }
}