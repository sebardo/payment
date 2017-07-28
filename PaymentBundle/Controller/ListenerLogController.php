<?php

namespace PaymentBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use PaymentBundle\Entity\ListenerLog;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ListenerLogController extends Controller
{
    
    /**
     * Lists all ListenerLog entities.
     *
     * @return array
     *
     * @Route("/admin/listener/")
     * @Method("GET")
     * @Template("PaymentBundle:ListenerLog:index.html.twig")
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * Returns a list of Listener entities in JSON format.
     *
     * @return JsonResponse
     *
     * @Route("/admin/listener/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" })
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \Kitchenit\AdminBundle\Services\DataTables\JsonList $jsonList */
        $jsonList = $this->get('json_list');
        $jsonList->setRepository($em->getRepository('PaymentBundle:ListenerLog'));

        $response = $jsonList->get();

        return new JsonResponse($response);
    }

     
    
    /**
     * Finds and displays a ListenerLog entity.
     *
     * @param int $id The entity id
     *
     * @throws NotFoundHttpException
     * @return array
     *
     * @Route("/admin/listener/{id}")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var ListenerLog $entity */
        $entity = $em->getRepository('PaymentBundle:ListenerLog')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ListenerLog entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    

    /**
     * Deletes a ListenerLog entity.
     *
     * @param Request $request The request
     * @param int     $id      The entity id
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse
     *
     * @Route("/admin/listener/{id}")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var ListenerLog $entity */
            $entity = $em->getRepository('PaymentBundle:ListenerLog')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ListenerLog entity.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('info', 'listener.deleted');
        }

        return $this->redirect($this->generateUrl('payment_listenerlog_index'));
    }

    /**
     * Creates a form to delete a ListenerLog entity by id.
     *
     * @param int $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }
    
    /**
     * @Route("/ipn")
     * @Route("/listener")
     * @Template()
     */    
    public function listenerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        //instance listener log entity as paypal type
        $listenerLog = new ListenerLog();
        $listenerLog->setType('paypal');
        
        // CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
        // Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
        // Set this to 0 once you go live or don't require logging.
        define("DEBUG", 1);
        // Set to 0 once you're ready to go live
        define("USE_SANDBOX", 1);
        $logPath = realpath($this->container->getParameter('kernel.root_dir').'/logs/ipn.log');
        define("LOG_FILE", $logPath);
        // Read POST data
        $myPost = $request->request->all();
        //save var info in input field entity
        $listenerLog->setInput(json_encode($myPost));
         
        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        if(function_exists('get_magic_quotes_gpc')) {
                $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) {
                if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                        $value = urlencode(stripslashes($value));
                } else {
                        $value = urlencode($value);
                }
                $req .= "&$key=$value";
        }
        // Post IPN data back to PayPal to validate the IPN data is genuine
        // Without this step anyone can fake IPN data
        if(USE_SANDBOX == true) {
                $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        } else {
                $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
        }
        $ch = curl_init($paypal_url);
        if ($ch == FALSE) {
                return FALSE;
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        if(DEBUG == true) {
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        }
        // CONFIG: Optional proxy configuration
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        // Set TCP timeout to 30 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        // CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
        // of the certificate as shown below. Ensure the file is readable by the webserver.
        // This is mandatory for some environments.
        //$cert = __DIR__ . "./cacert.pem";
        //curl_setopt($ch, CURLOPT_CAINFO, $cert);
        $res = curl_exec($ch);
        if (curl_errno($ch) != 0) // cURL error
                {
                if(DEBUG == true) {	
                        error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
                }
                curl_close($ch);
                exit;
        } else {
                        // Log the entire HTTP response if debug is switched on.
                        if(DEBUG == true) {
                                error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
                                error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
                        }
                        curl_close($ch);
        }
        // Inspect IPN validation result and act accordingly
        // Split response headers and payload, a better way for strcmp
        $tokens = explode("\r\n\r\n", trim($res));
        $res = trim(end($tokens));
        if (strcmp ($res, "VERIFIED") == 0) {
                
                //mark message as verified
                $listenerLog->setValid(true);
            
                // check whether the payment_status is Completed
                // check that txn_id has not been previously processed
                // check that receiver_email is your PayPal email
                // check that payment_amount/payment_currency are correct
                // process payment and mark item as paid.
                // assign posted variables to local variables
                //$item_name = $_POST['item_name'];
                //$item_number = $_POST['item_number'];
                //$payment_status = $_POST['payment_status'];
                //$payment_amount = $_POST['mc_gross'];
                //$payment_currency = $_POST['mc_currency'];
                //$txn_id = $_POST['txn_id'];
                //$receiver_email = $_POST['receiver_email'];
                //$payer_email = $_POST['payer_email'];

                if(DEBUG == true) {
                    $log = date('[Y-m-d H:i e] '). "Verified IPN: $req ";
                    error_log($log. PHP_EOL, 3, LOG_FILE);
                }
        } else if (strcmp ($res, "INVALID") == 0) {
                // log for manual investigation
                // Add business logic here which deals with invalid IPN messages
                if(DEBUG == true) {
                    $log = date('[Y-m-d H:i e] '). "Invalid IPN: $req ";
                    error_log($log . PHP_EOL, 3, LOG_FILE);
                }
        }
        $em->persist($listenerLog);
        $em->flush();
      
        return array(
            'response' => $log
        );
    }
    
}
