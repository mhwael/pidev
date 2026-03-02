<?php

namespace App\Controller\Api;

use App\Service\LlmChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request, LlmChatbotService $bot): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            return $this->json([
                'ok' => false,
                'reply' => 'Message is empty.',
                'cards' => [],
                'suggestions' => [],
            ], 400);
        }

        $out = $bot->chat($message);

        return $this->json([
            'ok' => true,
            'reply' => $out['reply'],
            'cards' => $out['cards'],
            'suggestions' => $out['suggestions'],
        ]);
    }
}