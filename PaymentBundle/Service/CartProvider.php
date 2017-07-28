<?php

namespace PaymentBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PaymentBundle\Entity\Cart;
use PaymentBundle\Service\CartStorage;

class CartProvider
{
    /**
     * Cart identifier storage.
     *
     * @var CartStorage
     */
    protected $storage;

    /**
     * Cart manager.
     *
     * @var ObjectManager
     */
    protected $manager;

    /**
     * Cart repository.
     *
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * Cart.
     *
     * @var CartInterface
     */
    protected $cart;

    /**
     * Constructor.
     *
     * @param CartStorage          $storage
     * @param ObjectManager        $manager
     */
    public function __construct(CartStorage $storage, ObjectManager $manager)
    {
        $this->storage = $storage;
        $this->manager = $manager;
        $this->repository = $manager->getRepository('PaymentBundle:Cart');
    }

    /**
     * {@inheritdoc}
     */
    public function hasCart()
    {
        return null !== $this->cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getCart()
    {
        if (null !== $this->cart) {
            return $this->cart;
        }

        $cartIdentifier = $this->storage->getCurrentCartIdentifier();

        if ($cartIdentifier && $cart = $this->getCartByIdentifier($cartIdentifier)) {
            return $this->cart = $cart;
        }

        $cart = $this->repository->createNew();
        $this->manager->persist($cart);
        $this->manager->flush($cart);

        $this->setCart($cart);

        return $cart;
    }

    /**
     * {@inheritdoc}
     */
    public function setCart(Cart $cart)
    {
        $this->cart = $cart;
        $this->storage->setCurrentCartIdentifier($cart);
    }

    /**
     * {@inheritdoc}
     */
    public function abandonCart()
    {
        $this->cart = null;
        $this->storage->resetCurrentCartIdentifier();
    }

    /**
     * Gets cart by cart identifier.
     *
     * @param mixed $identifier
     *
     * @return Cart|null
     */
    protected function getCartByIdentifier($identifier)
    {
        return $this->repository->find($identifier);
    }
}
