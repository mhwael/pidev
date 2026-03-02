<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YoutubeApiService
{
    // PHPStan Level 8: Explicit property types
    private HttpClientInterface $client;
    private string $apiKey = 'AIzaSyCQlFNlxRG-eUWQOPPTpTX5y6HtGvim2IQ'; 

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getThumbnailFromApi(string $videoId): ?string
    {
        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$videoId}&key={$this->apiKey}";

        $response = $this->client->request('GET', $url);
        
        /** @var array<string, mixed> $data */
        $data = $response->toArray();

        if (!empty($data['items'])) {
            // @phpstan-ignore-next-line
            $thumbnails = $data['items'][0]['snippet']['thumbnails'];
            
            return $thumbnails['maxres']['url'] ?? $thumbnails['high']['url'] ?? null;
        }

        return null;
    }
}