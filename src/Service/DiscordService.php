<?php

namespace App\Service;

use App\Entity\Guide;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordService
{
    // PHPStan Level 8: Always specify the type for properties
    private HttpClientInterface $httpClient;
    
    // PHPStan Level 8: Specify string type
    private string $webhookUrl = 'https://discord.com/api/webhooks/1472769054153637970/My16tinKswmdJW_2u-kcNDM8JCmgPfqv6a-UU4SFHCwqcuPB0AI7Ufp4UCdL7A1CTG-2';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function sendGuideNotification(Guide $guide): void
    {
        // PHPStan Level 8: Handle potential null values
        $game = $guide->getGame();
        $gameName = ($game !== null) ? $game->getName() : 'Unknown Game';

        // Determine the image to show in Discord
        $coverImage = $guide->getCoverImage();
        $imageUrl = ($coverImage !== null) 
            ? 'https://your-domain.com/uploads/guide/' . $coverImage 
            : 'https://your-domain.com/default-preview.jpg';

        $this->httpClient->request('POST', $this->webhookUrl, [
            'json' => [
                'embeds' => [
                    [
                        'title' => "🚀 New Guide: " . $guide->getTitle(),
                        'description' => $guide->getDescription(),
                        'color' => 16711772, // Pink (#ff005c)
                        'fields' => [
                            ['name' => 'Game', 'value' => $gameName, 'inline' => true],
                            ['name' => 'Difficulty', 'value' => $guide->getDifficulty(), 'inline' => true],
                        ],
                        'image' => ['url' => $imageUrl],
                    ]
                ]
            ]
        ]);
    }
}