<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * Get current user's teams as captain (AJAX endpoint)
     */
    #[Route('/api/user/teams', name: 'app_user_teams', methods: ['GET'])]
    public function getUserTeams(TeamRepository $teamRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        
        // Get teams where user is captain
        $userTeams = $teamRepository->findBy(['captain' => $user]);
        
        // Format teams for JSON response
        $teams = [];
        foreach ($userTeams as $team) {
            $teams[] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'members' => count($team->getMembers()),
                'elo' => $team->getEloRating(),
            ];
        }
        
        return $this->json([
            'teams' => $teams
        ]);
    }
}