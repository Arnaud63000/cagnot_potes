<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Participant;
use App\Entity\Payment;
use App\Form\CampaignType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Statement;

/**
 * @Route("/campaign")
 */
class SpendingController extends AbstractController
{
    /**
     * @Route("/new", name="campaign_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $campaign = new Campaign();
        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        // récupération du nom de l'auteur
        $campaign->setName($request->request->get('name'));

        // récupération du paramètre get depuis la homepage 
        if (isset($_GET['campaign_title'])) {
            $campaign_title = $_GET['campaign_title'];
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $id = md5(random_bytes(50));
            $campaign->setId($id);

            $em = $this->getDoctrine()->getManager();
            $em->persist($campaign);
            $em->flush();

            return $this->redirectToRoute('campaign_show', compact("id"));
        }

        if (isset($_GET['campaign_title'])) {
            return $this->render('campaign/new.html.twig', [
                'campaign' => $campaign,
                'form' => $form->createView(),
                'campaign_title' => $campaign_title,
            ]);
        } else {
            return $this->render('campaign/new.html.twig', [
                'campaign' => $campaign,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/{id}", name="campaign_show", methods="GET")
     */
    public function show(Campaign $campaign): Response
    {   
        //Récupération des participants et des paiements
        $query = 'SELECT * from participant
        LEFT JOIN payment ON payment.participant_id = participant.id
        WHERE campaign_id = "'.$campaign->getId().'"';

        $statement = $this->getDoctrine()->getManager()->getConnection()->prepare($query);

        $statement->execute();



        $participantsWithParticipations = $statement->fetchAll();

        $total_amount = 0;
        $total_participant = count($participantsWithParticipations);

        for($i = 0; $i <= ($total_participant - 1); $i++)
        {
            $total_amount += $participantsWithParticipations[$i]["amount"];
        }

        $total_amount2 = $total_amount / 100;
        $objectif = $total_amount2 / $campaign->getGoal() * 100;

        return $this->render('campaign/show.html.twig', compact('campaign', 'participantsWithParticipations', 'total_participant', 'total_amount2', 'objectif'));
    }

    /**
     * @Route("/{id}/pay", name="campaign_pay", methods="GET")
     */
    public function pay(Campaign $campaign): Response
    {
        $amount_participant = $_GET['amount_participant'];
        return $this->render('campaign/pay.html.twig', compact('campaign', 'amount_participant'));
    }

    /**
     * @Route("/{id}/edit", name="campaign_edit", methods="GET|POST")
     */
    public function edit(Request $request, Campaign $campaign): Response
    {
        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('campaign_edit', ['id' => $campaign->getId()]);
        }

        return $this->render('campaign/edit.html.twig', [
            'campaign' => $campaign,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="campaign_delete", methods="DELETE")
     */
    public function delete(Request $request, Campaign $campaign): Response
    {
        if ($this->isCsrfTokenValid('delete'.$campaign->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($campaign);
            $em->flush();
        }

        return $this->redirectToRoute('campaign_index');
    }
}
