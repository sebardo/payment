<?php

namespace PaymentBundle\Controller;

use PaymentBundle\Entity\Address;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use stdClass;

/**
 * Address controller.
 *
 * @Route("/admin/actor/{actorId}/addresses")
 */
class AddressController extends Controller
{
    /**
     * Returns a list of Address entities in JSON format.
     *
     * @param int $actorId The actor id
     *
     * @return JsonResponse
     *
     * @Route("/{id}/getinfo.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function getInfoAction($actorId, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Address $entity */
        $entity = $em->getRepository('PaymentBundle:Address')->findOneById($id);

        $addressResponse = new stdClass();
        $addressResponse->id = $entity->getId();
        $addressResponse->address = $entity->getAddress();
        $addressResponse->dni = $entity->getDni();
        $addressResponse->city = $entity->getCity();
        $addressResponse->state = $entity->getState()->getId();
        $addressResponse->country = $entity->getCountry()->getId();
        $addressResponse->phone = $entity->getPhone();
        $addressResponse->phone2 = $entity->getPhone2();
        $addressResponse->postalCode = $entity->getPostalCode();
        $addressResponse->preferredSchedule = $entity->getPreferredSchedule();
        
        return new JsonResponse($addressResponse);
    }
    
    /**
     * Returns a list of Address entities in JSON format.
     *
     * @param int $actorId The actor id
     *
     * @return JsonResponse
     *
     * @Route("/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function listJsonAction($actorId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:Address'));
        $jsonList->setEntityId($actorId);

        $response = $jsonList->get();

        return new JsonResponse($response);
    }

    /**
     * Finds and displays an Address entity.
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
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Address $entity */
        $entity = $em->getRepository('PaymentBundle:Address')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }
    
    /**
     * Creates a new Address entity.
     *
     * @param Request $request The request
     *
     * @return array|RedirectResponse
     *
     * @Route("/new")
     * @Template("PaymentBundle:Address:new.html.twig")
     */
    public function newAction(Request $request, $actorId)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Newsletter $entity */
        $entity = $em->getRepository('CoreBundle:Actor')->find($actorId);
        
        $address = new Address();
        $form = $this->createForm('PaymentBundle\Form\AddressType', $address, array('token_storage' => $this->container->get('security.token_storage')));
       
        if($request->getMethod() == 'POST'){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                    $address->setActor($entity);
                    $forBilling = $em->getRepository('PaymentBundle:Address')->findOneBy(array('actor' => $entity, 'forBilling' => true));
                    if(!$forBilling) {
                        $address->setForBilling(true);
                    }else{
                        $address->setForBilling(false);
                    }
                    $em->persist($address);
                    $em->flush();
                    //if come from popup
                    if ($request->isXMLHttpRequest()) {         
                        return new JsonResponse(array(
                                    'id' => $address->getId()
                                ));
                    }
                    $this->get('session')->getFlashBag()->add('success', 'user.email.created');
                    return $this->redirect($this->generateUrl('core_actor_show', array('id' => $entity->getId())));
            }
            //if come from popup
            if ($request->isXMLHttpRequest()) {         
                return new JsonResponse(array(
                            'status' => false
                        ));
            }
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }
    
    /**
     * Creates a new Address entity.
     *
     * @param Request $request The request
     *
     * @return array|RedirectResponse
     *
     * @Route("/{address}/edit")
     * @Template("PaymentBundle:Address:new.html.twig")
     */
    public function editAction(Request $request, $actorId, Address $address)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Newsletter $entity */
        $entity = $em->getRepository('CoreBundle:Actor')->find($actorId);
        $form = $this->createForm('PaymentBundle\Form\AddressType', $address, array('token_storage' => $this->container->get('security.token_storage')));
       
        if($request->getMethod() == 'POST'){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                    $em->flush();
                    //if come from popup
                    if ($request->isXMLHttpRequest()) {         
                        return new JsonResponse(array(
                                    'id' => $address->getId()
                                ));
                    }
                    $this->get('session')->getFlashBag()->add('success', 'address.created');
                    return $this->redirect($this->generateUrl('core_actor_show', array('id' => $entity->getId())));
            }
            //if come from popup
            if ($request->isXMLHttpRequest()) {         
                return new JsonResponse(array(
                            'status' => false
                        ));
            }
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }
}
