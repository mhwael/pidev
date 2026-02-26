<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DrillController extends AbstractController
{
    #[Route('/api/generate-drill', name: 'api_generate_drill', methods: ['POST'])]
    public function generateDrill(Request $request, HttpClientInterface $httpClient): Response
    {
        $data = json_decode($request->getContent(), true);
        $stepText = $data['step_text'] ?? 'Click the targets.';

        // The exact prompt that worked for your Aim Trainer
        $masterPrompt = "You are an expert JavaScript game developer. Create a 2D HTML5 Canvas mini-game based strictly on this instruction: '" . $stepText . "'. STRICT RULES: 1. Return ONLY raw HTML code. Do NOT wrap the code in markdown blocks. 2. Output MUST start with HTML DOCTYPE. 3. The game must be a single file (CSS and JS inside the HTML). 4. Use a <canvas> element of exactly 400x300 pixels. 5. Keep graphics primitive: Use simple colored shapes. No external images. 6. Make sure the JavaScript runs automatically on load.";

        // No experimental model tags, just the clean URL that worked for you
        $baseUrl = 'https://' . 'text.pollinations.ai/';
        $url = $baseUrl . rawurlencode($masterPrompt);
        
        try {
            $response = $httpClient->request('GET', $url, [
                'timeout' => 30, // Gives the API plenty of time to write the game
            ]);
            
            $rawContent = $response->getContent();
            
            // The Regex Shield that successfully protected your HTML
            if (preg_match('/<!DOCTYPE html>.*<\/html>/is', $rawContent, $matches)) {
                $cleanHtml = $matches[0];
            } else {
                $cleanHtml = str_replace(['```html', '```'], '', $rawContent); 
            }
            
            return new Response($cleanHtml);
        } catch (\Exception $e) {
            return new Response('Error generating game: ' . $e->getMessage(), 500);
        }
    }
}