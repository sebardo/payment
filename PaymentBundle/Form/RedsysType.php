<?php

namespace PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class RedsysType
 */
class RedsysType extends AbstractType
{
    private $formConfig;


    /**
     * @param SecurityContext $securityContext
     */
    public function __construct($formConfig)
    {
        $this->formConfig = $formConfig;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add('Ds_Merchant_MerchantData', HiddenType::class, array('data' => $this->formConfig['data']))
            ->add('Ds_Merchant_MerchantName', HiddenType::class, array('data' => $this->formConfig['name']))
            ->add('Ds_Merchant_ProductDescription', HiddenType::class, array('data' => $this->formConfig['product']))
            ->add('Ds_Merchant_Titular', HiddenType::class, array('data' => $this->formConfig['titular']))
            ->add('Ds_Merchant_Amount', HiddenType::class, array('data' => $this->formConfig['amount']))
            ->add('Ds_Merchant_Currency', HiddenType::class, array('data' => $this->formConfig['currency']))
            ->add('Ds_Merchant_Order', HiddenType::class, array('data' => $this->formConfig['order']))
            ->add('Ds_Merchant_MerchantCode', HiddenType::class, array('data' => $this->formConfig['code']))
            ->add('Ds_Merchant_Terminal', HiddenType::class, array('data' => $this->formConfig['terminal']))
            ->add('Ds_Merchant_TransactionType', HiddenType::class, array('data' => $this->formConfig['transaction_type']))
            ->add('Ds_Merchant_MerchantURL', HiddenType::class, array('data' => $this->formConfig['bank_response_url']))
            ->add('Ds_Merchant_UrlOK', HiddenType::class, array('data' => $this->formConfig['return_url']))
            ->add('Ds_Merchant_UrlKO', HiddenType::class, array('data' => $this->formConfig['cancel_url']))
            ->add('Ds_Merchant_MerchantSignature', HiddenType::class, array('data' => $this->formConfig['signature']))
            ->add('Ds_Merchant_ConsumerLanguage', HiddenType::class, array('data' => $this->formConfig['consumer_language']))
            ;
    }

}
