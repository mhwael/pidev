<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\GameMatch; 
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use App\Repository\TeamRepository;
use App\Service\MatchmakingAI;
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

    #[Route('/tournament/{id}', name: 'app_tournament_view', methods: ['GET'])]
    public function view(Tournament $tournament): Response
    {
        return $this->render('tournament/view.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    /**
     * AI Matchmaking Web UI
     */
    #[Route('/tournament/{id}/matchmaking', name: 'app_tournament_matchmaking', methods: ['GET'])]
    public function showMatchmaking(
        int $id, 
        TournamentRepository $tournamentRepository, 
        MatchmakingAI $matchmakingAI
    ): Response {
        $tournament = $tournamentRepository->find($id);

        if (!$tournament) {
            throw $this->createNotFoundException('Tournoi non trouvé.');
        }

        $teams = $tournament->getTeams()->toArray();

        if (count($teams) < 2) {
            $this->addFlash('warning', 'Il faut au moins 2 équipes pour générer des matchups.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }

        $matchups = $matchmakingAI->generateMatchups($teams);
        $averageBalance = $matchmakingAI->getAverageBalance($matchups);
        $fairnessRating = $matchmakingAI->getFairnessRating($averageBalance);

        return $this->render('tournament/matchmaking.html.twig', [
            'tournament' => $tournament,
            'matchups' => $matchups,
            'averageBalance' => $averageBalance,
            'fairnessRating' => $fairnessRating,
        ]);
    }

    /**
     * Confirming the AI suggestions and saving them to the DB
     */
    #[Route('/tournament/{id}/matchmaking/confirm', name: 'app_tournament_matchmaking_confirm', methods: ['POST'])]
    public function confirmMatchmaking(
        int $id, 
        TournamentRepository $tournamentRepository, 
        EntityManagerInterface $entityManager, 
        MatchmakingAI $matchmakingAI
    ): Response {
        $tournament = $tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }

        $teams = $tournament->getTeams()->toArray();
        $matchups = $matchmakingAI->generateMatchups($teams);

        foreach ($matchups as $data) {
            $match = new GameMatch();
            $match->setTournament($tournament);
            $match->setTeam1($data['team1']);
            $match->setTeam2($data['team2']);
            $match->setStatus('pending');
            $match->setRound(1);
            

            $entityManager->persist($match);
        }

        $entityManager->flush();
        $this->addFlash('success', 'AI Matchups have been confirmed!');

        return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
    }

    #[Route('/dashboard/new', name: 'app_tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $tournament = new Tournament();
        $tournament->setOrganizer($this->getUser());
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        if ($tournament->getOrganizer() !== $this->getUser()) {
            $this->addFlash('error', 'Seul l\'organisateur peut supprimer ce tournoi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

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

    #[Route('/tournament/join', name: 'app_tournament_join', methods: ['POST'])]
    public function joinTournament(
        Request $request,
        TournamentRepository $tournamentRepository,
        TeamRepository $teamRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $teamId = $request->request->get('teamId');
        $tournamentId = $request->request->get('tournamentId');
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('join_tournament', $token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        $user = $this->getUser();
        $tournament = $tournamentRepository->find($tournamentId);
        $team = $teamRepository->find($teamId);

        if (!$tournament || !$team) {
            return $this->json(['success' => false, 'message' => 'Ressource non trouvée.'], 404);
        }

        if ($team->getCaptain() !== $user) {
            return $this->json(['success' => false, 'message' => 'Action non autorisée.'], 403);
        }

        if ($tournament->getStatus() !== 'open') {
            return $this->json(['success' => false, 'message' => 'Inscriptions fermées.'], 400);
        }

        if (count($tournament->getTeams()) >= $tournament->getMaxTeams()) {
            return $this->json(['success' => false, 'message' => 'Tournoi complet.'], 400);
        }

        if ($tournament->getTeams()->contains($team)) {
            return $this->json(['success' => false, 'message' => 'Équipe déjà inscrite.'], 400);
        }

        $now = new \DateTimeImmutable();
        if ($now > $tournament->getRegistrationDeadline()) {
            return $this->json(['success' => false, 'message' => 'Délai dépassé.'], 400);
        }

        try {
            $tournament->addTeam($team);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Inscription réussie! 🎉']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/tournament/{id}/leave', name: 'app_tournament_leave', methods: ['POST'])]
    public function leaveTournament(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isCsrfTokenValid('leave_tournament_' . $tournament->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
        }

        $user = $this->getUser();
        $removed = false;
        foreach ($tournament->getTeams() as $team) {
            if ($team->getCaptain() === $user) {
                $tournament->removeTeam($team);
                $removed = true;
                break;
            }
        }

        if ($removed) {
            $em->flush();
            $this->addFlash('success', 'Tournoi quitté.');
        } else {
            $this->addFlash('error', 'Équipe non trouvée.');
        }

        return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
    }
}