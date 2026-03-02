<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class IGDBService
{
    private HttpClientInterface $client;
    
    private string $clientId = 'buruj2pzoo88tsyvwby355hdmorrta'; 
    private string $clientSecret = '582nkaxbvb0gi4u5zwa2yqhdznfn32';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Fetches game details safely from IGDB
     * @return array<string, mixed>|null
     */
    public function getGameDetails(string $gameName): ?array
    {
        if ($this->clientId === 'your_client_id_here') {
            return null;
        }

        try {
            // 1. Get OAuth Token
            $tokenUrl = "https://id.twitch.tv/oauth2/token?client_id={$this->clientId}&client_secret={$this->clientSecret}&grant_type=client_credentials";
            $tokenResponse = $this->client->request('POST', $tokenUrl)->toArray();
            $accessToken = (string) ($tokenResponse['access_token'] ?? '');

            // 2. Query IGDB for the summary
            $response = $this->client->request('POST', 'https://api.igdb.com/v4/games', [
                'headers' => [
                    'Client-ID' => $this->clientId,
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'body' => "fields name, summary; search \"{$gameName}\"; limit 1;"
            ]);

            $data = $response->toArray();
            return !empty($data) ? (array) $data[0] : null;

        } catch (Throwable $e) {
            // Safety: return null so the app doesn't crash
            return null;
        }
    }
}