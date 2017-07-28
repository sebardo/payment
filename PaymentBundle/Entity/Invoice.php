<?php

namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PaymentBundle\Entity\Addressable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Invoice Entity class
 *
 * @ORM\Table(name="invoice", indexes={@ORM\Index(columns={"invoice_number"})})
 * @ORM\Entity(repositoryClass="PaymentBundle\Entity\Repository\InvoiceRepository")
 */
class Invoice extends Addressable
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
     * @var integer
     *
     * @ORM\Column(name="invoice_number", type="integer", unique=true)
     * @Assert\NotBlank
     */
    private $invoiceNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255)
     * @Assert\NotBlank
     */
    private $fullName;

    /**
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity="Transaction", inversedBy="invoice")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $transaction;


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
     * Set invoiceNumber
     *
     * @param integer $invoiceNumber
     *
     * @return Invoice
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    /**
     * Get invoiceNumber
     *
     * @return integer 
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * Set full name
     *
     * @param string $fullName
     *
     * @return Invoice
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
     * Set transaction
     *
     * @param Transaction $transaction
     *
     * @return Invoice
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
}