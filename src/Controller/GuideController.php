<?php

namespace App\Controller;

use App\Entity\Guide;
use App\Form\GuideType;
use App\Repository\GuideRepository;
use App\Service\YoutubeApiService;
use App\Service\YoutubeHelper;
use App\Service\DiscordService; // ✨ Added
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/guide')]
final class GuideController extends AbstractController
{
    #[Route(name: 'app_guide_index', methods: ['GET'])]
    public function index(GuideRepository $guideRepository): Response
    {
        return $this->render('guide/index.html.twig', [
            'guides' => $guideRepository->findAll(),
            'stats' => [
                'total' => $guideRepository->count([]),
                'easy' => $guideRepository->count(['difficulty' => 'Easy']),
                'medium' => $guideRepository->count(['difficulty' => 'Medium']),
                'hard' => $guideRepository->count(['difficulty' => 'Hard'])
            ]
        ]);
    }

    #[Route('/new', name: 'app_guide_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        YoutubeApiService $apiService,
        YoutubeHelper $ytHelper,
        DiscordService $discordService // ✨ Injected
    ): Response {
        $guide = new Guide();
        $form = $this->createForm(GuideType::class, $guide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Handle Main Cover Image
            $imageFile = $form->get('coverImage')->getData();
            if ($imageFile) {
                $newFilename = $this->uploadFile($imageFile, $slugger);
                $guide->setCoverImage($newFilename);
            }

            // 2. Handle Step Images & YouTube API
            foreach ($form->get('guideSteps') as $stepForm) {
                $step = $stepForm->getData();
                $stepImageFile = $stepForm->get('image')->getData(); 
                
                if ($stepImageFile) {
                    $stepFilename = $this->uploadFile($stepImageFile, $slugger);
                    $step->setImage($stepFilename);
                } elseif ($step->getVideoUrl()) { 
                    $videoId = $ytHelper->getYoutubeId($step->getVideoUrl());
                    if ($videoId) {
                        $apiUrl = $apiService->getThumbnailFromApi($videoId);
                        if ($apiUrl) {
                            $step->setImage($apiUrl);
                        }
                    }
                }
            }

            $entityManager->persist($guide);
            $entityManager->flush();

            // 🚀 DISCORD NOTIFICATION
            // We trigger this only once the full Guide is successfully saved
            $discordService->sendGuideNotification($guide);

            return $this->redirectToRoute('app_guide_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('guide/new.html.twig', [
            'guide' => $guide,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Guide $guide): Response
    {
        return $this->render('guide/show.html.twig', [
            'guide' => $guide,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_guide_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request, 
        Guide $guide, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $slugger,
        YoutubeApiService $apiService,
        YoutubeHelper $ytHelper
    ): Response {
        $form = $this->createForm(GuideType::class, $guide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('coverImage')->getData();
            if ($imageFile) {
                $newFilename = $this->uploadFile($imageFile, $slugger);
                $guide->setCoverImage($newFilename);
            }

            foreach ($form->get('guideSteps') as $stepForm) {
                $step = $stepForm->getData();
                $stepImageFile = $stepForm->get('image')->getData();
                
                if ($stepImageFile) {
                    $stepFilename = $this->uploadFile($stepImageFile, $slugger);
                    $step->setImage($stepFilename);
                } elseif ($step->getVideoUrl()) {
                    $videoId = $ytHelper->getYoutubeId($step->getVideoUrl());
                    if ($videoId) {
                        $apiUrl = $apiService->getThumbnailFromApi($videoId);
                        if ($apiUrl) {
                            $step->setImage($apiUrl);
                        }
                    }
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_guide_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('guide/edit.html.twig', [
            'guide' => $guide,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_guide_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Guide $guide, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$guide->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($guide);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_guide_index', [], Response::HTTP_SEE_OTHER);
    }

    private function uploadFile($file, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/guide',
                $newFilename
            );
        } catch (FileException $e) {
            // Handle error
        }

        return $newFilename;
    }
}