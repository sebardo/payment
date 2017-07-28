<?php

namespace PaymentBundle\Controller;

use PaymentBundle\CheckoutManager;
use PaymentBundle\Entity\Invoice;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Invoice controller.
 *
 * @Route("/admin/invoices")
 */
class InvoiceController extends Controller
{
    /**
     * Lists all Invoice entities.
     *
     * @return array
     *
     * @Route("/")
     * @Method("GET")
     * @Template("PaymentBundle:Invoice:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Returns a list of Invoice entities in JSON format.
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
        $jsonList->setRepository($em->getRepository('PaymentBundle:Invoice'));

        $response = $jsonList->get();

        return new JsonResponse($response);
    }

    /**
     * Returns a list of Invoice entities for a given user in JSON format.
     *
     * @param int $userId The user id
     *
     * @return JsonResponse
     *
     * @Route("/users/{userId}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function listforUserJsonAction($userId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:Invoice'));
        $jsonList->setEntityId($userId);

        $response = $jsonList->get();

        return new JsonResponse($response);
    }

    /**
     * Finds and displays an Invoice entity.
     *
     * @param int $id The entity id
     *
     * @throws NotFoundHttpException
     * @return array
     *
     * @Route("/{id}")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Invoice $entity */
        $invoice = $em->getRepository('PaymentBundle:Invoice')->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        // download invoice
//        if (!$invoice ||
//            false === $this->container->get('checkout_manager')->isCurrentUserOwner($invoice->getTransaction())) {
//            throw new AccessDeniedException();
//        }

        /** @var CheckoutManager $checkoutManager */
        $checkoutManager = $this->container->get('checkout_manager');

        $delivery = $invoice->getTransaction()->getDelivery();
        $totals = $checkoutManager->calculateTotals($invoice->getTransaction(), $delivery);
        if ('true' === $request->get('download')) {
            $html = $this->container->get('templating')->render('PaymentBundle:Profile:Invoice/download.html.twig', array(
                    'delivery' => $delivery,
                    'invoice'  => $invoice,
                    'totals'   => $totals,
                ));
            $html2pdf = $this->get('html2pdf_factory')->create();
            $html2pdf->WriteHTML($html);
            
            return new Response(
                $html2pdf->Output('invoice'.$invoice->getInvoiceNumber().'.pdf'),
                200,
                array(
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="invoice'.$invoice->getInvoiceNumber().'.pdf"'
                )
            );
        }
        
       
        return array(
            'entity' => $invoice,
            'totals' => $totals,
        );
    }
}
