<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class TournamentController extends AbstractController
{
    #[Route('/dashboard/tournament', name: 'app_tournament_index', methods: ['GET'])]
    public function index(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('tournament/index.html.twig', [
            'tournaments' => $tournamentRepository->findAll(),
        ]);
    }
    #[Route('/tournament', name: 'app_tournament', methods: ['GET'])]
    public function tournament(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('tournament/tournament.html.twig', [
            'tournaments' => $tournamentRepository->findAll(),
        ]);
    }


    #[Route('/dashboard/new', name: 'app_tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $tournament = new Tournament();
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // L'utilisateur connecté devient organisateur
            $tournament->setOrganizer($this->getUser());
            
            $entityManager->persist($tournament);
            $entityManager->flush();

            $this->addFlash('success', 'Tournoi créé avec succès !');

            return $this->redirectToRoute('app_tournament_index');
        }

        return $this->render('tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/tournament/{id}', name: 'app_tournament_show', methods: ['GET'])]
    public function show(Tournament $tournament): Response
    {
        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    #[Route('/dashboard/tournament/{id}/edit', name: 'app_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        // Seul l'organisateur peut modifier
        if ($tournament->getOrganizer() !== $this->getUser()) {
            $this->addFlash('error', 'Seul l\'organisateur peut modifier ce tournoi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // Impossible de modifier si déjà commencé
        if (in_array($tournament->getStatus(), ['ongoing', 'completed'])) {
            $this->addFlash('error', 'Impossible de modifier un tournoi déjà commencé.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Tournoi modifié avec succès !');

            return $this->redirectToRoute('app_tournament_index');
        }

        return $this->render('tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/tournament/{id}', name: 'app_tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        // Seul l'organisateur peut supprimer
        if ($tournament->getOrganizer() !== $this->getUser()) {
            $this->addFlash('error', 'Seul l\'organisateur peut supprimer ce tournoi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // Impossible de supprimer si déjà commencé
        if (!in_array($tournament->getStatus(), ['draft', 'open'])) {
            $this->addFlash('error', 'Impossible de supprimer un tournoi déjà commencé.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$tournament->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tournament);
            $entityManager->flush();
            $this->addFlash('success', 'Tournoi supprimé avec succès !');
        }

        return $this->redirectToRoute('app_tournament_index');
    }
}