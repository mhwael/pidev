<?php

namespace App\Controller;

use App\Entity\GuideStep;
use App\Form\GuideStepType;
use App\Repository\GameRepository;
use App\Repository\GuideStepRepository;
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
        // 1. Get the game ID from the URL (e.g., ?game=1)
        $selectedGameId = $request->query->get('game');

        // 2. Load all games for the dropdown
        $games = $gameRepository->findAll();

        // 3. Create the query builder
        $qb = $guideStepRepository->createQueryBuilder('s')
            ->join('s.guide', 'g')
            ->addSelect('g'); // Select the guide data too (performance boost)

        // 4. If a game is selected, filter by it
        if ($selectedGameId) {
            $qb->andWhere('g.game = :gameId')
               ->setParameter('gameId', $selectedGameId);
        }

        // 5. CRITICAL: Sort by Guide Title first, then by Step Number
        // This ensures steps don't get mixed up visually
        $qb->orderBy('g.title', 'ASC')
           ->addOrderBy('s.stepOrder', 'ASC');

        return $this->render('guide_step/index.html.twig', [
            'guide_steps' => $qb->getQuery()->getResult(),
            'games' => $games,
            'selected_game' => $selectedGameId,
        ]);
    }

    #[Route('/new', name: 'app_guide_step_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $guideStep = new GuideStep();
        $form = $this->createForm(GuideStepType::class, $guideStep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function edit(Request $request, GuideStep $guideStep, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GuideStepType::class, $guideStep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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