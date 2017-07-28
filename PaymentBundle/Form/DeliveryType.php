<?php

namespace PaymentBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PaymentBundle\Entity\Delivery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class DeliveryType
 */
class DeliveryType extends AbstractType
{
    private $securityContext;
    private $em;
    private $session;

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->securityContext = $options['securityContext'];
        $this->em = $options['manager'];
        $this->session = $options['session'];
        
        /** @var User $user */
        $user = $this->securityContext->getToken()->getUser();
        if (!$user) {
            throw new \LogicException('The DeliveryType cannot be used without an authenticated user!');
        }

        $numDeliveryAddresses = $this->em->getRepository('PaymentBundle:Address')->countTotal($user->getId(), false);

        // initialize delivery addresses options
        $selectDelivery['account.address.select.same'] = 'same';
        if ($numDeliveryAddresses > 0) {
            $selectDelivery['account.address.select.existing'] = 'existing';
        }
        $selectDelivery['account.address.select.new'] = 'new';

        $builder
            ->add('fullName', null, array(
                'required' => false
            ))
            ->add('dni', null, array(
                'required' => false
            ))
            ->add('address', null, array(
                'required' => false
            ))
            ->add('city', TextType::class, array(
                'required' => false
            ))
            ->add('state', EntityType::class, array(
                'class' => 'CoreBundle:State',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('c');
                },
                'required' => false,
                'placeholder' => 'Selecciona tu provincia',
                'empty_data'  => null
            ))
            
            ->add('postalCode', null, array(
                'required' => false
            ))
            ->add('phone', null, array(
                'required' => false
            ))
            ->add('phone2', null, array(
                'required' => false
            ))
            ->add('preferredSchedule', ChoiceType::class, array(
                'choices'  => Delivery::getSchedules(),
                'required' => false,
                'choices_as_values' => true,
            ))

            ->add('deliveryDni', TextType::class, array(
                'required' => false
            ))
            ->add('deliveryAddress', TextType::class, array(
                'required' => false
            ))
            ->add('deliveryCity', TextType::class, array(
                'required' => false
            ))
            ->add('deliveryState', EntityType::class, array(
                    'class' => 'CoreBundle:State',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('c');
                    },
                    'required' => false,
                    'placeholder' => 'Selecciona tu provincia',
                    'empty_data'  => null
                ))
            ->add('deliveryPostalCode', TextType::class, array(
                'required' => false
            ))
            ->add('deliveryPhone', TextType::class, array(
                'required' => false
            ))
            ->add('deliveryPhone2', TextType::class, array(
                'required' => false
            ))
            ->add('deliveryPreferredSchedule', ChoiceType::class, array(
                'choices'  => Delivery::getSchedules(),
                'required' => false
            ))
            ->add('notes', TextareaType::class, array(
                'required' => false
            ));

            $factory = $builder->getFormFactory();

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($user, $numDeliveryAddresses, $factory) {
            $form = $event->getForm();
 

            // delivery addresses
            if ($numDeliveryAddresses > 0) {
                $existingDeliveryAddress = null;

//                if ($this->session->has('select-delivery') && 'existing' === $this->session->get('select-delivery')) {
//                    $existingDeliveryAddress = $this->session->get('existing-delivery-address');
//                }

                $deliveryAddressData = !is_null($existingDeliveryAddress) ?
                    $this->em->getReference('PaymentBundle:Address', $existingDeliveryAddress) :
                    null;

                $formOptions = array(
                    'class'         => 'PaymentBundle\Entity\Address',
                    'multiple'      => false,
                    'expanded'      => false,
                    'mapped'        => false,
                    'required'      => false,
                    'auto_initialize' => false,
                    'data'          => $deliveryAddressData,
                    'query_builder' => function(EntityRepository $er) use ($user) {
                        return $er->createQueryBuilder('a')
                            ->where('a.actor = :user')
                            ->andWhere('a.forBilling = false')
                            ->setParameter('user', $user);
                    }
                );

                $form->add($factory->createNamed('existingDeliveryAddress', 'entity', null, $formOptions));
            }
        });

        $builder->add('selectDelivery', ChoiceType::class, array(
            'choices'  => $selectDelivery,
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'label'    => false,
            'mapped'   => false,
            'data'     => $this->session->has('select-delivery') ? $this->session->get('select-delivery') : 'same'
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PaymentBundle\Entity\Delivery',
            'securityContext' => null,
            'manager' => null,
            'session' => null,
        ));
    }
}
