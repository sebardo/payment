<?php

namespace PaymentBundle\Controller;

use CoreBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use PaymentBundle\Form\DeliveryType;
use CoreBundle\Form\Model\Registration;
use PaymentBundle\Entity\Address;
use PaymentBundle\Entity\Transaction;
use PaymentBundle\Entity\CartItem;
use PaymentBundle\Form\CartType;
use PaymentBundle\Entity\CreditCardForm;
use PaymentBundle\Form\CreditCardType;
use PaymentBundle\Entity\Contract;
use PaymentBundle\Entity\Agreement;
use CoreBundle\Entity\EmailToken;
use DateTime;

class CheckoutController extends BaseController
{

    /*********************************
     ************* CART **************
     *********************************/
    /**
     * @Route("/cart/")
     * @Template()
     */
    public function indexAction()
    {
        $manager = $this->get('payment_manager');
        $products = $manager->getRepositoryProduct()->findBy(array());
        
        return array('products' => $products);
    }

    
    /**
     * @Route("/cart/product/{slug}")
     * @Template()
     */
    public function productAction($slug)
    {
        $manager = $this->get('payment_manager');
        $product = $manager->getRepositoryProduct()->findOneBySlug(array('slug' => $slug));
        
        return array('product' => $product);
    }
    
    /**
     * Displays current cart summary page.
     * The parameters includes the form .
     * 
     * @param Request
     * @return Response
     * 
     * @Route("/cart/detail")
     * @Template("PaymentBundle:Checkout/cart:detail.html.twig")   
     */
    public function detailAction(Request $request)
    {
       
        $cart = $this->getCurrentCart();
        $form = $this->createForm('PaymentBundle\Form\CartType', $cart);

        return array(
            'cart' => $cart,
            'form' => $form->createView()
        );
    }
  
    /**
     * Adds item to cart.
     * It uses the resolver service so you can populate the new item instance
     * with proper values based on current request.
     *
     * It redirect to cart summary page by default.
     *
     * @param Request $request
     * @return Response
     * 
     * @Route("/cart/add")
     * @Template("PaymentBundle:Front:detail.html.twig")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $coreManager = $this->get('core_manager');
        $checkoutManager = $this->get('checkout_manager');
        $cart = $this->getCurrentCart();
        $emptyItem = new CartItem();
        
        try {
            $item = $checkoutManager->resolve($emptyItem, $request);
        } catch (\Exception $exception) {
            // Write flash message
            print_r($exception->getMessage());die();
            return $this->redirect($this->generateUrl('payment_checkout_detail'));
        }

        $price = $item->getProduct()->getPrice();
        $item->setUnitPrice($price);
        $freeTransport = $item->getProduct()->isFreeTransport();
        $item->setFreeTransport($freeTransport);
        //add
        $cart->addItem($item);
        //refresh
        $cart->calculateTotal();
        $cart->setTotalItems($cart->countItems());
        //save
        $em->persist($cart);
        $em->flush();
       
        // Write flash message
        $referer = $coreManager->getRefererPath($request);
        return $this->redirect($this->generateUrl('payment_checkout_detail').'?referer='.$referer);
    }
    
    /**
     * This action is used to submit the cart summary form.
     * If the form and updated cart are valid, it refreshes
     * the cart data and saves it using the operator.
     *
     * If there are any errors, it displays the cart detail page.
     *
     * @param Request $request
     * @return Response
     * 
     * @Route("/cart/save")
     * @Template("PaymentBundle:Front:detail.html.twig")
     */
    public function saveAction(Request $request)
    {
        $cart = $this->getCurrentCart();
        $form = $this->createForm(CartType::class, $cart);

        if ($form->handleRequest($request)->isValid()) {
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($cart);
            $em->flush();
            
            return $this->redirect($this->generateUrl('payment_checkout_identification'));
            
//            $event = new CartEvent($cart);
//            $event->isFresh(true);
//
//            // Update models
//            $this->dispatchEvent(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);
//
//            // Write flash message
//            $this->dispatchEvent(SyliusCartEvents::CART_SAVE_COMPLETED, new FlashEvent());
        }

        return array(
            'cart' => $cart,
            'form' => $form->createView()
        );
    }
    
    /**
     * Removes item from cart.
     * It takes an item id as an argument.
     *
     * If the item is found and the current user cart contains that item,
     * it will be removed and the cart - refreshed and saved.
     *
     * @param mixed $id
     * @return Response
     * 
     * @Route("/cart/remove/{id}")
     */
    public function removeAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $cart = $this->getCurrentCart();
        $repository = $em->getRepository('PaymentBundle:CartItem');
        $item = $repository->find($id);

        if (!$item || false === $cart->hasItem($item)) {
            // Write flash message
            return $this->redirect($this->generateUrl('payment_checkout_detail'));
        }

        // Update models
        $cart->removeItem($item);
        $cart->setTotalItems(count($cart->getItems()));
        $em->flush();
        // Write flash message

        return $this->redirect($this->generateUrl('payment_checkout_detail'));
    }

    /**
     * Returns current cart.
     *
     * @return CartInterface
     */
    public function getCurrentCart()
    {
        return $this->get('cart_provider')->getCart();
    }
    
    
    /*********************************
     ***********CHECKOUT**************
     *********************************/
    /**
     * Step 1: identification
     *
     * @return array
     *
     * @Route("/identification")
     * @Method("GET")
     * @Template
     */
    public function identificationAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('payment_checkout_deliveryinfo'));
        }

        $session = $request->getSession();
//        // get the login error if there is one
//        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
//            $error = $request->attributes->get(
//                SecurityContext::AUTHENTICATION_ERROR
//            );
//        } else {
//            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
//            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
//        }
        
        $registration = new Registration();
        $form = $this->createForm('CoreBundle\Form\RegistrationType', $registration, array('translator' => $this->get('translator')));
        
        return array(
                // last username entered by the user
//                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
//                'error'         => $error,
                'form'          => $form->createView()
            );
    }

    /**
     * Step 2: delivery info
     *
     * @param Request $request
     *
     * @return array
     *
     * @Route("/delivery-info")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function deliveryInfoAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('index'));
        }
        
        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->get('checkout_manager');
        //ere create a transaction
        $checkoutManager->updateTransaction();
        
        $transaction = $checkoutManager->getCurrentTransaction();
        $delivery = $checkoutManager->getDelivery($transaction);

        $options = array(
            'securityContext' =>  $this->get('security.token_storage'),
            'manager' => $this->get('doctrine')->getManager(), 
            'session' => $this->get('session')
        );
                
        $form = $this->createForm(DeliveryType::class, $delivery, $options);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $cart = $this->getCurrentCart();
                $this->get('checkout_manager')->saveDelivery($delivery, $request->get('delivery'), $cart);

                $url = $this->container->get('router')->generate('payment_checkout_summary');

                return new RedirectResponse($url);
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }
    
    /**
     * Step 3: summary
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     *
     * @Route("/summary")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function summaryAction(Request $request)
    {
        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->get('checkout_manager');
        if (false === $checkoutManager->isDeliverySaved()) {
            return $this->redirect($this->generateUrl('payment_checkout_deliveryinfo'));
        }

        $transaction = $checkoutManager->getCurrentTransaction();
        $delivery = $checkoutManager->getDelivery();
        $totals = $checkoutManager->calculateTotals($transaction, $delivery);

        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('payment_manager');
        $psps = $paymentManager->getProviders();
 
        // process payment method form
        if ($request->isMethod('POST')) {
            foreach ($psps as $ppf) {
                if($request->request->has($ppf->getForm()->getName())){
                    $ppf->getForm()->handleRequest($request);
                    if ($ppf->getForm()->isValid()){
                        $answer = $paymentManager->processPayment($request, $transaction, $delivery, $ppf);
                        return $this->redirect($answer->redirectUrl);
                    }
                }
            }
        }

        return array(
            'transaction'    => $transaction,
            'delivery'       => $delivery,
            'totals'         => $totals,
            'psps'           => $psps
            );
    }
    
     /**
     * @Route("/credit-card")
     * @Template()
     */
    public function creditCardAction(Request $request) {
        
        $em = $this->getDoctrine()->getManager();
        list($actor, $form, $token) = $this->createCCForm($request);

        //check if actor already hace a contract active
        if($this->hasActiveContract($actor)){
            $this->get('session')->getFlashBag()->add(
                'danger',
                'Ya posees un contracto activo por favor, cancélalo primero si quieres cambiar de plan'
            );
        }

        if($request->getMethod() == 'POST'){
            $form->handleRequest($request);
            if ($form->isValid()){
                
                //Create contract and paypal agreement 
               $answer = $this->createContract($actor, $form);
               
               if($answer['state'] == 'Active'){
                    if($token != ''){
                        $token = $em->getRepository('CoreBundle:EmailToken')->findOneByToken($token);
                        if ($token instanceof EmailToken) {
                            $em->remove($token);
                            $em->flush(); 
                        }
                    }
                   return $this->redirect($this->generateUrl('payment_checkout_confirmationpayment'));
               }else{
                   return $this->redirect($this->generateUrl('payment_checkout_cancelationpayment'));
               }
            }
        }

        return array(
            'form' => $form->createView()
                );
           
    }
    
    public function hasActiveContract(Actor $actor) {
        foreach ($actor->getContracts() as $contract) {
            if ($contract->getAgreement()->getStatus() == 'Active'){
                return true;
                
            }
        }
        return false;
    }
    
    /**
     * Creates a form to create a Post entity.
     *
     * @param Post $token string
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreditCardForm()
    {
        $class = new CreditCardForm($this->get('validator'));
        $type = new CreditCardType(array());
        
        $form = $this->createForm($type, $class, array(
            'action' => $this->generateUrl('payment_checkout_summary'),
            'method' => 'POST',
            'attr' => array('id' => 'payment-cc', 'class' => 'cc-form')
        ));
        $form->add('submit', 'submit', array('label' => 'Pagar'));
        
        return $form;
    }
    
    /**
     * Creates a form to create a Post entity.
     *
     * @param Post $token string
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCCForm(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        //Token
        $token = null;
        $promotionCode = null;
        if($request->query->get('token') != ''){
            $token = $request->query->get('token');
            $tokenEntity = $em->getRepository('CoreBundle:EmailToken')->findOneByToken($token);
            $actor =  $em->getRepository('CoreBundle:Actor')->findOneByEmail($tokenEntity->getEmail());
        }else{
            $actor = $this->get('security.token_storage')->getToken()->getUser();
            //Promotion code part
            if($request->query->get('promotionCode') != ''){
                $promotionCode = $request->query->get('promotionCode');
            }
        }
        
        //Config form
        $planConfig = array('plan' => true);
        $url = null;
        if(!is_null($token)) $url = '?token='.$token;
        if(!is_null($actor)) {
            //PromotionCode part
            if(!is_null($promotionCode)){
                 $planConfig['plan'] = $em->getRepository('PaymentBundle:Plan')->findOneByPaypalId($promotionCode);
                 if(is_null($token))
                     $url .= '?promotionCode='.$promotionCode;
                 else
                     $url .= '&promotionCode='.$promotionCode;
            }else{
                //all actor already have a plan, every pack must be a plan assocc 
                if(is_null($token))
                $planConfig['plan'] = $actor->getPack()->getPlans()->first();
            }
        }
        
        $class = new CreditCardForm($this->get('validator'));
        $type = new CreditCardType($planConfig);
        
        $form = $this->createForm($type, $class, array(
            'action' => $this->generateUrl('payment_checkout_creditcard').$url,
            'method' => 'POST',
            'attr' => array('id' => $type->getName(),'class' => 'cc-form')
        ));
        $form->add('submit', 'submit', array('label' => 'Pagar'));

        return array($actor, $form, $token);
    }
    
    public function createContract($actor, $form) 
    {
        $em = $this->getDoctrine()->getManager();
        //contract
        $contract = new Contract();
        $contract->setFinished(new DateTime('+1 year'));
        $contract->setActor($actor);
        $contract->setUrl('http://localhost/aviso-legal');
        $actor->addContract($contract);
        $em->persist($contract);

        $agree = new Agreement();
        $agree->setPlan($form->getNormData()->plan);
        $agree->setStatus('Created');
        $agree->setPaymentMethod('credit_card');
        $uid = uniqid();
        $agree->setName('Test agreement '.$uid);
        $agree->setDescription('Description of test agreement '.$uid);
        $agree->setContract($contract);
        $contract->setAgreement($agree);
        $em->persist($agree);
        $em->flush(); 

        //4548812049400004//visa//12//2017//123
        //error
        $errors = $this->get('validator')->validate($agree);
        if(count($errors)==0){
            $answer = $this->get('checkout_manager')->createPaypalAgreement($agree, array(
                "number" => $form->getNormData()->cardNo,
                "type" => $form->getNormData()->cardType,
                "expire_month" =>  $form->getNormData()->expirationDate->format('m'),
                "expire_year" =>  $form->getNormData()->expirationDate->format('Y'),
                "cvv2" =>  $form->getNormData()->CVV,
                "first_name" =>  $form->getNormData()->firstname,
                "last_name" =>  $form->getNormData()->lastname
           ));
            
            return $answer;
        }else{
            throw $this->createNotFoundException('Error: '.json_decode($errors));
        }

    }
    /**
     * 
     * @Route("/response-ok")
     * @Template("PaymentBundle:Checkout:response.ok.html.twig")
     */    
    public function confirmationPaymentAction(Request $request)
    {
        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->get('checkout_manager');
        $transaction = $checkoutManager->getCurrentTransaction();
        $psp = $transaction->getPaymentMethod()->getPaymentServiceProvider();
        
        /** @var PaymentManager $paymentManager */
        $paymentManager = $this->get('payment_manager');
        $paymentManager->confirmationPayment($request, $psp);

        /** Url invoice */
        $delivery = $checkoutManager->getDelivery();
        $urlInvoice = $checkoutManager->getRedirectUrlInvoice($delivery);
        $checkoutManager->cleanSession();

        return array('url_invoice' => $urlInvoice);
    }
   
    /**
     * @Route("/cancel-payment")
     * @Template("PaymentBundle:Checkout:cancelationPayment.html.twig")
     */    
    public function cancelationPaymentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->get('checkout_manager');
        //clean al sessionn vars
        $checkoutManager->cleanSession();
            
        if( $this->get('request')->get('token') != '' ){
            //Search on transaction paymentDetails the $paymentId
            $token = $this->get('request')->get('token');
            $transaction = $em->getRepository('PaymentBundle:Transaction')->findOnPaymentDetails($token);
            //UPDATE TRANSACTION
            if($transaction instanceof Transaction){
                $transaction->setStatus(Transaction::STATUS_CANCELLED);
                $em->flush();
            }

            $this->get('session')->getFlashBag()->add(
                'danger',
                'transaction.cancel'
            );
            
            return $this->redirect($this->generateUrl('core_profile_index', array('transactions' => true)));
            
        }
        
        return array();
    }
    
    /*
     * Profile
     */
    
     /**
     * Edit the billing address
     *
     * @param Request $request
     *
     * @Route("/profile/billing/")
     * @Method({"GET","POST"})
     * 
     */
    public function editBillingAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $em = $this->container->get('doctrine')->getManager();
            $checkoutManager = $this->container->get('checkout_manager');

            /** @var Address $address */
            $address = $checkoutManager->getBillingAddress();
            $form = $this->createForm('PaymentBundle\Form\AddressType', $address, array('token_storage' => $this->container->get('security.token_storage')));

            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $em->persist($address);
                    $em->flush();
                    $url = $this->container->get('router')->generate('core_actor_profile').'?billing=1';
                    $this->container->get('session')->getFlashBag()->add('success', 'account.address.added');
                    return new JsonResponse(array('status' => 'success', 'url' => $url));
                }else{
                    $template = $this->container->get('twig')->render("PaymentBundle:Profile:Billing/billing.form.html.twig", array(
                        'billing_form' => $form->createView(),
                        'address' => $address
                    ));
                    return new JsonResponse(array('status' => 'error', 'answer' => $template));
                }
            }
        }else {
            throw new \Exception('Only by ajax');
        }
        
    }

    /**
     * Set the address as the billing address
     *
     * @param integer $id
     *
     * @throws AccessDeniedException
     * @return RedirectResponse
     * 
     * @Route("/profile/delivery/{id}/set-for-billing")
     * @Method("GET")
     * @Template("PaymentBundle:Profile:Delivery/show.html.twig")
     */
    public function setBillingAddressAction($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $address = $em->getRepository('PaymentBundle:Address')
            ->findOneBy(array(
                    'id'   => $id,
                    'actor' => $user,
                ));
        if (is_null($address)) {
            throw new AccessDeniedException();
        }

        $em->getRepository('PaymentBundle:Address')->removeForBillingToAllAddresses($user->getId());

        $address->setForBilling(1);
//        $em->persist($address);
        $em->flush();

        $url = $this->container->get('router')->generate('core_actor_profile').'?delivery=1';

        $this->container->get('session')->getFlashBag()->add('success', 'account.address.assigned.for.billing');

        return new RedirectResponse($url);
    }

    /**
     * Show delivery addresses
     *
     * @return Response
     * 
     * @Route("/profile/delivery/")
     * @Method({"GET","POST"})
     * @Template("PaymentBundle:Profile:Delivery/show.html.twig")
     */
    public function showDeliveryAction()
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $addresses = $em->getRepository('PaymentBundle:Address')
            ->findBy(array(
                    'actor' => $user,
                ));

        return array(
                'user'      => $user,
                'addresses' => $addresses
            );
    }

    /**
     * Add delivery address
     *
     * @param Request $request
     * @return Response
     * 
     * @Route("/profile/delivery/new")
     * @Method({"GET","POST"})
     */
    public function newDeliveryAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $em = $this->container->get('doctrine')->getManager();
            $user = $this->container->get('security.token_storage')->getToken()->getUser();

            /** @var Address $address */
            $country = $em->getRepository('CoreBundle:Country')->find('es');
            $address = new Address();
            $address->setForBilling(false);
            $address->setCountry($country);
            $address->setActor($user);
            $form = $this->createForm('PaymentBundle\Form\AddressType', $address, array('token_storage' => $this->container->get('security.token_storage')));

            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $em->persist($address);
                    $em->flush();
                    $url = $this->container->get('router')->generate('core_actor_profile').'?delivery=1';
                    $this->container->get('session')->getFlashBag()->add('success', 'account.address.added');
                    return new JsonResponse(array('status' => 'success', 'url' => $url));
                }else{
                    $template = $this->container->get('twig')->render("PaymentBundle:Profile:Delivery/new.html.twig", array(
                        'delivery_form' => $form->createView(),
                        'address' => $address
                    ));
                    return new JsonResponse(array('status' => 'error', 'answer' => $template));
                }
            }
        }else {
            throw new \Exception('Only by ajax');
        }
    }

    /**
     * Edit delivery addresses
     *
     * @param Request $request
     * @param integer $id
     *
     * @throws AccessDeniedException
     * @return Response
     * 
     * @Route("/profile/delivery/{id}/edit")
     * @Method({"GET","POST"})
     */
    public function editDeliveryAction(Request $request, $id)
    {
        if($request->isXmlHttpRequest()){
            $em = $this->container->get('doctrine')->getManager();
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
        
            /** @var Address $address */
            $address = $em->getRepository('PaymentBundle:Address')->findOneBy(array(
                    'id'   => $id,
                    'actor' => $user,
                ));
            $form = $this->createForm('PaymentBundle\Form\AddressType', $address, array('token_storage' => $this->container->get('security.token_storage')));

            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $em->persist($address);
                    $em->flush();
                    $url = $this->container->get('router')->generate('core_actor_profile').'?delivery=1';
                    $this->container->get('session')->getFlashBag()->add('success', 'account.address.saved');
                    return new JsonResponse(array('status' => 'success', 'url' => $url));
                }else{
                    $template = $this->container->get('twig')->render("PaymentBundle:Profile:Delivery/edit.html.twig", array(
                        'delivery_form' => $form->createView(),
                        'address' => $address
                    ));
                    return new JsonResponse(array('status' => 'error', 'answer' => $template));
                }
            }
        }else {
            throw new \Exception('Only by ajax');
        }
    }

    /**
     * Delete delivery addresses
     *
     * @param integer $id
     *
     * @throws AccessDeniedException
     * @return RedirectResponse
     * 
     * @Route("/profile/delivery/{id}/delete")
     * @Method({"GET","POST"})
     * @Template("PaymentBundle:Profile:Delivery/edit.html.twig")
     */
    public function deleteDeliveryAction($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $address = $em->getRepository('PaymentBundle:Address')
            ->findOneBy(array(
                    'id'   => $id,
                    'actor' => $user,
                ));
        if (is_null($address)) {
            throw new AccessDeniedException();
        }

        $em->remove($address);
        $em->flush();

        $url = $this->container->get('router')->generate('core_actor_profile').'?delivery=1';
        $this->container->get('session')->getFlashBag()->add('success', 'account.address.deleted');
        return new RedirectResponse($url);
    }
    
    /**
     * Show transactions list
     *
     * @return Response
     * 
     * @Route("/profile/transaction/")
     * @Method({"GET","POST"})
     * @Template("FrontBundle:Profile:Transaction/show.html.twig")
     */
    public function showTransactionAction()
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        
        $transactions = $em->getRepository('PaymentBundle:Transaction')->findAllFinished($user);

        return array(
                    'transactions' => $transactions        
                );
    }
    
    /**
     * Show invoice
     *
     * @param Request $request
     * @param string  $number
     *
     * @throws AccessDeniedException
     * @return Response
     * 
     * @Route("/profile/invoice/{number}/view")
     * @Method("GET")
     * @Template("PaymentBundle:Profile:Invoice/show.html.twig")
     */
    public function showInvoiceAction(Request $request, $number)
    {
        
        $em = $this->container->get('doctrine')->getManager();
        /** @var Invoice $invoice */
        $invoice = $em->getRepository('PaymentBundle:Invoice')->findOneBy(array(
            'invoiceNumber' => $number
        ));

        if (!$invoice ||
            false === $this->container->get('checkout_manager')->isCurrentUserOwner($invoice->getTransaction())) {
            //throw new AccessDeniedException();
        }

        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->container->get('checkout_manager');

        $delivery = $invoice->getTransaction()->getDelivery();
        $totals = $checkoutManager->calculateTotals($invoice->getTransaction(), $delivery);

        return array(
                'delivery' => $delivery,
                'invoice'  => $invoice,
                'totals'   => $totals,
            );
    }

}
