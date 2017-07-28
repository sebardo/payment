<?php

namespace PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PaymentBundle\Form\PaymentMethodType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PaymentServiceProviderType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('apiCredentialParameters', CollectionType::class, array(
                // each entry in the array will be an "email" field
                'entry_type'   => TextType::class,
                // these options are passed to each "email" type
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options'  => array(
                    'required'  => false,
                    'attr'      => array('class' => 'form-control')
                ),
            ))
            ->add('recurring') 
            ->add('isTestingAccount')
            ->add('active')
            ->add('paymentMethod', PaymentMethodType::class)
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PaymentBundle\Entity\PaymentServiceProvider'
        ));
    }
}
