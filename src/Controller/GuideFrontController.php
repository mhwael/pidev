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
// ✨ NEW: Import the HTTP Client so Symfony can talk to Python
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    // ✨ NEW: Added HttpClientInterface $client here!
    #[Route('/guides/{id}', name: 'front_guide_show', methods: ['GET', 'POST'])]
    public function show(Guide $guide, Request $request, EntityManagerInterface $em, HttpClientInterface $client): Response
    {
        $rating = new GuideRating();
        $form = $this->createForm(FrontGuideRatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'You must be logged in to rate a guide.');
                return $this->redirectToRoute('app_login');
            }

            // 🚀 THE PRO WAY: Call your Python Microservice!
            $commentText = $rating->getComment() ?? "";
            
            try {
                // Send the text to Python
                $response = $client->request('POST', 'http://127.0.0.1:8001/api/predict', [
                    'json' => ['text' => $commentText]
                ]);

                // Read Python's answer
                $aiData = $response->toArray();
                $prediction = $aiData['sentiment']; // "HAPPY", "ANGRY", or "NEUTRAL"
                $score = $aiData['score'];

                // Create a cool message for the user
                if ($prediction === 'HAPPY') {
                    $aiMessage = "🤖 AI Analysis: We predict you are HAPPY (Score: {$score})! Glad you liked it.";
                } elseif ($prediction === 'ANGRY') {
                    $aiMessage = "🤖 AI Analysis: We predict you are FRUSTRATED (Score: {$score}). We will review this guide!";
                } else {
                    $aiMessage = "🤖 AI Analysis: Neutral feedback received.";
                }

            } catch (\Exception $e) {
                // If the Python server is off, don't crash the website!
                $aiMessage = "🤖 AI is currently sleeping, but your review was posted!";
            }

            // Save everything to the database
            $rating->setUser($user);
            $rating->setGuide($guide);
            $rating->setCreatedAt(new \DateTimeImmutable());

            $em->persist($rating);
            $em->flush();

            // Show the AI message!
            $this->addFlash('success', 'Thank you! ' . $aiMessage);
            
            return $this->redirectToRoute('front_guide_show', ['id' => $guide->getId()]);
        }

        return $this->render('guide_front/show.html.twig', [
            'guide' => $guide,
            'ratingForm' => $form->createView(),
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

    #[Route('/ai-generator', name: 'front_ai_generator')]
    public function generateGuide(Request $request, HttpClientInterface $client): Response
    {
        $topic = $request->query->get('topic');
        $generatedText = null;

        if ($topic) {
            // We use a free, keyless text generation API wrapper for Large Language Models
            $prompt = "Write a short, professional, step-by-step gaming guide for: " . $topic . ". Make it look like a gaming wiki article.";
            
            try {
                // Send the prompt to the Generative AI
                $response = $client->request('GET', 'https://text.pollinations.ai/' . urlencode($prompt));
                $generatedText = $response->getContent(); // It returns pure text!
            } catch (\Exception $e) {
                $generatedText = "🤖 AI Error: The servers are currently overloaded. Please try again.";
            }
        }

        return $this->render('guide_front/ai_generator.html.twig', [
            'topic' => $topic,
            'generatedText' => $generatedText,
        ]);
    }
    }