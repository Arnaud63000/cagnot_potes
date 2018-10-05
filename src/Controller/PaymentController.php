<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Participant;
use App\Entity\Campaign;
use App\Form\PaymentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/payment")
 */
class PaymentController extends AbstractController
{
    /**
     * @Route("/new", name="payment_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {

        $campaign_id = $request->request->get('campaign_id');

        // Payer avec Stripe
       try {
        \Stripe\Stripe::setApiKey('sk_test_QdV1IQdOxLEKWQ2GwNn2iYlH');
        $charge = \Stripe\Charge::create([
            'amount' => $request->request->get('amount') * 100, 
            'currency' => 'eur', 
            'source' => $request->request->get('stripeToken')
        ]);   
       }
       catch(\Exception $e) {
           $this->addFlash(
               'error',
               'Le paiement à échoué. Raison : '. $e->getMessage()
           );
           return $this->redirectToRoute('campaign_pay', [
                "id" => $campaign_id
           ]);
           // TODO: Gérer les messages spéciaux
       }
  
        // Instancier la campaign
        $campaign = $this->getDoctrine()
                        ->getRepository(Campaign::class)
                        ->find($campaign_id);

        // Enregistrer les participants
        $participant = new Participant();

        $participant->setName($request->request->get('name'));
        $participant->setEmail($request->request->get('email'));

        $participant->setCampaign($campaign);
        // Cowboy : 
        // $participant->setCampaignId($request->request->get('campaign_id'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($participant);
        $em->flush();

        // Enregistrer un paiement
        $payment = new Payment();

        $payment->setParticipant($participant);
        $payment->setAmount($request->request->get('amount') * 100);

        $em = $this->getDoctrine()->getManager();
        $em->persist($payment);
        $em->flush();

        // Rediriger sur campaign show
        return $this->redirectToRoute('campaign_show', [
            "id" => $campaign_id
        ]);
    }
}
