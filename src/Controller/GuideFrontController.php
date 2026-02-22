<?php

namespace App\Controller;

use App\Entity\Guide;
use App\Entity\Game;
use App\Entity\GuideRating;
use App\Repository\GuideRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')] 
class GuideFrontController extends AbstractController
{
    #[Route('/guides', name: 'front_guide_index')]
    public function index(GuideRepository $guideRepository): Response
    {
        return $this->render('guide_front/index.html.twig', [
            'guides' => $guideRepository->findAll(),
        ]);
    }

    #[Route('/guides/{id}', name: 'front_guide_show', methods: ['GET', 'POST'])]
    public function show(Guide $guide, Request $request, EntityManagerInterface $em): Response
    {
        // 1. Handle Guide Rating Submission
        if ($request->isMethod('POST')) {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'You must be logged in to rate a guide.');
                return $this->redirectToRoute('app_login');
            }

            $comment = $request->request->get('comment');
            $ratingVal = $request->request->get('ratingValue');

            if ($comment && $ratingVal) {
                $rating = new GuideRating();
                $rating->setGuide($guide);
                $rating->setUser($user);
                $rating->setComment($comment); // Matches your getComment/setComment
                $rating->setRatingValue((int)$ratingVal); // Matches your getRatingValue/setRatingValue
                $rating->setCreatedAt(new \DateTimeImmutable()); // Required based on your entity

                $em->persist($rating);
                $em->flush();

                $this->addFlash('success', 'Thank you! Your feedback has been posted.');
                return $this->redirectToRoute('front_guide_show', ['id' => $guide->getId()]);
            }
        }

        return $this->render('guide_front/show.html.twig', [
            'guide' => $guide,
        ]);
    }

    #[Route('/games', name: 'front_game_index')]
    public function gameIndex(GameRepository $gameRepository): Response
    {
        return $this->render('guide_front/game_index.html.twig', [
            'games' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/games/{id}', name: 'front_game_show')]
    public function gameShow(Game $game): Response
    {
        return $this->render('guide_front/game_show.html.twig', [
            'game' => $game,
            'guides' => $game->getGuides(), 
        ]);
    }
}