<?php

namespace App\Controller;
use App\Service\FileUploader;
use App\Form\AdmissionFormType;
use App\Entity\Courses;
use App\Entity\Subject;
use App\Entity\Test;
use App\Entity\ElectiveName;
use App\Entity\Transcations;
use App\Entity\AdmissionStudentDetails;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use paytmpg\pg\constants\LibraryConstants;
use paytmpg\pg\constants\MerchantProperties;
define('PROJECT','./vendor/paytm-pg');

class AdmissionController extends AbstractController
{
    /**
     * @Route("/", name="admission")
     */
    public function admission(): Response
    {
     
			//Code	
        
            return $this->redirectToRoute('pay-now');
           
        }
        return $this->render('admission/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/pay-now", name="pay-now")
     */

     public function pay(Request $request, SessionInterface $session){

        $order_id = $session->get('order_id');
        $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();         
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->register('helper.service', 'App\Helper\PaytmChecksum');        
            $paytmParams = array(
                "MID" => "mid_key",
                "WEBSITE" => "WEBSTAGING",
                "INDUSTRY_TYPE_ID" => "Retail",
                "CHANNEL_ID" => "WEB",
                "ORDER_ID" => $order_id,
                "CUST_ID" => 1,
                "EMAIL" => 'example@gmail.com',
                "TXN_AMOUNT" => 300,
                "CALLBACK_URL" => $baseurl.$this->generateUrl('paytmcheckout')
                );
      
             $checksum = $containerBuilder->get('helper.service')->generateSignature($paytmParams, 'key');   

            return $this->render('admission/pay.html.twig',[
                'paytmParams' => $paytmParams,
                'checksum' => $checksum,                
            ]);

     }

    /**
    * @Route("/paytmcheckout", name="paytmcheckout")
    */
    public function paytmcheckoutAction(Request $request, SessionInterface $session)
    {

        $order_id = $session->get('order_id');
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->register('helper.service', 'App\Helper\PaytmChecksum');
        $token = $containerBuilder->get('helper.service')->VerifierChecksum($request);       
        $status = $request->request->get('STATUS');
        $bankname = $request->request->get('BANKNAME');
        $payment_gateway = $request->request->get('GATEWAYNAME');
        $payment_mode = $request->request->get('PAYMENTMODE');
        // dd($request);
                $transactions = $this->getDoctrine()->getRepository(Transcations::class)->findBy(['order_id' => $request->get('ORDERID')]);
            //    $session->set('uniq',$request->get('ORDERID'));
                foreach($transactions as $t){
                    $t->setStatus('TXN_SUCCESS');
                    $t->setAmount('300');
                    if($payment_mode == 'UPI'){
                        $t->setBankName('na');
                    }else{
                        $t->setBankName($bankname);
                    }
                    
                    $t->setGatewayName($payment_gateway);
                    $t->setPaymentMode($payment_mode);
                }
                $em = $this->getDoctrine()->getManager();
                $em->persist($t);
                $em->flush();

        return $this->redirectToRoute('details');
     }    

    /**
     * @Route("/details", name="details")
     */ 
     public function details(SessionInterface $session, Request $request){
        $uniq_id = $session->get('order_id');
        
    //    dd($uniq_id);
        $details = $this->getDoctrine()->getRepository(AdmissionStudentDetails::class)->findBy(['cust_unique_id'=> $uniq_id ]);
        //dd($details);
        foreach($details as $detail){
                $st_details = $detail;
        }
        return $this->render('admission/details.html.twig',[
            'details' => $st_details
        ]);
    
}
