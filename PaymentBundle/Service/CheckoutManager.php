<?php
namespace PaymentBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Doctrine\Common\Collections\ArrayCollection;
use PaymentBundle\Entity\ProductPurchase;
use PaymentBundle\Entity\Cart;
use PaymentBundle\Entity\Delivery;
use PaymentBundle\Entity\Transaction;
use CoreBundle\Entity\Actor;
use PaymentBundle\Entity\Address;
use PaymentBundle\Entity\Invoice;
use PaymentBundle\Entity\CartItem;
use PaymentBundle\Entity\Plan;
use PaymentBundle\Entity\Agreement;
use PaymentBundle\Entity\Product;
use PaymentBundle\Entity\Advert;
use PaymentBundle\Factory\PaymentProvidresolveer;
use stdClass;
use DateTime;

/**
 * Class CheckoutManager
 */
class CheckoutManager
{
    

    private $session;
    
    private $manager;
    
    private $cartProvider;
    
    private $securityContext;
    
    private $router;
    
    private $kernel;
    
    private $environment;

    public  $token;
    
    public $mailer;
            
    public $specialPercentageCharge;
    
    public $deliveryExpensesType;
    
    public $deliveryExpensesPercentage;
    
    public $vat;
    
    public $paymentManager;
    

    /**
     * @param array $parameters
     */
    public function __construct(
            $session, 
            $manager, 
            $cartProvider, 
            $securityContext,
            $router,
            $kernel,
            $mailer,
            $specialPercentageCharge,
            $deliveryExpensesType,
            $deliveryExpensesPercentage,
            $vat,
            $paymentManager
            )
    {
        $this->session = $session;
        $this->manager = $manager;
        $this->cartProvider = $cartProvider;
        $this->securityContext = $securityContext;
        $this->router = $router;
        $this->kernel = $kernel;
        $this->environment = $kernel->getEnvironment();
        $this->mailer = $mailer;   
        $this->specialPercentageCharge = $specialPercentageCharge;
        $this->deliveryExpensesType = $deliveryExpensesType;
        $this->deliveryExpensesPercentage = $deliveryExpensesPercentage;
        $this->vat = $vat;
        $this->paymentManager = $paymentManager;
    }
    
     /**
     * Calculate totals
     *
     * @param Order    $transaction
     * @param Delivery $delivery
     *
     * @return array
     */
    public function calculateTotals(Transaction $transaction, $delivery=null)
    {
        
        if($transaction->getPaymentMethod() == Transaction::PAYMENT_METHOD_STORE_PICKUP){
            $totals['amount'] = $transaction->getTotalPrice();
            $totals['amount_clean'] = $transaction->getTotalPrice();
            $totals['delivery_expenses'] = 0;
            $totals['vat'] = 0;
            // return total
            return $totals;
        }
        $totals['delivery_expenses'] = 0;
        $totals['amount'] = $transaction->getTotalPrice();
        $totals['amount_clean'] = $transaction->getTotalPrice();
        $totals['vat'] = number_format($totals['amount_clean'] * $this->vat, 2);
        
        if(!is_null($delivery)){
            $totalPerDeliveryExpenses = $this->calculateTotalAmountForDeliveryExpenses($transaction);

            // calculate delivery expenses
            if ('by_percentage' === $this->deliveryExpensesType) {
                $totals['delivery_expenses'] = round($totalPerDeliveryExpenses * ($delivery->getExpenses() / 100),2);
            } else {
                $totals['delivery_expenses'] = $delivery->getExpenses();
            }
        }
        
        // calculate vat
        if(!is_null($transaction->getVat())){
            $vat = $transaction->getVat();
            $totals['vat'] = ($totals['amount'] + $totals['delivery_expenses']) * ($vat / 100);
        }
        // calculate amount
        $totals['amount'] += $totals['delivery_expenses'] + $totals['vat'];
        // return total
        return $totals;
    }
    
   
    /**
     * Calculate total amount for delivery expenses
     *
     * @param Transaction    $transaction
     *
     * @return float $total
     */
    private function calculateTotalAmountForDeliveryExpenses(Transaction $transaction)
    {
        $total = 0;
        foreach ($transaction->getItems() as $productPurchase) {
            if($productPurchase->getProduct() instanceof Product){
                if(!$productPurchase->getProduct()->isFreeTransport()){
                    $addPercent = 0;
                    if($this->deliveryExpensesPercentage > 0) 
                        $addPercent = $productPurchase->getTotalPrice() * $this->deliveryExpensesPercentage;
                    
                    $total += $productPurchase->getTotalPrice() + $addPercent;
                }
            }else{
                $total += $productPurchase->getTotalPrice();
            }
        }
        return $total;
    }
    
    /**
     * Get current transaction
     *
     * @throws AccessDeniedHttpException
     * @return Transaction
     */
    public function getCurrentTransaction()
    {
        if (false === $this->session->has('transaction-id')) {
            throw new AccessDeniedHttpException();
        }

        return $this->manager->getRepository('PaymentBundle:Transaction')->find($this->session->get('transaction-id'));
    }
    
    /**
     * Update transaction with cart's contents
     */
    public function updateTransaction()
    {        
        $cart = $this->cartProvider->getCart();

        if (0 === $cart->countItems() || $this->isTransactionUpdated($cart)) {
            return;
        }

        /** @var TransactionRepository $transactionRepository */
        $transactionRepository = $this->manager->getRepository('PaymentBundle:Transaction');

        if ($this->session->has('transaction-id')) {
            /** @var Transaction $transaction */
            $transaction = $transactionRepository->find($this->session->get('transaction-id'));

            $transactionRepository->removeItems($transaction);
        } else {
            $transactionKey = $transactionRepository->getNextNumber();

            // create a new transaction
            $transaction = new Transaction();
            $transaction->setTransactionKey($transactionKey);
            $transaction->setStatus(Transaction::STATUS_CREATED);
            $transaction->setActor($this->securityContext->getToken()->getUser());
            $cartItem = $cart->getItems()->first();
            $product = $cartItem->getProduct();
        }

        $orderTotalPrice = 0;

        foreach ($cart->getItems() as $cartItem) {
            /** @var Product $product */
            $product = $cartItem->getProduct();

            $productPurchase = new ProductPurchase();
            $productPurchase->setProduct($product);
            $productPurchase->setBasePrice($cartItem->getUnitPrice());
            $productPurchase->setQuantity($cartItem->getQuantity());
            $productPurchase->setDiscount($product->getDiscount());
            $productPurchase->setTotalPrice($cartItem->getTotal());
            $productPurchase->setTransaction($transaction);
            $productPurchase->setReturned(false);
            //free transport
            if($cartItem->isFreeTransport()){
                $productPurchase->setDeliveryExpenses(0);
            }else{ 
                $productPurchase->setDeliveryExpenses($cartItem->getShippingCost());
            }
            $orderTotalPrice += $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();

            $this->manager->persist($productPurchase);
        }

        $transaction->setTotalPrice($orderTotalPrice);

        $this->manager->persist($transaction);
        $this->manager->flush();

        $this->session->set('transaction-id', $transaction->getId());
        $this->session->save();
        
    }
    
    public function createAdvertTransaction($advert, $unitPrice, $quantity, $discount, $subtotal, $totalPrice) 
    {
        /** @var TransactionRepository $transactionRepository */
        $transactionRepository = $this->manager->getRepository('PaymentBundle:Transaction');

        // create a new transaction
        $transactionKey = $transactionRepository->getNextNumber();
        $transaction = new Transaction();
        $transaction->setTransactionKey($transactionKey);
        $transaction->setStatus(Transaction::STATUS_CREATED);
        if($advert->getActor() instanceof Actor) $transaction->setActor($advert->getActor());
        $transaction->setVat(21);
        $transaction->setTotalPrice($totalPrice);
 
        // create a new productpurchase
        $productPurchase = new ProductPurchase();
        $productPurchase->setAdvert($advert);
        $productPurchase->setBasePrice($unitPrice);
        $productPurchase->setQuantity($quantity);
        $productPurchase->setDiscount($discount);
        $productPurchase->setTotalPrice($subtotal);
        $productPurchase->setTransaction($transaction);
        $productPurchase->setReturned(false);
        $productPurchase->setDeliveryExpenses(0);
        
        $this->manager->persist($productPurchase);
        $transaction->addItem($productPurchase);
        $this->manager->persist($transaction);
        $this->manager->flush();
        
        $this->createInvoice(null, $transaction);
        
        return $transaction;
    }
    
    public function createAdvertTransactionFront($advert, $unitPrice, $quantity, $discount, $subtotal, $totalPrice) 
    {
        /** @var TransactionRepository $transactionRepository */
        $transactionRepository = $this->manager->getRepository('PaymentBundle:Transaction');

        // create a new transaction
        $transactionKey = $transactionRepository->getNextNumber();
        $transaction = new Transaction();
        $transaction->setAdvert($advert);
        $transaction->setTransactionKey($transactionKey);
        $transaction->setStatus(Transaction::STATUS_CREATED);
        $transaction->setActor($this->securityContext->getToken()->getUser());
        $transaction->setVat(21);
        $transaction->setTotalPrice($totalPrice);
 
        // create a new productpurchase
        $productPurchase = new ProductPurchase();
        $productPurchase->setAdvert($advert);
        $productPurchase->setBasePrice($unitPrice);
        $productPurchase->setQuantity($quantity);
        $productPurchase->setDiscount($discount);
        $productPurchase->setTotalPrice($subtotal);
        $productPurchase->setTransaction($transaction);
        $productPurchase->setReturned(false);
        $productPurchase->setDeliveryExpenses(0);
        
        $this->manager->persist($productPurchase);
        $transaction->addItem($productPurchase);
        $this->manager->persist($transaction);
        $this->manager->flush();
        
        $this->createInvoice(null, $transaction);
        
        return $transaction;
    }
    /**
     * Compare current cart with current transaction
     *
     * @param CartInterface $cart
     *
     * @return boolean
     */
    private function isTransactionUpdated(Cart $cart)
    {
        if (false === $this->session->has('transaction-id')) {
            return false;
        }

        /** @var TransactionRepository $transactionRepository */
        $transactionRepository = $this->manager->getRepository('PaymentBundle:Transaction');

        /** @var Order $order */
        $transaction = $transactionRepository->find($this->session->get('transaction-id'));

        /** @var ArrayCollection $cartItems */
        $cartItems = $cart->getItems();
        /** @var ArrayCollection $orderItems */
        $productPurchases = $transaction->getItems();

        if ($cartItems->count() !== $productPurchases->count()) {
            return false;
        }

        for ($i=0; $i<$cartItems->count(); $i++) {
            /** @var CartItem $cartItem */
            $cartItem = $cartItems[$i];
            /** @var OrderItem $orderItem */
            $productPurchase = $productPurchases[$i];

            if ($cartItem->getProduct()->getId() !== $productPurchase->getProduct()->getId() ||
                $cartItem->getQuantity() !== $productPurchase->getQuantity()) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Build a delivery object for the current actor
     *
     * @param Transaction $transaction
     *
     * @return Delivery
     */
    public function getDelivery(Transaction $transaction = null)
    {
        if ($this->session->has('delivery-id')) {
            $delivery = $this->manager->getRepository('PaymentBundle:Delivery')->find($this->session->get('delivery-id'));

            return $delivery;
        }

        $delivery = new Delivery();

        /** @var Address $billingAddress */
        $billingAddress = $this->manager->getRepository('PaymentBundle:Address')->findOneBy(array(
            'actor' => $this->securityContext->getToken()->getUser(),
            'forBilling' => true
        ));

        if (false === is_null($billingAddress)) {
            $delivery->setFullName($this->securityContext->getToken()->getUser()->getFullName());
            $delivery->setContactPerson($billingAddress->getContactPerson());
            $delivery->setDni($billingAddress->getDni());
            $delivery->setAddressInfo($billingAddress->getAddressInfo());
            $delivery->setPhone($billingAddress->getPhone());
            $delivery->setPhone2($billingAddress->getPhone2());
            $delivery->setPreferredSchedule($billingAddress->getPreferredSchedule());
        }

        $country = $this->manager->getRepository('CoreBundle:Country')->find('es');
        $delivery->setCountry($country);

        if (false === is_null($transaction)) {
            $delivery->setTransaction($transaction);
        }

        return $delivery;
    }
    
    /**
     * Save delivery fields from billing fields
     *
     * @param Delivery $delivery
     * @param array    $params
     */
    public function saveDelivery(Delivery $delivery, array $params, $cart)
    {
        /** @var Carrier $carrier */
//        $carrier = $this->manager->getRepository('ModelBundle:Carrier')->find($delivery->getCarrier());

        if ('same' === $params['selectDelivery']) {
            $delivery->setDeliveryContactPerson($delivery->getContactPerson());
            $delivery->setDeliveryDni($delivery->getDni());
            $delivery->setDeliveryAddressInfo($delivery->getAddressInfo());
            $delivery->setDeliveryPhone($delivery->getPhone());
            $delivery->setDeliveryPhone2($delivery->getPhone2());
            $delivery->setDeliveryPreferredSchedule($delivery->getPreferredSchedule());
        } else if ('existing' === $params['selectDelivery']) {
            /** @var Address $address */
            $address = $this->manager->getRepository('PaymentBundle:Address')->find($params['existingDeliveryAddress']);

            $delivery->setDeliveryContactPerson($address->getContactPerson());
            $delivery->setDeliveryDni($address->getDni());
            $delivery->setDeliveryAddressInfo($address->getAddressInfo());
            $delivery->setDeliveryPhone($address->getPhone());
            $delivery->setDeliveryPhone2($address->getPhone2());
            $delivery->setDeliveryPreferredSchedule($address->getPreferredSchedule());
            
        } else if ('new' === $params['selectDelivery']) {
            $this->addUserDeliveryAddress($delivery);
        }

        $deliveryCountry = $this->manager->getRepository('CoreBundle:Country')->find('es');
        $delivery->setDeliveryCountry($deliveryCountry);

         
        $total = 0;
        $productPurchases = $this->manager->getRepository('PaymentBundle:ProductPurchase')->findByTransaction($delivery->getTransaction());
        foreach ($productPurchases as $item) {
            if($item->getDeliveryExpenses()>0)
            $total = $total + $item->getDeliveryExpenses();
        }
        $delivery->setExpenses($total);
        if($total>0) $delivery->setExpensesType('store_pickup');
        else $delivery->setExpensesType('send');

        
        $this->saveUserBillingAddress($delivery);
        
      
              
        $this->manager->persist($delivery);
        $this->manager->flush();

        $this->session->set('delivery-id', $delivery->getId());
        $this->session->set('select-delivery', $params['selectDelivery']);
        if ('existing' === $params['selectDelivery']) {
            $this->session->set('existing-delivery-address', intval($params['existingDeliveryAddress']));
        } else {
            $this->session->remove('existing-delivery-address');
        }

        $this->session->save();
    }
    
    /**
     * Save user billing address
     *
     * @param Delivery $delivery
     */
    private function saveUserBillingAddress($delivery)
    {
                    
        
        // get billing address
        /** @var Address $billingAddress */
        $billingAddress = $this->manager->getRepository('PaymentBundle:Address')->findOneBy(array(
            'actor' => $this->securityContext->getToken()->getUser(),
            'forBilling' => true
        ));

        
            
        // build new billing address when it does not exist
        if (is_null($billingAddress)) {
            $billingAddress = new Address();
            $billingAddress->setForBilling(true);
            $billingAddress->setActor($this->securityContext->getToken()->getUser());
        }

        
            
        $billingAddress->setContactPerson($delivery->getContactPerson());
        $billingAddress->setDni($delivery->getDni());
        $billingAddress->setAddressInfo($delivery->getAddressInfo());
        $billingAddress->setPhone($delivery->getPhone());
        $billingAddress->setPhone2($delivery->getPhone2());
        $billingAddress->setPreferredSchedule($delivery->getPreferredSchedule());
        $country = $this->manager->getRepository('CoreBundle:Country')->find('es');
        $billingAddress->setCountry($country);

        $this->manager->persist($billingAddress);
        $this->manager->flush();
      
    }
    
    /**
     * Check if a delivery ID is saved in session
     *
     * @return bool
     */
    public function isDeliverySaved()
    {
        return $this->session->has('delivery-id');
    }
    
    public function getRedirectUrlInvoice($delivery=null)
    {
        $invoice = $this->createInvoice($delivery);
        $this->cleanSession();

        $this->session->getFlashBag()->add(
            'success',
            'transaction.success'
        );

        return $this->router->generate('payment_checkout_showinvoice', array('number' => $invoice->getInvoiceNumber()));
    }
    
     /**
     * Create invoice from order
     *
     * @param Delivery $delivery
     *
     * @return Invoice
     */
    public function createInvoice($delivery=null, $transaction=null)
    {
        /** @var Transaction $transaction */
        if(is_null($transaction))
        $transaction = $this->getCurrentTransaction();
        
        
        $invoiceNumber = $this->manager->getRepository('PaymentBundle:Invoice')->getNextNumber();

        $invoice = new Invoice();
        $invoice->setInvoiceNumber($invoiceNumber);
        //Actor invoices  = ONE SHOT
        
        if(is_object($transaction->getItems()->first()->getProduct())){
            $invoice->setFullName($this->securityContext->getToken()->getUser()->getFullName());
            if(!is_null($delivery)){
               $invoice->setDni($delivery->getDni());
               $invoice->setAddressInfo($delivery->getAddressInfo()); 
            }else{
                $invoice->setAddressInfo($this->getBillingAddress($transaction->getActor()));
            }
            
            $invoice->setTransaction($transaction);
            $this->manager->persist($invoice);
            $this->manager->flush();

            $totals = $this->calculateTotals($transaction, $delivery);
            $this->mailer->sendPurchaseNotification($invoice);
            $this->mailer->sendPurchaseConfirmationMessage($invoice, $totals['amount']);
            
        
        }
       

        return $invoice;
    }

    /**
     * Clean checkout parameters from session and void the shopping cart
     */
    public function cleanSession()
    {
        // remove checkout session parameters
        $this->session->remove('select-delivery');
        $this->session->remove('delivery-id');
        $this->session->remove('existing-delivery-address');
        $this->session->remove('transaction-id');

        // abandon cart
        $this->cartProvider->abandonCart();
    }
    
    /**
     * Get billing address
     *
     * @return Address
     */
    public function getBillingAddress($actor=null)
    {
        if(is_null($actor)){
            $actor = $this->securityContext->getToken()->getUser();
            if (!$actor || !is_object($actor)) {
                throw new \LogicException(
                    'The getBillingAddress cannot be used without an authenticated user!'
                );
            }
        }
        /** @var Address $address */
        $address = $this->manager->getRepository('PaymentBundle:Address')
            ->findOneBy(array(
                    'actor'       => $actor,
                    'forBilling' => true
                ));

        // if it does not exist, create a new one
        if (is_null($address)) {
            $address = new Address();
            $address->setForBilling(true);
            $country = $this->manager->getRepository('CoreBundle:Country')->find('es');
            $address->setCountry($country);
            $address->setActor($actor);
        }

        return $address;
    }
    
    /**
     * Check if current user is the transaction owner
     *
     * @param Transaction $transaction
     *
     * @return boolean
     */
    public function isCurrentUserOwner(Transaction $transaction)
    {
        if($this->securityContext->getToken()->getUser()->isGranted('ROLE_ADMIN')){
            return true;
        }
        
        $currentUserId = $this->securityContext->getToken()->getUser()->getId();
        //actor owner
        if($transaction->getActor() instanceof Actor){
            if($currentUserId ==  $transaction->getActor()->getId()){
                return true;
            }elseif($currentUserId == $transaction->getItems()->first()->getProduct()->getActor()->getId()){
                return true;
            }
        }
        
        return false;
    }

    /**
     * Proccess sale transaction
     *
     * @param Transaction $transaction
     * @param Delivery $delivery
     *
     * @return stdClass
     */
    public function processBraintree(Transaction $transaction, $delivery, $request)
    {
        // in your controller
        $transactionService = $this->braintreeFactory->get('transaction');
        $nonce = $request->get('payment_method_nonce');

        $result = $transactionService::sale([
            'amount' => $transaction->getTotalPrice(),
            'paymentMethodNonce' => $nonce
        ]);

        $pm = $this->manager->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('braintree');
        if ($result->success || !is_null($result->transaction)) {
            //UPDATE TRANSACTION
            $transaction->setStatus(Transaction::STATUS_PAID);
            $transaction->setPaymentMethod($pm);
            
            //details
            $details = new stdClass();
            $details->id = $result->transaction->id;
            $details->status = $result->transaction->status;
            $details->type = $result->transaction->type;
            $details->currencyIsoCode = $result->transaction->currencyIsoCode;
            $details->amount = $result->transaction->amount;
            $details->merchantAccountId = $result->transaction->merchantAccountId;
            $details->createdAt = $result->transaction->createdAt;
            $details->updatedAt = $result->transaction->updatedAt;
            $details->customer = $result->transaction->customer;
            $details->billing = $result->transaction->billing;
            $details->shipping = $result->transaction->shipping;
            
            $transaction->setPaymentDetails(json_encode($details));
            $this->manager->persist($transaction);
            $this->manager->flush();

            //confirmation payment
            $answer = new \stdClass();
            $answer->redirectUrl = $this->router->generate('payment_checkout_confirmationpayment');

            return $answer;
       }else{
            //UPDATE TRANSACTION
            $transaction->setStatus(Transaction::STATUS_CANCELLED);
            $transaction->setPaymentMethod($pm);
            
            //details
            $errorString = "";
            foreach($result->errors->deepAll() as $error) {
               $errorString .= 'Error: ' . $error->code . ": " . $error->message . "\n";
            }
            $this->session->getFlashBag()->add('error', $errorString);
            
            $transaction->setPaymentDetails(json_encode($errorString));
            $this->manager->persist($transaction);
            $this->manager->flush();

            //cancel payment
            $answer = new \stdClass();
            $answer->redirectUrl = $this->paypalFactory->getCancelUrl();

            return $answer;
        }
 
    }
    
    /**
     * Proccess sale transaction
     *
     * @param Transaction $transaction
     * @param Delivery $delivery
     *
     * @return stdClass
     */
    public function processPaypalSale(Transaction $transaction, $delivery=null, $credtCard=null)
    {
        
        $totals = $this->calculateTotals($transaction, $delivery);
  
        $this->paypalToken();
        
        if(is_null($credtCard)){
            $paymentMehod = array("payment_method" => "paypal");
        }else{
            $paymentMehod = array(
            "payment_method" => "credit_card",
            "funding_instruments" => array(
                array(
                  "credit_card" => $credtCard
                )
              )
            );
        }
        
        $returnValues = array();
        foreach ($transaction->getItems() as $productPurchase) {
            $sub = array();
            $sub['quantity'] = $productPurchase->getQuantity();
            $sub['name'] = $productPurchase->getProduct()->getName();
            $sub['price'] = number_format($productPurchase->getProduct()->getPrice(), 2);            
            $sub['sku'] = $productPurchase->getId();
            $sub['currency'] = "EUR";
            $returnValues[] = $sub;
        }
        
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/payment';
        $payment = array(
                        'intent' => 'sale',
                        'payer' => $paymentMehod,
                        'transactions' => array (array(
                                        'amount' => array(
                                                'total' => number_format($totals['amount'], 2),
                                                'currency' => 'EUR',
                                                'details' =>  array(
                                                    "subtotal" => number_format($totals['amount_clean'], 2),
                                                    "tax" => number_format($totals['vat'], 2),
                                                    "shipping" => $totals['delivery_expenses']
                                                  )
                                                ),
                                        'description' => 'payment using a PayPal account',
                                        "item_list" => array (
                                                "items" => $returnValues
                                                ),
                                        )),
                        'redirect_urls' => array (
                                'return_url' => $this->paypalFactory->getReturnUrl(),
                                'cancel_url' => $this->paypalFactory->getCancelUrl()
                        )
                    );

        $json = json_encode($payment);
        $json_resp = $this->paypalCall('POST', $url, $json);
               
        if(!is_null($credtCard)){
            if($json_resp['state'] == 'approved'){
                 //UPDATE TRANSACTION
                $pm = $this->manager->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('paypal-direct-payment');
                $transaction->setStatus(Transaction::STATUS_PAID);
                $transaction->setPaymentMethod($pm);
                $transaction->setPaymentDetails(json_encode($json_resp));
                $this->manager->persist($transaction);
                $this->manager->flush();
                
                //confirmation payment
                $answer = new \stdClass();
                $answer->redirectUrl = $this->router->generate('payment_checkout_confirmationpayment');

                return $answer;
            }else{
                //cancel payment
                $answer = new \stdClass();
                $answer->redirectUrl = $this->paypalFactory->getCancelUrl();

                return $answer;
            }
        }else{
            //UPDATE TRANSACTION
            $pm = $this->manager->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('paypal');
            $transaction->setStatus(Transaction::STATUS_PENDING);
            $transaction->setPaymentMethod($pm);
            $transaction->setPaymentDetails(json_encode($json_resp));
            $this->manager->persist($transaction);
            $this->manager->flush();


            foreach ($json_resp['links'] as $link) {
                    if($link['rel'] == 'execute'){
                            $payment_execute_url = $link['href'];
                            $payment_execute_method = $link['method'];
                    }elseif($link['rel'] == 'approval_url'){
                            $payment_approval_url = $link['href'];
                            $payment_approval_method = $link['method'];
                    }
            }

            $answer = new \stdClass();
            $answer->redirectUrl = $payment_approval_url;

            return $answer;
        }
        
    }
    
    
    /**
     * Proccess sale transaction
     *
     * @param Transaction $transaction
     * @param Delivery $delivery
     *
     * @return stdClass
     */
    public function processPaypalSaleAdvert(Transaction $transaction, $delivery=null, $credtCard=null)
    {
        
        $totals = $this->calculateTotalsAdvert($transaction, $delivery);
  
        $this->paypalToken();
        
        if(is_null($credtCard)){
            $paymentMehod = array("payment_method" => "paypal");
        }else{
            $paymentMehod = array(
            "payment_method" => "credit_card",
            "funding_instruments" => array(
                array(
                  "credit_card" => $credtCard
                )
              )
            );
        }
        
        
        $returnValues = array();
        foreach ($transaction->getItems() as $productPurchase) {
            $sub = array();
            $sub['quantity'] = $productPurchase->getQuantity();
            if($productPurchase->getProduct() instanceof Product){
                $sub['name'] = $productPurchase->getProduct()->getName();
                $sub['price'] = number_format($productPurchase->getProduct()->getPrice(), 2);
            }elseif($productPurchase->getAdvert() instanceof Advert){
                $sub['name'] = $productPurchase->getAdvert()->getTitle();
                $sub['price'] = number_format($totals['amount_clean'] / $productPurchase->getQuantity(), 2) ;
            }
            
            
            $sub['sku'] = $productPurchase->getId();
            $sub['currency'] = "EUR";
            $returnValues[] = $sub;
        }
        
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/payment';
        $payment = array(
                        'intent' => 'sale',
                        'payer' => $paymentMehod,
                        'transactions' => array (array(
                                        'amount' => array(
                                                'total' => number_format($totals['amount'], 2),
                                                'currency' => 'EUR',
                                                'details' =>  array(
                                                    "subtotal" => number_format($totals['amount_clean'], 2),
                                                    "tax" => number_format($totals['vat'], 2),
                                                    "shipping" => $totals['delivery_expenses']
                                                  )
                                                ),
                                        'description' => 'payment using a PayPal account',
                                        "item_list" => array (
                                                "items" => $returnValues
                                                ),
                                        )),
                        'redirect_urls' => array (
                                'return_url' => $this->paypalFactory->getReturnUrl(),
                                'cancel_url' => $this->paypalFactory->getCancelUrl()
                        )
                    );

        $json = json_encode($payment);
        $json_resp = $this->paypalCall('POST', $url, $json);
        
        
    
        if(!is_null($credtCard)){
            if(isset($json_resp['state']) && $json_resp['state'] == 'approved'){
                 //UPDATE TRANSACTION
                $transaction->setStatus(Transaction::STATUS_PAID);
                $transaction->setPaymentMethod(Transaction::PAYMENT_METHOD_CREDIT_CARD);
                $transaction->setPaymentDetails(json_encode($json_resp));
                $this->manager->persist($transaction);
                $this->manager->flush();
                
                //confirmation payment
                $answer = new \stdClass();
                $answer->redirectUrl = $this->router->generate('payment_checkout_confirmationpayment');

                return $answer;
            }else{
                print_r($json_resp);die();
                //cancel payment
                $answer = new \stdClass();
                $answer->redirectUrl = $this->paypalFactory->getCancelUrl();

                return $answer;
            }
        }else{
            //UPDATE TRANSACTION
            $transaction->setStatus(Transaction::STATUS_PENDING);
            $transaction->setPaymentMethod(Transaction::PAYMENT_METHOD_PAYPAL);
            $transaction->setPaymentDetails(json_encode($json_resp));
            $this->manager->persist($transaction);
            $this->manager->flush();


            foreach ($json_resp['links'] as $link) {
                    if($link['rel'] == 'execute'){
                            $payment_execute_url = $link['href'];
                            $payment_execute_method = $link['method'];
                    }elseif($link['rel'] == 'approval_url'){
                            $payment_approval_url = $link['href'];
                            $payment_approval_method = $link['method'];
                    }
            }

            $answer = new \stdClass();
            $answer->redirectUrl = $payment_approval_url;

            return $answer;
        }
        
    }
    
    /**
     * Create PayPal token
     *
     */
    public function paypalToken($print=false){
        # Sandbox
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/oauth2/token'; 
        $postdata = 'grant_type=client_credentials';
        
        $curl = curl_init($url); 
        curl_setopt($curl, CURLOPT_POST, true); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->paypalFactory->getClientId(). ":" . $this->paypalFactory->getSecret());
        curl_setopt($curl, CURLOPT_HEADER, false); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata); 
        $response = curl_exec( $curl );
        if (empty($response)) {
            // some kind of an error happened
            die(curl_error($curl));
            curl_close($curl); // close cURL handler
        } else {
            $info = curl_getinfo($curl);
            curl_close($curl); // close cURL handler
                if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
                        echo "Received error: " . $info['http_code']. "\n";
                        echo "Raw response:".$response."\n";
                        die(curl_error($curl));
            }
        }
        // Convert the result from JSON format to a PHP array 
        $jsonResponse = json_decode( $response );
        $this->token = $jsonResponse->access_token;

    }
    
    /**
     * Proccess plan
     *
     * @param Plan $plan
     *
     * @return stdClass
     */
    public function proccessPlan(Plan $plan){
        $this->createPaypalPlan($plan);
        $this->activePaypalPlan($plan);
    }
    
    public function createPaypalPlan(Plan $plan)
    {
       
        $this->paypalToken();
        
        $planConfig = array();
        $trial = false;
        $setupFee = array(
                    "currency"=> "EUR",
                    "value"=> $plan->getSetupAmount()
                );
        if($plan->getTrialCycles() > 0) {
            $trial = true;
            $planConfig[] = array(
                "name" => "Trial Plan",
                "type" => "TRIAL",
                "frequency_interval" =>  $plan->getTrialFrequencyInterval(),
                "frequency" => $plan->getTrialFrequency(),
                "cycles" => $plan->getTrialCycles(),
                "amount" => array(
                    "currency" => "EUR",
                    "value" => $plan->getTrialAmount()
                ),
                "charge_models" => array(array(
                        "type" => "TAX",
                        "amount" => array(
                            "currency" => "EUR",
                            "value" => "0"
                        )), array(
                        "type" => "SHIPPING",
                        "amount" => array(
                            "currency" => "EUR",
                            "value" => "0"
                        )))
            );
        }
        
         $planConfig[] = array(
                "name" => "Standard Plan",
                "type" => "REGULAR",
                "frequency_interval" =>  $plan->getFrequencyInterval(),
                "frequency" => $plan->getFrequency(),
                "cycles" => $plan->getCycles(),
                "amount" => array(
                    "currency" => "EUR",
                    "value" => $plan->getAmount()
                ),
                "charge_models" => array(array(
                        "type" => "TAX",
                        "amount" => array(
                            "currency" => "EUR",
                            "value" => "0"
                        )), array(
                        "type" => "SHIPPING",
                        "amount" => array(
                            "currency" => "EUR",
                            "value" => "0"
                        )))
            );
         
        
        $planPayPal = array(
                        "name" => $plan->getName(),
                        "description" => $plan->getDescription(),
                        "type" => "fixed",
                        "payment_definitions" => $planConfig,
                        "merchant_preferences" => array(
                                "setup_fee" => $setupFee,
                                "return_url" => $this->paypalFactory->getReturnUrl(),
                                "cancel_url" => $this->paypalFactory->getCancelUrl(),
                                "max_fail_attempts" => "0",
                                "auto_bill_amount" => "YES",
                                "initial_fail_amount_action" => "CONTINUE"
                            )
                        );
    
 
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-plans';
      
        $json = json_encode($planPayPal);
        $answer = $this->paypalCall('POST', $url, $json);
        
        if(isset($answer['id']) && isset($answer['state']) && $answer['state'] == 'CREATED'){
            $plan->setPaypalId($answer['id']);
            $plan->setState($answer['state']);
            $this->manager->persist($plan);
            $this->manager->flush();
        }
        return $answer;
    }

    public function activePaypalPlan(Plan $plan)
    {
        
        $this->paypalToken();
      
 
        $data ='[
                {
                    "op": "replace",
                    "path": "/",
                    "value": {
                        "state": "ACTIVE"
                    }
                }
            ]';

 
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-plans/'.$plan->getPaypalId();
        $answer = $this->paypalCall('PATCH', $url, $data);
        
        $payPalPlan = $this->getPaypalPlan($plan);
         
        
        if(isset($payPalPlan['state']) && $payPalPlan['state'] == 'ACTIVE'){
            $plan->setState($payPalPlan['state']);
            $plan->setActive(true);
            $this->manager->flush();
        }
        
        return $payPalPlan;
    }
    
    public function getPaypalPlan(Plan $plan)
    {
        
        $this->paypalToken();
      
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-plans/'.$plan->getPaypalId();
        
        $answer = $this->paypalCall('GET', $url);
                
        return $answer;
    }
    
    
    public function createPaypalAgreement(Agreement $agreement, $credtCard=null)
    {
        $this->paypalToken();
      
        $paymentMehod = array();
        if($agreement->getPaymentMethod() == 'paypal') {
            $paymentMehod = array("payment_method" => "paypal");
        }elseif($agreement->getPaymentMethod() == 'credit_card') {
            if(is_null($credtCard)) throw new \Exception('credit card values must be send');
            $paymentMehod = array(
            "payment_method" => "credit_card",
            "funding_instruments" => array(
                array(
                  "credit_card" => $credtCard
                )
              )
            );
        }
        $agreementPayPal = array(
                "name" => $agreement->getName(),
                "description" => $agreement->getDescription(),
                "start_date" => date('Y-m-d')."T".date('H:i:s')."Z",
                "plan"=> array(
                    "id" => $agreement->getPlan()->getPaypalId()
                ),
                "payer" => $paymentMehod
            );
       
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements';
        $json = json_encode($agreementPayPal);
        $answer = $this->paypalCall('POST', $url, $json);

        /**
         * The payment are efective at final of cycle of service
         * so this "status" is just for agreement status
         * any transaction must be created except setup_fee setted in the plan
         **/
        
        //if some error in response
        if(isset($answer['status']) && $answer['status'] == 'error'){
            $agreement->setStatus($answer['status'].':'.  json_encode($answer));
        }elseif($agreement->getPaymentMethod() == 'paypal'){
            if($this->hasApprovalLink($answer)){
                //STATUS_PENDING_APPROVAL
                $agreement->setStatus($answer['state']);
            }else{
                //put status pending if paypal payment methods to redirect to approve
                $agreement->setStatus($answer['state'].'-pending');
            }
        }elseif($agreement->getPaymentMethod() == 'credit_card'){
            if($answer['state'] == 'Active'){
                 //we need to move this worlflow transaction
                $agreement->setStatus($answer['state']);
                $agreement->setPaypalId($answer['id']);
                $agreement->setOutstandingAmount($answer['agreement_details']['outstanding_balance']['value']);
                $agreement->setCyclesRemaining($answer['agreement_details']['cycles_remaining']);
                $agreement->setNextBillingDate($answer['agreement_details']['next_billing_date']);
                $agreement->setFinalPaymentDate($answer['agreement_details']['final_payment_date']);
                $agreement->setFailedPaymentCount($answer['agreement_details']['failed_payment_count']);
            }
        }
        $this->manager->flush();
        $this->session->set('agreement', json_encode($answer));
        $this->session->save();
      
        //If setupAmount is diferent than 0
        //get first completed transaction agreement
        //and create transaction
        if($agreement->getPlan()->getSetupAmount() != 0){
            $transactions = $this->searchPaypalAgreementTransactions($agreement);
               foreach ($transactions['agreement_transaction_list'] as $transaction) {
                //Created: this is the first paypal transacttion
                //Expired: this is the last paypal transacttion
                //Failed: when transaction not billing
                if($transaction['status'] == 'Completed'){
                    $transaction = $this->createSale(
                            $agreement, 
                            $transaction['amount']['value'], 
                            $transaction['fee_amount']['value'],
                            $transaction['net_amount']['value'],
                            json_encode($transaction)
                            );
                }
            }
        }
        
        
        return $answer;
    }
 
    public function hasApprovalLink($answer) {
        if(isset($answer['links'])) return true;
        return false;
    }
    
    public function createSale($agreement, $amount, $feeAmount, $netAmount, $details) 
    {
        
        $transaction = new Transaction();
        $transaction->setTransactionKey(uniqid());
        $transaction->setStatus(Transaction::STATUS_PAID);
        $transaction->setTotalPrice($netAmount);
        $transaction->setTax(abs($feeAmount));
        $transaction->setPaymentMethod($agreement->getPaymentMethod());
        $transaction->setPaymentDetails($details);
        $transaction->setActor($agreement->getContract()->getActor());
        $agreement->addTransaction($transaction);

        $productPurchase = new ProductPurchase();
        $productPurchase->setPlan($agreement->getPlan());//this relation exist in productPurchase-transaction-agreement-plan
        $productPurchase->setQuantity(1);
        $productPurchase->setBasePrice($amount);
        $productPurchase->setTotalPrice($amount);
        $productPurchase->setTransaction($transaction);
        $productPurchase->setCreated(new \DateTime('now'));
        $productPurchase->setReturned(false);
        $this->manager->persist($productPurchase);
        $transaction->addItem($productPurchase);
        $this->manager->persist($transaction);
        $this->manager->flush();
        
        $this->createInvoice(null, $transaction);
        
    }
    
    public function getPaypalAgreement($id) {
        
        $this->paypalToken();
      
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$id;
        
        $answer = $this->paypalCall('GET', $url);
                
        return $answer;
                
    }
    public function cancelPaypalAgreement($agreement) {
         
        $this->paypalToken();
      
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$agreement->getPaypalId().'/cancel';
        
        $answer = $this->paypalCall('POST', $url, '{ "note" : "Cancel the agreement."}');
                
        return $answer;
    }
    
    public function suspendPaypalAgreement($agreement) {
         
        $this->paypalToken();
       
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$agreement->getPaypalId().'/suspend';
        
        $answer = $this->paypalCall('POST', $url, '{ "note" : "Suspending the agreement."}');
                
        return $answer;
    }
    
    public function reactivePaypalAgreement($agreement) {
         
        $this->paypalToken();
      
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$agreement->getPaypalId().'/re-activate';
        
        $answer = $this->paypalCall('POST', $url, '{ "note" : "Reactivating the agreement."}');;
                
        return $answer;
    }
    
    public function setOutstandingPaypalAgreement($agreement, $amount) {
         
        $this->paypalToken();
       
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$agreement->getPaypalId().'/set-balance';
        
        $answer = $this->paypalCall('POST', $url, '{ "value": "'.$amount.'", "currency": "EUR"}');

        return $answer;
    }
    
    public function billOutstandingPaypalAgreement($agreement, $amount) {
         
        $this->paypalToken();
       
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$agreement->getPaypalId().'/bill-balance';
        
        $answer = $this->paypalCall('POST', $url, '{"note": "Billing Balance Amount '.$amount.'€", "amount": {"value": "'.$amount.'", "currency": "EUR"} }');
               
        return $answer;
    }
    
    public function searchPaypalAgreementTransactions(Agreement $agreement) {
         
        $this->paypalToken();
      
        $startDate = $agreement->getCreated();
        $startDate->modify('-1 day');
        $endDate =  new DateTime('now');
        $endDate->modify('+1 day');
        $host = $this->paypalFactory->getHost();
        $url = $host.'/v1/payments/billing-agreements/'.$agreement->getPaypalId().'/transactions?start_date='.$startDate->format('Y-m-d').'&end_date='.$endDate->format('Y-m-d');
        $answer = $this->paypalCall('GET', $url);
                
        return $answer;
    }
    
    public function paypalCall($method, $url, $postdata=null) 
    {
        if($method == 'GET')
        {
            $curl = curl_init($url); 
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                                    'Authorization: Bearer '.$this->token,
                                    'Accept: application/json',
                                    'Content-Type: application/json'
                                    ));

            #curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
            $response = curl_exec( $curl );
            if (empty($response)) {
                // some kind of an error happened
                curl_close($curl); // close cURL handler
                return array(
                        'status' => 'error',
                        'error' => curl_error($curl)
                    );
            } else {
                
                $info = curl_getinfo($curl);
                curl_close($curl); // close cURL handler
                if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
                    return array(
                        'status' => 'error',
                        'error' => json_decode($response, TRUE)
                    );

                }
            }
            // Convert the result from JSON format to a PHP array 
            $jsonResponse = json_decode($response, TRUE);
            return $jsonResponse;
        }
        elseif($method == 'POST')
        {
            $curl = curl_init(); 
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                                    'Authorization: Bearer '.$this->token,
                                    'Accept: application/json',
                                    'Content-Type: application/json'
                                    ));

            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata); 
            #curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
            $response = curl_exec( $curl );
            if (empty($response)) {
                // some kind of an error happened
//                curl_close($curl); // close cURL handler
                return array(
                        'status' => 'error',
                        'error' => curl_error($curl)
                    );
            } else {
                $info = curl_getinfo($curl);
//                var_dump($response);die();
                curl_close($curl); // close cURL handler
                if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
                    return array(
                        'status' => 'error',
                        'error' => $response,
                    );
                }
            }
            // Convert the result from JSON format to a PHP array 
            $jsonResponse = json_decode($response, TRUE);
            return $jsonResponse;
        }
        elseif($method == 'PATCH')
        {
            $curl = curl_init(); 
            //set connection properties
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER,array(
                                    'Authorization: Bearer '.$this->token,
                                    'Content-Type: application/json'
                                    ));
            //curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
 
            #curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
            $response = curl_exec( $curl );
            if (empty($response)) {
                // some kind of an error happened
                die(curl_error($curl));
                curl_close($curl); // close cURL handler
            } else {
                $info = curl_getinfo($curl);
                curl_close($curl); // close cURL handler
                if($info['http_code'] != 200 && $info['http_code'] != 201 ) {
                    return array(
                        'status' => 'error',
                        'error' => json_decode($response, TRUE)
                    );
                }
            }
            // Convert the result from JSON format to a PHP array 
            $jsonResponse = json_decode($response, TRUE);
            return $jsonResponse;
        }
    }
    
    /**
     * Process a bank transfer request
     *
     * @param Transaction $transaction
     */
    public function processBankTransfer(Transaction $transaction)
    {
        $transaction->setStatus(Transaction::STATUS_PENDING_TRANSFER);
        $pm = $this->manager->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('bank-transfer-test');
        $transaction->setPaymentMethod($pm);

        $this->manager->persist($transaction);
        $this->manager->flush();
        
        return true;
    }

    /**
     * Process a redsys transaction
     *
     * @param int                   $ds_response
     * @param Transaction                 $transaction
     *
     * @return boolean
     */
    public function processRedsysTransaction($ds_response, Transaction $transaction)
    {
        if ($ds_response > 99) {
            return false;
        }

        $transaction->setStatus(Transaction::STATUS_PAID);
        $pm = $this->manager->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('redsys');
        $transaction->setPaymentMethod($pm);

        $this->manager->persist($transaction);
        $this->manager->flush();

        return true;
    }
    
    /**
     * @param CartItemInterface $item
     * @param Request           $request
     *
     * @throws ItemResolvingException
     * @return CartItemInterface|void
     */
    public function resolve(CartItem $item, Request $request)
    {
        
        $productId = $request->query->get('id');
        $itemForm = $request->request->get('cart_item_simple');

        $productRepository = $this->paymentManager->getRepositoryProduct();
        if (!$productId || !$product = $productRepository->find($productId)) {
            // no product id given, or product not found
            throw new \Exception('Requested product was not found');
        }

        // assign the product and quantity to the item
        $item->setProduct($product);
        $item->setQuantity(intval($itemForm['quantity']));

        // calculate item price adding the special charge
        $price = $product->getPrice();
        if ($this->specialPercentageCharge > 0) {
            $price += $price * ($this->specialPercentageCharge / 100);
        }
        $item->setUnitPrice(intval($price));

        return $item;
    }

    
}