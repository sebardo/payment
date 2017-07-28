<?php
namespace PaymentBundle\Factory\Providers;

use PaymentBundle\Factory\PaymentProviderFactory;
use Braintree_Configuration;
use Symfony\Component\HttpFoundation\Request;
use PaymentBundle\Entity\Transaction;
use PaymentBundle\Entity\Delivery;
use PaymentBundle\Entity\PaymentServiceProvider;
use stdClass;

/**
 * Description of BraintreeProvider
 *
 * @author sebastian
 */
class BraintreeProvider extends PaymentProviderFactory 
{
    
    protected $amount;
    
    protected $payment_method_nonce;
    
    /**
     * Constructor with Braintree configuration
     *
     * @param string $container
     * @param PaymentServiceProvider $psp
     */
    public function initialize($container, PaymentServiceProvider $psp)
    {
        parent::initialize($container, $psp);
        if(isset($this->parameters['environment'])) Braintree_Configuration::environment($this->parameters['environment']);
        if(isset($this->parameters['merchant_id'])) Braintree_Configuration::merchantId($this->parameters['merchant_id']);
        if(isset($this->parameters['public_key'])) Braintree_Configuration::publicKey($this->parameters['public_key']);
        if(isset($this->parameters['private_key'])) Braintree_Configuration::privateKey($this->parameters['private_key']);
        
        return $this;
    }
    
    public function getAmount() {
        return $this->amount;
    }
    
    public function setAmount($amount) {
        $this->amount = $amount;
    }
    
    public function getPaymentMethodNonce() {
        return $this->payment_method_nonce;
    }
    
    public function setPaymentMethodNonce($payment_method_nonce) {
        $this->payment_method_nonce = $payment_method_nonce;
    }
    
    /**
     * Factory method for creating and getting Braintree services
     *
     * @param string $serviceName braintree service name
     * @param array $attributes   attribures for braintree service creation
     *
     * @return mixed
     */
    public function get($serviceName, array $attributes = array(), $methodName='factory')
    {
        $className = 'Braintree_' . ucfirst($serviceName);
        if(class_exists($className) && method_exists($className, $methodName)) {
            if($methodName=='factory') return $className::$methodName($attributes);
            else return $className::$methodName();
        } else {
            throw new InvalidServiceException('Invalid service ' . $serviceName);
        }
    }
    
    /**
     * Proccess sale transaction
     *
     * @param Request $request
     * @param Transaction $transaction
     * @param Delivery $delivery
     *
     * @return stdClass
     */
    public function process(Request $request, Transaction $transaction, Delivery $delivery)
    {
        
        // in your controller
        $transactionService = $this->get('transaction');
        $data = $request->get('braintree');
        $nonce = $data['payment_method_nonce'];

        $result = $transactionService::sale([
            'amount' => $transaction->getTotalPrice(),
            'paymentMethodNonce' => $nonce
        ]);
        
        
        $em = $this->container->get('doctrine')->getManager();
        $pm = $em->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('braintree');
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
            $em->persist($transaction);
            $em->flush();

            //confirmation payment
            $answer = new stdClass();
            $answer->redirectUrl = $this->container->get('router')->generate('payment_checkout_confirmationpayment');

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
            $this->container->get('session')->getFlashBag()->add('error', $errorString);
            
            $transaction->setPaymentDetails(json_encode($errorString));
            $em->persist($transaction);
            $em->flush();

            //cancel payment
            $answer = new stdClass();
            $answer->redirectUrl = $this->container->get('router')->generate('payment_checkout_cancelationpayment');

            return $answer;
        }
 
    }
    
    /**
     * Confirmation payment OK
     *
     * @param Request $request
     *
     * @return stdClass
     */
    public function confirmation(Request $request)
    {
        
    }
    
    
    /**
     * Cancelation payment OK
     *
     * @param Request $request
     *
     * @return stdClass
     */
    public function cancelation(Request $request)
    {
        
    }
        
}
