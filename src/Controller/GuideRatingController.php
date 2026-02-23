<?php

namespace App\Controller;

use App\Entity\GuideRating;
use App\Form\GuideRatingType;
use App\Repository\GuideRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/guide/rating')]
final class GuideRatingController extends AbstractController
{
    #[Route(name: 'app_guide_rating_index', methods: ['GET'])]
    public function index(GuideRatingRepository $guideRatingRepository): Response
    {
        return $this->render('guide_rating/index.html.twig', [
            'guide_ratings' => $guideRatingRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_guide_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $guideRating = new GuideRating();
        $form = $this->createForm(GuideRatingType::class, $guideRating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // ✨ AUTOMATED FIELDS (Required by your Entity)
            // 1. Set the current date/time
            $guideRating->setCreatedAt(new \DateTimeImmutable());

            // 2. Set the currently logged-in User
            // ⚠️ NOTE: You must be logged in for this to work!
            $user = $this->getUser();
            if ($user) {
                $guideRating->setUser($user);
            } else {
                // Optional: Redirect to login if not logged in
                $this->addFlash('danger', 'You must be logged in to rate a guide!');
                return $this->redirectToRoute('app_login'); 
            }

            $entityManager->persist($guideRating);
            $entityManager->flush();

            // ✨ SUCCESS MESSAGE: Only appears if validation (Bad Words check) passes
            $this->addFlash('success', 'Your rating has been posted successfully! 🌟');

            return $this->redirectToRoute('app_guide_rating_index', [], Response::HTTP_SEE_OTHER);
        }
        
        // ⚠️ ERROR MESSAGE: Helps users know why the save failed (e.g., Bad Words)
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'There were errors in your form. Please check your comments for inappropriate language.');
        }

        return $this->render('guide_rating/new.html.twig', [
            'guide_rating' => $guideRating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_rating_show', methods: ['GET'])]
    public function show(GuideRating $guideRating): Response
    {
        return $this->render('guide_rating/show.html.twig', [
            'guide_rating' => $guideRating,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_guide_rating_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GuideRating $guideRating, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GuideRatingType::class, $guideRating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // ✨ SUCCESS MESSAGE
            $this->addFlash('success', 'Rating updated successfully!');

            return $this->redirectToRoute('app_guide_rating_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('guide_rating/edit.html.twig', [
            'guide_rating' => $guideRating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_rating_delete', methods: ['POST'])]
    public function delete(Request $request, GuideRating $guideRating, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$guideRating->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($guideRating);
            $entityManager->flush();
            
            // ✨ SUCCESS MESSAGE
            $this->addFlash('warning', 'Rating deleted.');
        }

        return $this->redirectToRoute('app_guide_rating_index', [], Response::HTTP_SEE_OTHER);
    }
}