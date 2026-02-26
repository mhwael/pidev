<?php

namespace App\Controller;

use App\Entity\ContentRequest;
use App\Entity\Guide;
use App\Repository\GuideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AssistantController extends AbstractController
{
    #[Route('/api/ask-assistant', name: 'api_ask_assistant', methods: ['POST'])]
    public function askAssistant(
        Request $request,
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        GuideRepository $guideRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userQuery = $data['query'] ?? '';

        if (empty($userQuery)) {
            return new JsonResponse(['error' => 'Please ask a question!'], 400);
        }

        $cleanQuery = preg_replace('/[^a-zA-Z0-9 ]/', '', $userQuery);

        $masterPrompt = "Extract 2 or 3 main search keywords from this text " . $cleanQuery . " Return ONLY the keywords separated by spaces with no punctuation";
        $url = 'https://text.pollinations.ai/' . rawurlencode($masterPrompt);

        try {
            $response = $httpClient->request('GET', $url, ['timeout' => 15]);
            $keywords = trim($response->getContent());
            $cleanKeywords = substr($keywords, 0, 255);

            $keywordArray = array_filter(explode(' ', $cleanKeywords));
            
            $qb = $guideRepository->createQueryBuilder('g');
            
            foreach ($keywordArray as $index => $word) {
                $qb->orWhere('g.title LIKE :word' . $index)
                   ->orWhere('g.description LIKE :word' . $index)
                   ->setParameter('word' . $index, '%' . $word . '%');
            }

            $qb->setMaxResults(3);
            $realGuidesFromDb = $qb->getQuery()->getResult();

            $guidesFound = []; 

            foreach ($realGuidesFromDb as $guide) {
                // Safely get the game name if it exists
                $gameName = $guide->getGame() ? $guide->getGame()->getName() : 'Unknown Game';

                $guidesFound[] = [
                    'title' => $guide->getTitle(),
                    'description' => $guide->getDescription(),
                    'coverImage' => $guide->getCoverImage(), // Send the image!
                    'gameName' => $gameName,                 // Send the game!
                    'difficulty' => $guide->getDifficulty(), // Send the difficulty!
                    // FIXED: Changed to front_guide_show so it stays on the frontend!
                    'url' => $this->generateUrl('front_guide_show', ['id' => $guide->getId()]) 
                ];
            }

            if (count($guidesFound) > 0) {
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'I found some guides in the database that perfectly match your problem!',
                    'keywords_used' => $cleanKeywords,
                    'data' => $guidesFound
                ]);
            } else {
                $contentRequest = new ContentRequest();
                $contentRequest->setUserQuery(substr($userQuery, 0, 255)); 
                $contentRequest->setExtractedKeywords($cleanKeywords);
                $contentRequest->setStatus('pending');

                $entityManager->persist($contentRequest);
                $entityManager->flush();

                return new JsonResponse([
                    'status' => 'wishlist_added',
                    'message' => "We don't have a guide for this yet, but I just logged a request for our Admins!",
                    'keywords_extracted' => $cleanKeywords
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'AI Routing failed: ' . $e->getMessage()], 500);
        }
    }
}