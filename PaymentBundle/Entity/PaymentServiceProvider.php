<?php
namespace PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="psp");
 * @ORM\Entity(repositoryClass="PaymentBundle\Entity\Repository\PaymentServiceProviderRepository")
 */
class PaymentServiceProvider 
{
    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @var PaymentMethod
     *
     * @ORM\OneToOne(targetEntity="PaymentMethod", inversedBy="paymentServiceProvider")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $paymentMethod;

    /**
    * @ORM\Column(type="array")
    */
    protected $apiCredentialParameters;

    /**
     * Is for recurring ?
     *
     * @ORM\Column(type="boolean")
     */
    protected $recurring;

    /**
     * True if it's a testing account
     * @ORM\Column(type="boolean")
     */
    protected $isTestingAccount;

    /**
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $formClass;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $modelClass;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $appendTwigToForm;
    
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
     * Set paymentMethod
     *
     * @param PaymentMethod $paymentMethod
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Get paymentMethod
     *
     * @return PaymentBundle\Entity\PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
    
    /**
     * Set apiCredentialParameters
     *
     * @param array $apiCredentialParameters
     */
    public function setApiCredentialParameters(array $apiCredentialParameters)
    {
        $this->apiCredentialParameters = $apiCredentialParameters;
    }
    
    /**
     * Get apiCredentialParameters
     *
     * @return array
     */
    public function getApiCredentialParameters()
    {
        return $this->apiCredentialParameters;
    }
    
    /**
     * Set recurring
     *
     * @param  boolean $recurring
     * @return PaymentServiceProvider
     */
    public function setRecurring($recurring)
    {
        $this->recurring = $recurring;

        return $this;
    }

    /**
     * Get recurring
     *
     * @return PaymentServiceProvider
     */
    public function getRecurring()
    {
        return $this->recurring;
    }

    /**
     * Set isTestingAccount
     *
     * @param  boolean $isTestingAccount
     * @return PaymentServiceProvider
     */
    public function setIsTestingAccount($isTestingAccount)
    {
        $this->isTestingAccount = $isTestingAccount;

        return $this;
    }

    /**
     * Get isTestingAccount
     *
     * @return boolean
     */
    public function getIsTestingAccount()
    {
        return $this->isTestingAccount;
    }
    
    /**
     * @inheritDoc
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @inheritDoc
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @inheritDoc
     */
    public function setFormClass($formClass)
    {
        $this->formClass = $formClass;
    }

    /**
     * @inheritDoc
     */
    public function getFormClass()
    {
        return $this->formClass;
    }
    
    /**
     * @inheritDoc
     */
    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * @inheritDoc
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }
    
    /**
     * @inheritDoc
     */
    public function setAppendTwigToForm($appendTwigToForm)
    {
        $this->appendTwigToForm = $appendTwigToForm;
    }

    /**
     * @inheritDoc
     */
    public function getAppendTwigToForm()
    {
        return $this->appendTwigToForm;
    }
    
      
    
}
