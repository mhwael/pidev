<?php

namespace App\Service;

use App\Entity\Guide; // ✨ Required to pass the whole Guide
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordService
{
    private $httpClient;
    // Replace with your actual Discord Webhook URL
    private $webhookUrl = 'https://discord.com/api/webhooks/1472769054153637970/My16tinKswmdJW_2u-kcNDM8JCmgPfqv6a-UU4SFHCwqcuPB0AI7Ufp4UCdL7A1CTG-2';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    // ✨ This must match the name used in your Controller
    public function sendGuideNotification(Guide $guide): void
    {
        // Determine the image to show in Discord
        $imageUrl = $guide->getCoverImage() 
            ? 'https://your-domain.com/uploads/guide/' . $guide->getCoverImage() 
            : 'https://your-domain.com/default-preview.jpg';

        $this->httpClient->request('POST', $this->webhookUrl, [
            'json' => [
                'embeds' => [
                    [
                        'title' => "🚀 New Guide: " . $guide->getTitle(),
                        'description' => $guide->getDescription(),
                        'color' => 16711772, // Pink (#ff005c)
                        'fields' => [
                            ['name' => 'Game', 'value' => $guide->getGame()->getName(), 'inline' => true],
                            ['name' => 'Difficulty', 'value' => $guide->getDifficulty(), 'inline' => true],
                        ],
                        'image' => ['url' => $imageUrl],
                    ]
                ]
            ]
        ]);
    }
}