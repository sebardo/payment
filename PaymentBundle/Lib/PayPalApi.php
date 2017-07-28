<?php

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use \PayPal\Api\Payment;
use \PayPal\Api\PayerInfo;
use \PayPal\Api\Details;
use \PayPal\Api\Item;
use \PayPal\Api\ShippingAddress;
use \PayPal\Api\ItemList;
use \PayPal\Exception;
use \PayPal\Api\CreditCard;
use \PayPal\Api\FundingInstrument;
use \PayPal\Exception\PayPalConnectionException;
use \PayPal\Api\Plan;
use \PayPal\Api\PaymentDefinition;
use \PayPal\Api\Currency;
use \PayPal\Api\ChargeModel;
use \PayPal\Api\MerchantPreferences;
use \PayPal\Api\PatchRequest;
use \PayPal\Api\Patch;
use \PayPal\Api\Agreement;
use \PayPal\Api\Address;


//"require": {
//    "php": ">=5.3.0",
//    "ext-curl": "*",
//    "ext-json": "*",
//    "paypal/rest-api-sdk-php" : "0.5.*"
//}

class PayPal_Direct_Payment extends PayPal_Abstract {


    public function createBillingPlan($cartSummary, $productName, $transactionId, $apiContext){

        $billingPlanDefaultValues = $this->getBillingPlanDefaultValues();

        $billingPlan = new Plan();
        $billingPlan->setName('Payment plan for '.$productName);
        $billingPlan->setDescription($cartSummary->paymentPlanTitle);
        $billingPlan->setType($billingPlanDefaultValues->type);

        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Charge for '.$productName);
        $paymentDefinition->setType('REGULAR');
        $paymentDefinition->setFrequencyInterval($billingPlanDefaultValues->interval);
        $paymentDefinition->setFrequency($billingPlanDefaultValues->frequency);
        $paymentDefinition->setCycles($billingPlanDefaultValues->cycle);

        $amount = new Currency();
        $amount->setCurrency($this->getCurrency());
        $amount->setValue($cartSummary->singleInstallmentCost);

        $paymentDefinition->setAmount($amount);

        $shippingAmount = new Currency();
        $shippingAmount->setCurrency($this->getCurrency());
        // Shipping cost is taken out in the initial payment (setup_fees)
        $shippingAmount->setValue(0);
        //$shippingAmount->setValue($cartSummary->shippingCost);

        $chargeModelShipping = new ChargeModel();
        $chargeModelShipping->setType('SHIPPING');
        $chargeModelShipping->setAmount($shippingAmount);

        $taxAmount = new Currency();
        $taxAmount->setCurrency($this->getCurrency());
        $taxAmount->setValue($cartSummary->vat);

        $chargeModelTax = new ChargeModel();
        $chargeModelTax->setType('TAX');
        $chargeModelTax->setAmount($taxAmount);

        $paymentDefinition->setChargeModels(array($chargeModelShipping, $chargeModelTax));

        $billingPlan->setPaymentDefinitions(array($paymentDefinition));

        $merchantPreferences = new MerchantPreferences();

        $setupFeesAmount = new Currency();
        $setupFeesAmount->setCurrency($this->getCurrency());
        $setupFeesAmount->setValue($cartSummary->firstInstallmentCost);

        /* PayPal just passes a token in the return Url. This token is unique for each request. So pass the transection id in the return Url. */
        $returnUrl = $this->getRecurringExpressPaymentReturnUrl();
        $returnUrl = str_replace(':id', $transactionId, $returnUrl);
        $returnUrl = str_replace(':hash', Om_Model_Abstract::generateRequestHash($transactionId), $returnUrl);

        $merchantPreferences->setSetupFee($setupFeesAmount);
        $merchantPreferences->setCancelUrl($this->getCancelUrl());
        $merchantPreferences->setReturnUrl($returnUrl);
        $merchantPreferences->setMaxFailAttempts($billingPlanDefaultValues->maxFailedBillingAttempts);
        $merchantPreferences->setAutoBillAmount($billingPlanDefaultValues->autoBillAmount);
        $merchantPreferences->setInitialFailAmountAction($billingPlanDefaultValues->initialFailAmountAction);

        $billingPlan->setMerchantPreferences($merchantPreferences);

        return $billingPlan->create($apiContext);

    }

    public function activateBillingPlan($planId, $apiContext){

        $patch = new Patch();
        $patch->setOp('replace');
        $patch->setPath('/');
        $patch->setValue(array('state' => 'ACTIVE'));

        $patchRequest = new PatchRequest();
        $patchRequest->setPatches(array($patch));

        $plan = new Plan();
        $plan->setId($planId);
        return $plan->update($patchRequest, $apiContext);

    }

    public function getBillingPlan($planId, $apiContext = null){
        if(empty($apiContext)){
            $apiContext = $this->getApiContext();
        }
        $plan = new Plan();
        return $plan->get($planId, $apiContext);
    }

    public function createBillingAgreement($planId, $shippingAddress, $billingAddress, $productName, $cartSummary, $cardDetails, $apiContext) {

        $billingPlanDefaultValues = $this->getBillingPlanDefaultValues();

        $billingAgreement = new Agreement();
        $billingAgreement->setName('Billing Agreement For '.$productName);
        $billingAgreement->setDescription($cartSummary->paymentPlanTitle);

        $startDate = new Zend_Date();
        $startDate->addDay($billingPlanDefaultValues->startDateInterval);
        $billingAgreement->setStartDate($startDate->get(Zend_Date::ISO_8601));

        $payerInfo  = new PayerInfo();
        $payerInfo->setFirstName($billingAddress->firstname);
        $payerInfo->setLastName($billingAddress->lastname);
        $payerInfo->setEmail($billingAddress->emailAddress);

        /* Fields not supported yet */
        //$payerInfo->setEmail($cart->address->billing['billing_email']);
        //$payerInfo->setPhone($cart->address->billing['billing_contactNo']);

        /* Get a MALFORMED_REQUEST error when using this field */
        //$payerInfo->setCountryCode($cart->address->billing['billing_countryCode']);

        $cardName = $cardDetails->cardName;
        $cardNumber = $cardDetails->cardNumber;
        $cardType = strtolower($cardDetails->cardType);
        $cardExpiryMonth = $cardDetails->cardExpiryMonth;
        $cardExpiryYear = $cardDetails->cardExpiryYear;
        $cardSecurityCode = $cardDetails->cardSecurityCode;

        $nameParser = new Om_Model_Name();
        $name = $nameParser->parse_name($cardName);
        $card = new CreditCard();
        $card->setType($cardType);
        $card->setNumber($cardNumber);
        $card->setExpireMonth($cardExpiryMonth);
        $card->setExpireYear($cardExpiryYear);
        $card->setCvv2($cardSecurityCode);
        $card->setFirstName($name['fname']);
        $card->setLastName($name['lname']);

        $fundingInstrument = new FundingInstrument();
        $fundingInstrument->setCreditCard($card);

        $payer = new Payer();
        $payer->setPaymentMethod("credit_card");
        $payer->setFundingInstruments(array($fundingInstrument));
        $payer->setPayerInfo($payerInfo);

        $billingAgreement->setPayer($payer);

        $shippingAddressPayPal = new Address();
        $shippingAddressPayPal->setLine1($shippingAddress->addressLine1);
        $shippingAddressPayPal->setLine2($shippingAddress->addressLine2. ' ' .$shippingAddress->addressLine3);
        $shippingAddressPayPal->setCity($shippingAddress->city);
        $shippingAddressPayPal->setCountryCode($shippingAddress->getCountry()->code);
        $shippingAddressPayPal->setPostalCode($shippingAddress->postcode);
        $shippingAddressPayPal->setState($shippingAddress->county);
        $shippingAddressPayPal->setPhone($shippingAddress->contactNumber);

        $billingAgreement->setShippingAddress($shippingAddressPayPal);

        $plan = new Plan();
        $plan->setId($planId);

        $billingAgreement->setPlan($plan);

        return $billingAgreement->create($apiContext);

    }

    public function executeRecurringPayments($transaction, $cardDetails){

        $shippingAddress = $transaction->getShippingAddress();
        $billingAddress = $transaction->getBillingAddress();
        $cart = $transaction->getCart();
        $cartItems = $cart->getCartItems();
        $cartSummary = $cart->getCartSummary();
        /* The product name will be used in the billing plan / agreement titles. If there are multiple products use the name of first product. */
        $productName = $cartItems[0]->getProduct()->title;

        try {

            $apiContext = $this->getApiContext();

            /* Create billing plan */
            $billingPlan = $this->createBillingPlan($cartSummary, $productName, $transaction->id, $apiContext);

            /* Active billing plan */
            $this->activateBillingPlan($billingPlan->id, $apiContext);

            /* Get billing plan */
            $billingPlan = $this->getBillingPlan($billingPlan->id, $apiContext);


            /* Create billing agreement */
            $billingAgreement = $this->createBillingAgreement($billingPlan->id, $shippingAddress, $billingAddress, $productName, $cartSummary, $cardDetails,  $apiContext);

        /* How do I execute the billing agreement here? */


        } catch (PayPalConnectionException $pce) {
            $this->handelException($pce);
        }

    }

}