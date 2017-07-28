<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use CoreBundle\Entity\Actor;

/**
 * Address Entity class
 *
 * @ORM\Table(name="address")
 * @ORM\Entity(repositoryClass="PaymentBundle\Entity\Repository\AddressRepository")
 */
class Address extends Addressable
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
     * @var boolean
     *
     * @ORM\Column(name="for_billing", type="boolean")
     */
    private $forBilling;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_person", type="string", length=255, nullable=true)
     */
    private $contactPerson;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=9, nullable=true)
     * @Assert\Length(
     *   min = "9",
     *   max = "9"
     * )
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="phone2", type="string", length=9, nullable=true)
     * @Assert\Length(
     *   min = "9",
     *   max = "9"
     * )
     */
    private $phone2;

    /**
     * @var integer
     *
     * @ORM\Column(name="preferred_schedule", type="integer")
     */
    private $preferredSchedule=0;

    /**
     * Dynamic
     */
    private $actor;


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
     * Is for billing?
     *
     * @return boolean
     */
    public function isForBilling()
    {
        return $this->forBilling;
    }

    /**
     * Set for billing
     *
     * @param boolean $forBilling
     *
     * @return Address
     */
    public function setForBilling($forBilling)
    {
        $this->forBilling = $forBilling;

        return $this;
    }

    /**
     * Set contact person
     *
     * @param string $contactPerson
     *
     * @return Address
     */
    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    /**
     * Get contact person
     *
     * @return string
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Address
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set phone2
     *
     * @param string $phone2
     *
     * @return Address
     */
    public function setPhone2($phone2)
    {
        $this->phone2 = $phone2;

        return $this;
    }

    /**
     * Get phone2
     *
     * @return string 
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * Set preferred schedule
     *
     * @param integer $preferredSchedule
     *
     * @return Address
     */
    public function setPreferredSchedule($preferredSchedule)
    {
        $this->preferredSchedule = $preferredSchedule;

        return $this;
    }

    /**
     * Get preferred schedule
     *
     * @return integer
     */
    public function getPreferredSchedule()
    {
        return $this->preferredSchedule;
    }

    /**
     * Set actor
     *
     * @param Actor $actor
     *
     * @return Address
     */
    public function setActor($actor)
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * Get actor
     *
     * @return Actor
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->address.' - '.$this->city;
    }
}