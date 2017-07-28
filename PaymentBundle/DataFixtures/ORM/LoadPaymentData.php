<?php
namespace PaymentBundle\DataFixtures\ORM;

use CoreBundle\DataFixtures\SqlScriptFixture;
use CatalogueBundle\Entity\Product;
use PaymentBundle\Entity\Transaction;
use PaymentBundle\Entity\Invoice;
use PaymentBundle\Entity\ProductPurchase;
use PaymentBundle\Entity\Address;
use PaymentBundle\Entity\Delivery;
use PaymentBundle\Entity\PaymentMethod;
use PaymentBundle\Entity\PaymentServiceProvider;
use Gedmo\Mapping\Annotation as Gedmo;


/*
 * php app/console doctrine:fixtures:load --fixtures=src/PaymentBundle/DataFixtures/ORM/LoadPaymentData.php
 */
class LoadPaymentData extends SqlScriptFixture
{
    public function createFixtures()
    {
        
        //get dinamic product class
        $productClass = $this->container->get('core_manager')->getProductClass();

        //Create Products
        $product = new $productClass();
        $product->setName('Product test 1');
        $product->setInitPrice(1.84);
        $product->setPrice(0.84);
        $product->setPriceType($productClass::PRICE_TYPE_FIXED());
        $product->setDiscount(1);
        $product->setDiscountType($productClass::PRICE_TYPE_FIXED());
        $product->setStock(7);
        $product->setStorePickup(false);
        $product->setFreeTransport(true);
        $product->setReference(uniqid());
        $product->setActive(true);
        $product->setDescription('Description product test 1 for testing.');
        $product->setMetaTitle('Meta titlte test 1');
        $product->setMetaDescription('Meta description test 1');
        $product->setMetaTags('tags1, tags2');

        $product2 = new $productClass();
        $product2->setName('Product test 2');
        $product2->setPrice(1.14);
        $product2->setPriceType($productClass::PRICE_TYPE_FIXED());
        $product2->setDiscount(0);
        $product2->setDiscountType($productClass::PRICE_TYPE_FIXED());
        $product2->setStock(12);
        $product2->addRelatedProduct($product);
        $product2->setStorePickup(false);
        $product2->setFreeTransport(true);
        $product2->setReference(uniqid());
        $product2->setActive(true);
        $product2->setDescription('Description product test 2 for testing.');
        $product2->setMetaTitle('Meta titlte test 2');
        $product2->setMetaDescription('Meta description test 2');

        $product3 = new $productClass();
        $product3->setName('Product test 3');
        $product3->setInitPrice(10.14);
        $product3->setPrice(7.98);
        $product3->setPriceType($productClass::PRICE_TYPE_FIXED());
        $product3->setDiscount(10);
        $product3->setDiscountType($productClass::PRICE_TYPE_PERCENT());
        $product3->setStock(12);
        $product3->addRelatedProduct($product);
        $product3->addRelatedProduct($product2);
        $product3->setStorePickup(true);
        $product3->setFreeTransport(false);
        $product3->setReference(uniqid());
        $product3->setActive(true);
        $product3->setDescription('Description product test 3 for testing.');
        $product3->setMetaTitle('Meta titlte test 3');
        $product3->setMetaDescription('Meta description test 3');


        //Create a sale (create all entities needed)
        $actor = $this->getManager()->getRepository('CoreBundle:BaseActor')->findOneByUsername('user');
        $actor2 = $this->getManager()->getRepository('CoreBundle:BaseActor')->findOneByUsername('user2');
        $country = $this->getManager()->getRepository('CoreBundle:Country')->find('es');

        //payment methods
        $pm = new PaymentMethod();
        $pm->setName('Bank Transfer');
        $psp = new PaymentServiceProvider();
        $psp->setActive(true);
        $psp->setIsTestingAccount(false);
        $psp->setRecurring(false);
        $psp->setPaymentMethod($pm);
        $psp->setFormClass('PaymentBundle\Form\BankTransferType');
        $psp->setModelClass('PaymentBundle\Factory\Providers\BankTransferProvider');
        $psp->setAppendTwigToForm('<div class="col-sm-10 detail-transfer">
            {{ "checkout.transfer" | trans({"%bank_name%": twig_global.getParameter("name"), "%bank_account%": twig_global.getParameter("bank_account") }) | nl2br }}
            <br />
        </div>
        <div class="col-sm-12 priceAndCheck">
            <div class="price">
                <span>{{ "checkout.amount" | trans }}:</span>
                <strong style="margin-right: 15px;">{{ totals.amount | price }}</strong>
                <button >{{ "checkout.confirm" | trans }}</button>
            </div>
            <a href="{{ path(\'payment_checkout_deliveryinfo\') }}" class="btn btn-primary" title="{{ "back" | trans }}">{{ "back" | trans }}</a>
        </div>');

        $pm1 = new PaymentMethod();
        $pm1->setName('Bank Transfer test');
        $psp1 = new PaymentServiceProvider();
        $psp1->setActive(true);
        $psp1->setIsTestingAccount(true);
        $psp1->setRecurring(false);
        $psp1->setPaymentMethod($pm1);
        $psp1->setFormClass('PaymentBundle\Form\BankTransferType');
        $psp1->setModelClass('PaymentBundle\Factory\Providers\BankTransferProvider');
        $psp1->setAppendTwigToForm('<div class="col-sm-10 detail-transfer">
            {{ "checkout.transfer" | trans({"%bank_name%": twig_global.getParameter("name"), "%bank_account%": twig_global.getParameter("bank_account") }) | nl2br }}
            <br />
        </div>
        <div class="col-sm-12 priceAndCheck">
            <div class="price">
                <span>{{ "checkout.amount" | trans }}:</span>
                <strong style="margin-right: 15px;">{{ totals.amount | price }}</strong>
                <button >{{ "checkout.confirm" | trans }}</button>
            </div>
            <a href="{{ path(\'payment_checkout_deliveryinfo\') }}" class="btn btn-primary" title="{{ "back" | trans }}">{{ "back" | trans }}</a>
        </div>');

        $pm2 = new PaymentMethod();
        $pm2->setName('Store Pickup');
        $psp2 = new PaymentServiceProvider();
        $psp2->setActive(false);
        $psp2->setIsTestingAccount(false);
        $psp2->setRecurring(false);
        $psp2->setPaymentMethod($pm2);

        $pm3 = new PaymentMethod();
        $pm3->setName('Paypal');
        $psp3 = new PaymentServiceProvider();
        $psp3->setActive(true);
        $psp3->setIsTestingAccount(true);
        $psp3->setRecurring(false);
        $psp3->setPaymentMethod($pm3);
        $psp3->setApiCredentialParameters(array(
            'host' => 'https://api.sandbox.paypal.com',
            'client_id' => 'AafbeOnqAQTpS4bgP85kvrewollR8XsxAYmHlHI7ZzqEXqfjHMrMCaCjZjweT5y4DemLMSlfPro-P3Nz',
            'secret' => 'EJIuyFXnqYwW5HtPmPl7TsWsoCgT0-RtPnAa8TodOUGjOg9yp6E0nZOHIM5bOVP_Q1jSnTencHlxGUQ7',
            'return_url' => 'http://sasturain.dev/response-ok?paypal=true',
            'cancel_url' => 'http://sasturain.dev/cancel-payment'
        ));
        $psp3->setFormClass('PaymentBundle\Form\PayPalType');
        $psp3->setModelClass('PaymentBundle\Factory\Providers\PayPalDirectPaymentProvider');
        $psp3->setAppendTwigToForm('<div class="col-sm-12 priceAndCheck">
            <div class="price">
                <span>{{ "checkout.amount" | trans }}:</span>
                <strong style="margin-right: 15px;">{{ totals.amount | price }}</strong>
                <button >{{ "checkout.confirm" | trans }}</button>
            </div>
            <a href="{{ path(\'payment_checkout_deliveryinfo\') }}" class="btn btn-primary" title="{{ "back" | trans }}">{{ "back" | trans }}</a>
        </div>');

        $pm4 = new PaymentMethod();
        $pm4->setName('Paypal Direct Payment');
        $psp4 = new PaymentServiceProvider();
        $psp4->setActive(true);
        $psp4->setIsTestingAccount(true);
        $psp4->setRecurring(true);
        $psp4->setPaymentMethod($pm4);
        $psp4->setApiCredentialParameters(array(
            'host' => 'https://api.sandbox.paypal.com',
            'client_id' => 'AafbeOnqAQTpS4bgP85kvrewollR8XsxAYmHlHI7ZzqEXqfjHMrMCaCjZjweT5y4DemLMSlfPro-P3Nz',
            'secret' => 'EJIuyFXnqYwW5HtPmPl7TsWsoCgT0-RtPnAa8TodOUGjOg9yp6E0nZOHIM5bOVP_Q1jSnTencHlxGUQ7',
            'return_url' => 'http://sasturain.dev/response-ok?paypal=true',
            'cancel_url' => 'http://sasturain.dev/cancel-payment'
        ));
        $psp4->setFormClass('PaymentBundle\Form\PayPalDirectPaymentType');
        $psp4->setModelClass('PaymentBundle\Factory\Providers\PayPalDirectPaymentProvider');
        $psp4->setAppendTwigToForm('<div class="col-sm-12 priceAndCheck">
            <div class="price">
               <span>{{ "checkout.amount" | trans }}:</span>
               <strong>{{ totals.amount | price }}</strong>
               <button >{{ "checkout.pay" | trans }}</button>
            </div>
            <a href="{{ path(\'payment_checkout_deliveryinfo\') }}" class="btn btn-primary" title="{{ "back" | trans }}">{{ "back" | trans }}</a>
        </div>');

        $pm6 = new PaymentMethod();
        $pm6->setName('Braintree');
        $psp6 = new PaymentServiceProvider();
        $psp6->setActive(true);
        $psp6->setIsTestingAccount(true);
        $psp6->setRecurring(true);
        $psp6->setPaymentMethod($pm6);
        $psp6->setApiCredentialParameters(array(
            'environment'=> 'sandbox',
            'merchant_id'=> '3j49t8qb3h4nv9hk',
            'public_key'=> 'tygwjhymnm5bm55s',
            'private_key'=> 'a791f33b9d41ab9c57c857da1c526fa1'
        ));
        $psp6->setFormClass('PaymentBundle\Form\BraintreeType');
        $psp6->setModelClass('PaymentBundle\Factory\Providers\BraintreeProvider');
        $psp6->setAppendTwigToForm('<section>
            <div class="bt-drop-in-wrapper">
                <div id="bt-dropin"></div>
            </div>

            <label for="amount">
                <span class="input-label">Amount</span>
                <div class="input-wrapper amount-wrapper">
                    <input id="amount" name="braintree[amount]" type="tel" min="1" placeholder="Amount" value="10">
                </div>
            </label>
        </section>
        <button class="btn btn-primary" type="submit"><span>Test Transaction</span></button>');

        $transaction = new Transaction();
        $transaction->setTransactionKey(uniqid());
        $transaction->setStatus(Transaction::STATUS_PENDING);
        $transaction->setTotalPrice(2.12);
        $transaction->setVat($this->container->getParameter('core.vat'));
        $transaction->setPaymentMethod($pm);
        $transaction->setActor($actor);
        $transaction->setCreated(new \DateTime('now'));

        $productPurchase = new ProductPurchase();
        $productPurchase->setProduct($product);
        $productPurchase->setQuantity(1);
        $productPurchase->setBasePrice(2.12);
        $productPurchase->setTotalPrice(2.12);
        $productPurchase->setTransaction($transaction);
        $productPurchase->setCreated(new \DateTime('now'));
        $productPurchase->setReturned(false);

        $address = new Address();
        $address->setAddress('Test address 113');
        $address->setPostalCode('08349');
        $address->setCity('Cabrera de Mar');
        $address->setState('Barcelona');
        $address->setCountry($country);
        $address->setPhone('123123123');
        $address->setPreferredSchedule(1);
        $address->setContactPerson('Testo Ramon');
        $address->setForBilling(true);
        $address->setDni('33956669K');
        $address->setActor($actor);

        $address2 = new Address();
        $address2->setAddress('Test address 112');
        $address2->setPostalCode('08349');
        $address2->setCity('Cabrera de Mar');
        $address2->setState('Barcelona');
        $address2->setCountry($country);
        $address2->setPhone('123123121');
        $address2->setPreferredSchedule(1);
        $address2->setContactPerson('Test User');
        $address2->setForBilling(true);
        $address2->setDni('30110048N');
        $address2->setActor($actor2);

        $invoice = new Invoice();
        $invoice->setInvoiceNumber(rand(1600, 2000));
        $invoice->setTransaction($transaction);
        $invoice->setFullName('full name test invoice');
        $invoice->setCreated(new \DateTime('now'));
        $invoice->setDni('33956669K');
        $invoice->setAddressInfo($address);

        $delivery = new Delivery();
        $delivery->setFullName('Tes full name');
        $delivery->setPhone('123123123');
        $delivery->setPreferredSchedule(1);
        $delivery->setExpenses(5.5);
        $delivery->setExpensesType('by_percentage');
        $delivery->setTransaction($transaction);
        $delivery->setDni('33956669K');
        $delivery->setAddressInfo($address);
        $delivery->setDeliveryPhone('123123123');
        $delivery->setDeliveryPreferredSchedule(1);
        $delivery->setDeliveryDni('33956669K');
        $delivery->setDeliveryAddress('Address test 113');
        $delivery->setDeliveryPostalCode('08349');
        $delivery->setDeliveryCity('Cabrera de Mar');
        $delivery->setDeliveryState('Barcelona');
        $delivery->setDeliveryCountry($country);


        $this->getManager()->persist($product);
        $this->getManager()->persist($product2);
        $this->getManager()->persist($product3);

        $this->getManager()->persist($pm);
        $this->getManager()->persist($psp);
        $this->getManager()->persist($pm1);
        $this->getManager()->persist($psp1);
        $this->getManager()->persist($pm2);
        $this->getManager()->persist($psp2);
        $this->getManager()->persist($pm3);
        $this->getManager()->persist($psp3);
        $this->getManager()->persist($pm4);
        $this->getManager()->persist($psp4);
        $this->getManager()->persist($pm6);
        $this->getManager()->persist($psp6);

        $this->getManager()->persist($transaction);
        $this->getManager()->persist($productPurchase);
        $this->getManager()->persist($address);
        $this->getManager()->persist($address2);
        $this->getManager()->persist($invoice);
        $this->getManager()->persist($delivery);

        $this->getManager()->flush();
        
    }
    
    
   
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }

}
