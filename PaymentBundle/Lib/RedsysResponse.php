<?php

namespace PaymentBundle\Lib;

use Symfony\Component\HttpFoundation\Request;


class RedsysResponse
{

    private $password;

    private $request;
    
    private $jsonValues;

    public function __construct($password, Request $request)
    {
            $this->password = $password;
            $this->request = $request;
            
            $amount = $this->request->query->get('Ds_Amount');
            $order = $this->request->query->get('Ds_Order');
            $merchantCode = $this->request->query->get('Ds_MerchantCode');
            $currency = $this->request->query->get('Ds_Currency');
            $response = $this->request->query->get('Ds_Response');
            $signature = $this->request->query->get('Ds_Signature');
            $jsonValues = json_encode(array(
                'Ds_Amount' => $amount,
                'Ds_Order' => $order,
                'Ds_MerchantCode' => $merchantCode,
                'Ds_Currency' => $currency,
                'Ds_Response' => $response,
                'Ds_Signature' => $signature
            ));
    }

    public function isValidRequest()
    {
            $amount = $this->request->query->get('Ds_Amount');
            $order = $this->request->query->get('Ds_Order');
            $merchantCode = $this->request->query->get('Ds_MerchantCode');
            $currency = $this->request->query->get('Ds_Currency');
            $response = $this->request->query->get('Ds_Response');
            $signature = $this->request->query->get('Ds_Signature');

            $message = $amount
                            . $order
                            . $merchantCode
                            . $currency
                            . $response
                            . $this->password;

            $digest = strtoupper(sha1($message));

            return $digest == $signature;
    }

    public function isAuthorized()
    {
            $r = intval($this->getResponse());
            return $r >= 0 && $r <= 99;
    }

    public function isSecurePayment()
    {
            return $this->request->query->get('Ds_SecurePayment') == 1;
    }

    public function getResponse()
    {
            return $this->request->query->get('Ds_Response');
    }

    public function getMerchantData()
    {
            return $this->request->query->get('Ds_MerchantData');
    }

    public function getOrder()
    {
            return $this->request->query->get('Ds_Order');
    }
    
    public function getJsonValues()
    {
        return $this->jsonValues;
    }

}