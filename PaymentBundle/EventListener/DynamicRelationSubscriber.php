<?php
namespace PaymentBundle\EventListener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\EventSubscriber;

class DynamicRelationSubscriber implements EventSubscriber
{
    /**
     * @var array
     */
    protected $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::loadClassMetadata,
        );
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        // the $metadata is the whole mapping info for this class
        $metadata = $eventArgs->getClassMetadata();
        $class = $metadata->getReflectionClass();
         
        //transaction dynamic actor
        if ($metadata->getName() == 'PaymentBundle\Entity\Transaction') {
            $metadata->mapManyToOne(array(
                'targetEntity' => $this->mapping['baseactor']['entity'],
                'fieldName' => 'actor',
                'joinColumns' => array(array('name' => 'actor_id')),
                'inversedBy' => 'transactions',
            ));
        }elseif ($metadata->getName() == $this->mapping['baseactor']['entity']) {
            $metadata->mapOneToMany(array(
                'targetEntity' => $class->getName(),
                'fieldName' => 'transactions',
                'mappedBy' => 'actor',
            ));
        }
        if ($metadata->getName() == 'PaymentBundle\Entity\Address') {
            $metadata->mapManyToOne(array(
                'targetEntity' => $this->mapping['baseactor']['entity'],
                'fieldName' => 'actor',
                'joinColumns' => array(array('name' => 'actor_id')),
                'inversedBy' => 'addresses',
            ));
        }elseif ($metadata->getName() == $this->mapping['baseactor']['entity']) {
            $metadata->mapOneToMany(array(
                'targetEntity' => $class->getName(),
                'fieldName' => 'addresses',
                'mappedBy' => 'actor',
            ));
        }
        
        //dynamic product
        if ($metadata->getName() == 'PaymentBundle\Entity\ProductPurchase') {
            $metadata->mapManyToOne(array(
                'targetEntity' => $this->mapping['product']['entity'],
                'fieldName' => 'product',
                'joinColumns' => array(array('name' => 'product_id')),
                'inversedBy' => 'purchases',
            ));
        }elseif ($metadata->getName() == $this->mapping['product']['entity']) {
            $metadata->mapOneToMany(array(
                'targetEntity' => $class->getName(),
                'fieldName' => 'purchases',
                'mappedBy' => 'product',
            ));
        }
        
        if ($metadata->getName() == 'PaymentBundle\Entity\CartItem') {
            $metadata->mapManyToOne(array(
                'targetEntity' => $this->mapping['product']['entity'],
                'fieldName' => 'product',
                'joinColumns' => array(array('name' => 'product_id')),
                'inversedBy' => 'carts',
            ));
        }elseif ($metadata->getName() == $this->mapping['product']['entity']) {
            $metadata->mapOneToMany(array(
                'targetEntity' => $class->getName(),
                'fieldName' => 'carts',
                'mappedBy' => 'product',
            ));
        }
        
        if ($metadata->getName() == $this->mapping['product']['entity']) {
            $metadata->mapManyToMany(array(
                'targetEntity'  => $this->mapping['product']['entity'],
                'fieldName'     => 'relatedProducts',
                'cascade'       => array('persist'),
                'joinTable'     => array(
                    'name'        => 'related_products',
                    'joinColumns' => array(
                        array(
                            'name'                  => 'product_id',
                            'referencedColumnName'  => 'id',
                        ),
                    ),
                    'inverseJoinColumns'    => array(
                        array(
                            'name'                  => 'related_product_id',
                            'referencedColumnName'  => 'id',
                        ),
                    )
                )
            ));
        }
        
    }
}
                    