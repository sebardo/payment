<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PaymentBundle\Entity\CheckoutAddressable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Delivery Entity class
 *
 * @ORM\Table(name="delivery")
 * @ORM\Entity
 */
class Delivery extends CheckoutAddressable
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
     * @ORM\Column(name="expenses", type="float")
     */
    private $expenses;

    /**
     * @var integer
     *
     * @ORM\Column(name="expenses_type", type="string", nullable=true)
     */
    private $expensesType;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255)
     */
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_person", type="string", length=255, nullable=true)
     */
    private $contactPerson;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=9)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="phone2", type="string", length=9, nullable=true)
     */
    private $phone2;

    /**
     * @var integer
     *
     * @ORM\Column(name="preferred_schedule", type="integer")
     */
    private $preferredSchedule;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_contact_person", type="string", length=255, nullable=true)
     */
    private $deliveryContactPerson;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_phone", type="string", length=9)
     */
    private $deliveryPhone;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_phone2", type="string", length=9, nullable=true)
     */
    private $deliveryPhone2;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_preferred_schedule", type="integer")
     */
    private $deliveryPreferredSchedule;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity="Transaction", inversedBy="delivery")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $transaction;

    /**
     * @var string
     *
     * @ORM\Column(name="tracking_code", type="string", nullable=true)
     */
    private $trackingCode;


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
     * Set expenses
     *
     * @param float $expenses
     *
     * @return Delivery
     */
    public function setExpenses($expenses)
    {
        $this->expenses = $expenses;

        return $this;
    }

    /**
     * Get expenses
     *
     * @return float 
     */
    public function getExpenses()
    {
        return $this->expenses;
    }

    /**
     * Set expenses type
     *
     * @param integer $type
     *
     * @return Delivery
     */
    public function setExpensesType($type)
    {
        $this->expensesType = $type;

        return $this;
    }

    /**
     * Get expenses type
     *
     * @return integer
     */
    public function getExpensesType()
    {
        return $this->expensesType;
    }

    /**
     * Set full name
     *
     * @param string $fullName
     *
     * @return Delivery
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get full name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set contact person
     *
     * @param string $contactPerson
     *
     * @return Delivery
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
     * @return Delivery
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
     * @return Delivery
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
     * @return Delivery
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
     * Set delivery contact person
     *
     * @param string $contactPerson
     *
     * @return Delivery
     */
    public function setDeliveryContactPerson($contactPerson)
    {
        $this->deliveryContactPerson = $contactPerson;

        return $this;
    }

    /**
     * Get delivery contact person
     *
     * @return string
     */
    public function getDeliveryContactPerson()
    {
        return $this->deliveryContactPerson;
    }

    /**
     * Set delivery phone
     *
     * @param string $phone
     *
     * @return Delivery
     */
    public function setDeliveryPhone($phone)
    {
        $this->deliveryPhone = $phone;

        return $this;
    }

    /**
     * Get delivery phone
     *
     * @return string
     */
    public function getDeliveryPhone()
    {
        return $this->deliveryPhone;
    }

    /**
     * Set delivery phone2
     *
     * @param string $phone2
     *
     * @return Delivery
     */
    public function setDeliveryPhone2($phone2)
    {
        $this->deliveryPhone2 = $phone2;

        return $this;
    }

    /**
     * Get delivery phone2
     *
     * @return string
     */
    public function getDeliveryPhone2()
    {
        return $this->deliveryPhone2;
    }

    /**
     * Set delivery preferred schedule
     *
     * @param integer $preferredSchedule
     *
     * @return Delivery
     */
    public function setDeliveryPreferredSchedule($preferredSchedule)
    {
        $this->deliveryPreferredSchedule = $preferredSchedule;

        return $this;
    }

    /**
     * Get delivery preferred schedule
     *
     * @return integer
     */
    public function getDeliveryPreferredSchedule()
    {
        return $this->deliveryPreferredSchedule;
    }

    /**
     * Set notes
     *
     * @param string $notes
     *
     * @return Delivery
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set transaction
     *
     * @param Transaction $transaction
     *
     * @return Delivery
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
     * Set tracking code
     *
     * @param integer $trackingCode
     *
     * @return $this
     */
    public function setTrackingCode($trackingCode)
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    /**
     * Get tracking code
     *
     * @return string
     */
    public function getTrackingCode()
    {
        return $this->trackingCode;
    }
}