<?php

namespace PaymentBundle\Form;

use PaymentBundle\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class AddressType
 */
class AddressType extends AbstractType
{

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tokenStorage = $options['token_storage'];
        $builder
            ->add('dni')
            ->add('address')
            ->add('city')
            ->add('state')
//            ->add('state', EntityType::class, array(
//                    'class' => 'CoreBundle:State',
//                    'query_builder' => function(EntityRepository $er) {
//                        return $er->createQueryBuilder('c');
//                    },
//                    'required' => false,
//                    'placeholder' => 'Selecciona tu provincia',
//                ))
            ->add('country', EntityType::class, array(
                    'class' => 'CoreBundle:Country',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('c');
                    },
                    'required' => false,
                    'placeholder' => 'Selecciona tu país',
                ))
            ->add('postalCode')
            ->add('phone')
            ->add('preferredSchedule', ChoiceType::class, array(
                'choices'  => Address::getSchedules(),
                'required' => false,
                'choices_as_values' => true,
            ));

//        $user = $tokenStorage->getToken()->getUser();
//        if (!$user) {
//            throw new \LogicException(
//                'The AddressFormType cannot be used without an authenticated user!'
//            );
//        }
//
//        $factory = $builder->getFormFactory();
//
//        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($user, $factory) {
//            $form = $event->getForm();
//
//            // if user is a business, add the contact person field
////            if ($user::BUSINESS == $user->getAccountType()) {
////                $formOptions = array(
////                    'required' => false
////                );
////
////                $form->add($factory->createNamed('contactPerson', 'text', null, $formOptions));
////            }
//        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PaymentBundle\Entity\Address',
            'token_storage' => null
        ));
    }
}
