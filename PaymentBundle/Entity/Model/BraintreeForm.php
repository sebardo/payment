<?php
namespace PaymentBundle\Entity\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * BraintreeForm Model
**/
class BraintreeForm
{

    protected $validator;

    protected $payment_method_nonce;

    public function __construct($validator) {
        $this->validator = $validator;
    }

    public function getPaymentMethodNonce() {
        return $this->payment_method_nonce;
    }
    
    public function setPaymentMethodNonce($payment_method_nonce) {
        $this->payment_method_nonce = $payment_method_nonce;
    }
    
}
