<?php
namespace PaymentBundle\Factory;

use PaymentBundle\Entity\PaymentServiceProvider;
use PaymentBundle\Exception\InvalidServiceException;
use Symfony\Component\HttpFoundation\Request;
use PaymentBundle\Entity\Transaction;
use PaymentBundle\Entity\Delivery;
use Symfony\Component\Validator\Validator\RecursiveValidator;

/**
 * Description of PaymentProvider
 *
 * @author sebastian
 */
class PaymentProviderFactory 
{
    protected $validator;
    
    protected $container;
    
    protected $parameters;
    
    protected $name;
    
    protected $slug;
    
    protected $twig;
    
    protected $form;
    
    protected $formView;
    
    private $modelClass;
    
    public function __construct(RecursiveValidator $validator) {
        $this->validator = $validator;
    }
    
    public function initialize($container, PaymentServiceProvider $psp) {
        $this->container = $container;
        $this->parameters = $psp->getApiCredentialParameters();
//        if(is_array($this->parameters)){
//            foreach ($this->parameters as $param){
//                try {
//                    $service = $this->container->get($param);
//                    $this->parameters[$param] = $service;
//                } catch (\Exception $exc) {
//                    throw new InvalidServiceException('Invalid service ' . $param);
//                }
//            }
//        }
        $class = $psp->getModelClass();
        $this->setName($psp->getPaymentMethod()->getName());
        $this->setSlug($psp->getPaymentMethod()->getSlug());
        $this->setModelClass($class);
        $this->setForm($this->container->get('form.factory')
                ->create($psp->getFormClass(), new $class($container->get('validator'))));
         $this->setFormView($this->getForm()->createView());
        $this->setTwig($psp->getAppendTwigToForm());
        
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function getName() {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name) {
        $this->name = $name;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * {@inheritdoc}
     */
    public function setSlug($slug) {
        $this->slug = $slug;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTwig() {
        return $this->twig;
    }

    /**
     * {@inheritdoc}
     */
    public function setTwig($twig) {
        $this->twig = $twig;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function setForm($form) {
        $this->form = $form;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFormView() {
        return $this->formView;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormView($formView) {
        $this->formView = $formView;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getModelClass() {
        return $this->modelClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setModelClass($modelClass) {
        $this->modelClass = $modelClass;
    }
    
    public function process(Request $request, Transaction $transaction, Delivery $delivery) {
        $class = $this->getModelClass();
        if(is_null($class)){
            throw new \Exception('No model class defined on provider: '.$this->getName());
        }
        $instance = new $class($this->validator);
        return $instance->process($request, $transaction, $delivery);
    }
    
    public function confirmation(Request $request) {
        $class = $this->getModelClass();
        $instance = new $class($this->validator);
        return $instance->confirmation($request);
    }
    
    public function cancelation(Request $request) {
        $class = $this->getModelClass();
        $instance = new $class($this->validator);
        return $instance->cancelation($request);
    }

}
