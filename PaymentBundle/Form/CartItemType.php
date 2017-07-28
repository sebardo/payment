<?php

namespace PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CartItemType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', IntegerType::class, array('attr' => array('min' => 1, 'class' => 'quantity')))
//            ->add('shippingCost', HiddenType::class)
//            ->add('storePickup', ChoiceType::class, array(
//                'choices'  => array(1 => 'Recoger en tienda', 0 => 'Envio On-line'),
//                'required' => true,
//                'expanded' => true,
//                'multiple' => false,
//                'empty_data' => null
////                'placeholder' => 'Selecciona el tipo de envío'
//            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'data_class' => 'PaymentBundle\Entity\CartItem',
            ))
        ;
    }

}
