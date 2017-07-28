<?php
namespace PaymentBundle\Entity\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * PayPalForm Model
**/
class PayPalForm
{

    protected $validator;
    
    protected $paypal;
       
    public function __construct($validator) {
        $this->validator = $validator;
    }

    public function getPaypal() {
        return $this->paypal;
    }
    
    public function setPaypal($paypal) {
        $this->paypal = $paypal;
    }
}
