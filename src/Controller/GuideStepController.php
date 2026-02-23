<?php

namespace App\Controller;

use App\Entity\GuideStep;
use App\Form\GuideStepType;
use App\Repository\GameRepository;
use App\Repository\GuideStepRepository;
use App\Service\YoutubeApiService; // ✨ Added
use App\Service\YoutubeHelper;     // ✨ Added
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/guide/step')]
final class GuideStepController extends AbstractController
{
    #[Route(name: 'app_guide_step_index', methods: ['GET'])]
    public function index(Request $request, GuideStepRepository $guideStepRepository, GameRepository $gameRepository): Response
    {
        $selectedGameId = $request->query->get('game');
        $games = $gameRepository->findAll();

        $qb = $guideStepRepository->createQueryBuilder('s')
            ->join('s.guide', 'g')
            ->addSelect('g');

        if ($selectedGameId) {
            $qb->andWhere('g.game = :gameId')
               ->setParameter('gameId', $selectedGameId);
        }

        $qb->orderBy('g.title', 'ASC')
           ->addOrderBy('s.stepOrder', 'ASC');

        return $this->render('guide_step/index.html.twig', [
            'guide_steps' => $qb->getQuery()->getResult(),
            'games' => $games,
            'selected_game' => $selectedGameId,
        ]);
    }

    #[Route('/new', name: 'app_guide_step_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        YoutubeApiService $apiService, // ✨ Injected
        YoutubeHelper $ytHelper        // ✨ Injected
    ): Response {
        $guideStep = new GuideStep();
        $form = $this->createForm(GuideStepType::class, $guideStep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // ✨ API LOGIC: Auto-fetch thumbnail if video URL is present
            if ($guideStep->getVideoUrl() && !$guideStep->getImage()) {
                $videoId = $ytHelper->getYoutubeId($guideStep->getVideoUrl());
                if ($videoId) {
                    $thumbnailUrl = $apiService->getThumbnailFromApi($videoId);
                    if ($thumbnailUrl) {
                        $guideStep->setImage($thumbnailUrl);
                    }
                }
            }

            $entityManager->persist($guideStep);
            $entityManager->flush();

            return $this->redirectToRoute('app_guide_step_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('guide_step/new.html.twig', [
            'guide_step' => $guideStep,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_step_show', methods: ['GET'])]
    public function show(GuideStep $guideStep): Response
    {
        return $this->render('guide_step/show.html.twig', [
            'guide_step' => $guideStep,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_guide_step_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        GuideStep $guideStep, 
        EntityManagerInterface $entityManager,
        YoutubeApiService $apiService, // ✨ Injected
        YoutubeHelper $ytHelper        // ✨ Injected
    ): Response {
        $form = $this->createForm(GuideStepType::class, $guideStep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // ✨ API LOGIC: Update thumbnail if video changed or image is missing
            if ($guideStep->getVideoUrl()) {
                $videoId = $ytHelper->getYoutubeId($guideStep->getVideoUrl());
                if ($videoId) {
                    $thumbnailUrl = $apiService->getThumbnailFromApi($videoId);
                    if ($thumbnailUrl) {
                        $guideStep->setImage($thumbnailUrl);
                    }
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_guide_step_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('guide_step/edit.html.twig', [
            'guide_step' => $guideStep,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_step_delete', methods: ['POST'])]
    public function delete(Request $request, GuideStep $guideStep, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$guideStep->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($guideStep);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_guide_step_index', [], Response::HTTP_SEE_OTHER);
    }
}