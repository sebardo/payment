<?php
namespace PaymentBundle\Factory\Providers;

use PaymentBundle\Factory\PaymentProviderFactory;
use PaymentBundle\Entity\PaymentServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use PaymentBundle\Entity\Transaction;
use PaymentBundle\Entity\Delivery;
use stdClass;


class BankTransferProvider  extends PaymentProviderFactory 
{

    protected $bankTransfer;
    
    /**
     * Constructor with Paypal configuration
     *
     * @param string $container
     * @param PaymentServiceProvider $psp
     */
    public function initialize($container, PaymentServiceProvider $psp)
    {
        parent::initialize($container, $psp);
        
        return $this;
    }
   
    public function getBankTransfer() {
        return $this->bankTransfer;
    }
    
    public function setBankTransfer($bankTransfer) {
        $this->bankTransfer = $bankTransfer;
    }
        
    /**
     * Process payment OK
     *
     * @param Request $request
     *
     * @return stdClass
     */
    public function process(Request $request, Transaction $transaction, Delivery $delivery)
    {
        //UPDATE TRANSACTION
        $em = $this->container->get('doctrine')->getManager();
        $pm = $em->getRepository('PaymentBundle:PaymentMethod')->findOneBySlug('bank-transfer-test');
        $transaction->setStatus(Transaction::STATUS_PAID);
        $transaction->setPaymentMethod($pm);
        $em->persist($transaction);
        $em->flush();
        //confirmation payment
        $answer = new stdClass();
        $answer->redirectUrl = $this->container->get('router')->generate('payment_checkout_confirmationpayment');

        return $answer;
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
