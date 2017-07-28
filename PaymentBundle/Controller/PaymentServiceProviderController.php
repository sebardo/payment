<?php

namespace PaymentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use PaymentBundle\Entity\PaymentServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * PaymentServiceProvider controller.
 *
 * @Route("/admin/paymentserviceprovider")
 */
class PaymentServiceProviderController extends Controller
{
    /**
     * Lists all PaymentServiceProvider entities.
     *
     * @Route("/")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $paymentServiceProviders = $em->getRepository('PaymentBundle:PaymentServiceProvider')->findAll();

        return array(
            'paymentServiceProviders' => $paymentServiceProviders,
        );
    }

    /**
     * Returns a list of Advert entities in JSON format.
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
        $jsonList->setRepository($em->getRepository('PaymentBundle:PaymentServiceProvider'));

        $response = $jsonList->get();

        return new JsonResponse($response);
    }
    
    /**
     * Creates a new PaymentServiceProvider entity.
     *
     * @Route("/new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $paymentServiceProvider = new PaymentServiceProvider();
        $form = $this->createForm('PaymentBundle\Form\PaymentServiceProviderType', $paymentServiceProvider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             
            $em = $this->getDoctrine()->getManager();
            $em->persist($paymentServiceProvider);
            $em->persist($paymentServiceProvider->getPaymentMethod());
            $em->flush();

            return $this->redirectToRoute('payment_paymentserviceprovider_show', array('id' => $paymentServiceProvider->getId()));
        }

        return array(
            'paymentServiceProvider' => $paymentServiceProvider,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a PaymentServiceProvider entity.
     *
     * @Route("/{id}")
     * @Method("GET")
     * @Template()
     */
    public function showAction(PaymentServiceProvider $paymentServiceProvider)
    {
        $deleteForm = $this->createDeleteForm($paymentServiceProvider);

        return array(
            'entity' => $paymentServiceProvider,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing PaymentServiceProvider entity.
     *
     * @Route("/{id}/edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, PaymentServiceProvider $paymentServiceProvider)
    {
        $deleteForm = $this->createDeleteForm($paymentServiceProvider);
        $editForm = $this->createForm('PaymentBundle\Form\PaymentServiceProviderType', $paymentServiceProvider);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($paymentServiceProvider);
            $em->flush();

            return $this->redirectToRoute('payment_paymentserviceprovider_edit', array('id' => $paymentServiceProvider->getId()));
        }

        return array(
            'entity' => $paymentServiceProvider,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a PaymentServiceProvider entity.
     *
     * @Route("/{id}")
     * @Method("DELETE")
     * @Template()
     */
    public function deleteAction(Request $request, PaymentServiceProvider $paymentServiceProvider)
    {
        $form = $this->createDeleteForm($paymentServiceProvider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($paymentServiceProvider);
            $em->flush();
        }

        return $this->redirectToRoute('payment_paymentserviceprovider_index');
    }

    /**
     * Creates a form to delete a PaymentServiceProvider entity.
     *
     * @param PaymentServiceProvider $paymentServiceProvider The PaymentServiceProvider entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(PaymentServiceProvider $paymentServiceProvider)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('payment_paymentserviceprovider_delete', array('id' => $paymentServiceProvider->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
