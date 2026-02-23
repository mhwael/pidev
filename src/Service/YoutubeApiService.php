<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YoutubeApiService
{
    private $client;
    // 1. This is your "Passport" to Google's servers
    private $apiKey = 'AIzaSyCQlFNlxRG-eUWQOPPTpTX5y6HtGvim2IQ'; 

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getThumbnailFromApi(string $videoId): ?string
    {
        // 2. This is the REST API URL. We are asking Google for the "snippet" (info) of the video.
        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$videoId}&key={$this->apiKey}";

        // 3. We send a "GET" request to the API
        $response = $this->client->request('GET', $url);
        
        // 4. We convert the JSON response into a PHP array so we can read it
        $data = $response->toArray();

        // 5. We dig into the JSON data to find the 'thumbnails' object
        if (!empty($data['items'])) {
            $thumbnails = $data['items'][0]['snippet']['thumbnails'];
            
            // We pick 'maxres' (high quality) if it exists, otherwise 'high'
            return $thumbnails['maxres']['url'] ?? $thumbnails['high']['url'] ?? null;
        }

        return null;
    }
}