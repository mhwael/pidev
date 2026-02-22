<?php

namespace App\Controller;

use App\Entity\Guide;
use App\Entity\Game;
use App\Entity\GuideRating;
use App\Form\FrontGuideRatingType;
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
        // 1. Create a fresh Rating object
        $rating = new GuideRating();

        // 2. Create the Form (FrontGuideRatingType)
        $form = $this->createForm(FrontGuideRatingType::class, $rating);
        $form->handleRequest($request);

        // 3. Handle Form Submission
        if ($form->isSubmitted() && $form->isValid()) {
            
            $user = $this->getUser();
            
            // Security Check
            if (!$user) {
                $this->addFlash('error', 'You must be logged in to rate a guide.');
                return $this->redirectToRoute('app_login');
            }

            // ✨ Set the missing data automatically
            $rating->setUser($user);
            $rating->setGuide($guide); // Link to the current guide
            $rating->setCreatedAt(new \DateTimeImmutable());

            $em->persist($rating);
            $em->flush();

            $this->addFlash('success', 'Thank you! Your feedback has been posted.');
            
            // Redirect to avoid form re-submission
            return $this->redirectToRoute('front_guide_show', ['id' => $guide->getId()]);
        }

        // 4. Render the page with the form view
        return $this->render('guide_front/show.html.twig', [
            'guide' => $guide,
            'ratingForm' => $form->createView(), // 🚀 THIS LINE FIXES YOUR ERROR
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