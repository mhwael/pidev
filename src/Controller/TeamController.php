<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeamController extends AbstractController
{
    // ========== BACKEND ROUTES (Dashboard) ==========

    #[Route('/dashboard/team', name: 'app_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
    {
        return $this->render('team/index.html.twig', [
            'teams' => $teamRepository->findAll(),
        ]);
    }

    #[Route('/dashboard/team/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $team = new Team();
        $team->setCaptain($this->getUser());
        $team->addMember($this->getUser());
        
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Équipe créée avec succès !');
            return $this->redirectToRoute('app_team_index');
        }

        return $this->render('team/new.html.twig', [
            'team' => $team,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/dashboard/team/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(?Team $team): Response
    {
        if (!$team) {
            $this->addFlash('warning', 'Équipe introuvable.');
            return $this->redirectToRoute('app_team_index');
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/dashboard/team/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($team->getCaptain() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le capitaine peut modifier l\'équipe.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Équipe modifiée avec succès !');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/dashboard/team/{id}/delete', name: 'app_team_delete', methods: ['POST'])]
    public function delete(Request $request, Team $team, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($team->getCaptain() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le capitaine peut supprimer l\'équipe.');
            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$team->getId(), $request->request->get('_token'))) {
            $entityManager->remove($team);
            $entityManager->flush();
            $this->addFlash('success', 'Équipe supprimée avec succès !');
        }

        return $this->redirectToRoute('app_team_index');
    }

    // ========== FRONTEND ROUTES (Public) ==========

    #[Route('/team', name: 'app_team_list', methods: ['GET'])]
    public function list(TeamRepository $teamRepository): Response
    {
        return $this->render('team/team.html.twig', [
            'teams' => $teamRepository->findAll(),
        ]);
    }

    #[Route('/team/{id}', name: 'app_team_view', methods: ['GET'])]
    public function view(Team $team): Response
    {
        return $this->render('team/view.html.twig', [
            'team' => $team,
        ]);
    }
}