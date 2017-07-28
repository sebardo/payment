<?php

namespace PaymentBundle\Controller;

use PaymentBundle\Services\CheckoutManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use PaymentBundle\Entity\Transaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Transaction controller.
 *
 * @Route("/admin/transaction")
 */
class TransactionController extends Controller
{
    /**
     * Lists all Transaction entities.
     *
     * @return array
     *
     * @Route("/")
     * @Method("GET")
     * @Template("PaymentBundle:Transaction:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Returns a list of Transaction entities in JSON format.
     *
     * @return JsonResponse
     *
     * @Route("/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:Transaction'));

        $response = $jsonList->get();

        return new JsonResponse($response);
    }

    /**
     * Returns a list of Transaction entities for a given user in JSON format.
     *
     * @param int $userId The user id
     *
     * @return JsonResponse
     *
     * @Route("/actors/{actorId}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function listforUserJsonAction($actorId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:Transaction'));
        $jsonList->setEntityId($actorId);

        $response = $jsonList->get();

        return new JsonResponse($response);
    }

    /**
     * Returns a list of Transaction entities in JSON format.
     *
     * @return JsonResponse
     *
     * @Route("/contract/{id}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function agreementListJsonAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $contract = $em->getRepository('PaymentBundle:Contract')->find($id);
        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:Transaction'));
        //$jsonList->setAgreementId($contract->getAgreement()->getId());
        $response = $jsonList->get();

        return new JsonResponse($response);
    }
    
    /**
     * Returns a list of Transaction entities in JSON format.
     *
     * @return JsonResponse
     *
     * @Route("/advert/{id}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function advertListJsonAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('PaymentBundle:Advert')->find($id);
        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:Transaction'));
        $jsonList->setAdvertId($advert->getId());
        $response = $jsonList->get();

        return new JsonResponse($response);
    }
    
    /**
     * Finds and displays an Transaction entity.
     *
     * @param int $id The entity id
     *
     * @throws NotFoundHttpException
     * @return array
     *
     * @Route("/{id}")
     * @Method("GET")
     * @Template
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Transaction $entity */
        $entity = $em->getRepository('PaymentBundle:Transaction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Transaction entity.');
        }

        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->get('checkout_manager');

        $totals = $checkoutManager->calculateTotals($entity, $entity->getDelivery());

        return array(
            'entity' => $entity,
            'totals' => $totals,
        );
    }

    /**
     * Authorizes the payment of a pending transfer order
     *
     * @param int $id
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse
     *
     * @Route("/{id}/authorize-payment")
     * @Method("GET")
     */
    public function authorizePaymentAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Transaction $entity */
        $entity = $em->getRepository('PaymentBundle:Transaction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Transaction entity.');
        }

        $entity->setStatus(Transaction::STATUS_PAID);

        $em->persist($entity);
        $em->flush();

        //send email
        $this->get('core.mailer')->sendBankTransferConfirmation($entity);
        $this->get('checkout_manager')->sendToTransport($entity);

        $this->get('session')->getFlashBag()->add('success', 'order.authorized.payment');

        return $this->redirect($this->generateUrl('payment_transaction_show', array('id' => $entity->getId())));
    }

    /**
     * Set tracking code
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse
     *
     * @Route("/{id}/set-tracking-code")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function setTrackingCodeAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Transaction $entity */
        $entity = $em->getRepository('PaymentBundle:Transaction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Transaction entity.');
        }

        $form = $this->createFormBuilder($entity->getDelivery())
            ->add('trackingCode', \Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
                    'required' => true
                ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $entity->setStatus(Transaction::STATUS_DELIVERED);

                $em->persist($entity);
                $em->flush();

                $this->get('core.mailer')->sendTrackingCodeEmailMessage($entity);

                $this->get('session')->getFlashBag()->add('success', 'transaction.sent');
            }else{
                $string = (string) $form->getErrors(true, false);
                print_r($string);
                die('invalid');
            }

            return $this->redirect($this->generateUrl('payment_transaction_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView()
        );
    }
    
    /**
     * Set tracking code
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse
     *
     * @Route("/{id}/validate-cupon-code")
     * @Method({"GET", "POST"})
     * @Template
     */
    public function validateCuponCodeAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Transaction $entity */
        $entity = $em->getRepository('PaymentBundle:Transaction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Transaction entity.');
        }

        $form = $this->createFormBuilder(null)
            ->add('cuponCode', 'text', array(
                    'required' => true
                ))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            $data = $form->getNormData();
            
            if ($data['cuponCode'] != '' && $entity->getStorePickupCode() == $data['cuponCode']) {
                $entity->setStatus(Transaction::STATUS_PAID);

                $em->persist($entity);
                $em->flush();

                $this->get('core.mailer')->sendCuponCodeEmailMessage($entity);

                $this->get('session')->getFlashBag()->add('success', 'transaction.validate');
            }else{
                $this->get('session')->getFlashBag()->add('success', 'transaction.validate.invalid');
            }

            return $this->redirect($this->generateUrl('core_actor_show', array('id' => $entity->getItems()->first()->getProduct()->getActor()->getId(), 'transactions' => 1)));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView()
        );
    }

    /**
     * Deletes a Transaction entity.
     *
     * @param int $id The order id
     *
     * @throws NotFoundHttpException
     * @return array
     *
     * @Route("/{id}/delete")
     * @Method("GET")
     * @Template("PaymentBundle:Transaction:index.html.twig")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('PaymentBundle:Transaction')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Transaction entity.');
        }

        $em->remove($entity);
        $em->flush();
        
         $this->get('session')->getFlashBag()->add('info', 'transaction.deleted');
         
        return array();
    }

}
