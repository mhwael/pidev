<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiClient
{
    public function __construct(
        private HttpClientInterface $http,
        private string $apiKey,
        private string $model = 'gemini-2.0-flash'
    ) {}

    /**
     * @param list<array{role:string, content:string}> $messages
     * @return array{status:int, text:string, raw: array<mixed>}
     */
    public function generateText(string $systemInstruction, array $messages, int $timeoutSeconds = 15): array
    {
        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'];
            $role = ($role === 'assistant') ? 'model' : (($role === 'system') ? 'user' : $role);

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => (string) $msg['content']]
                ]
            ];
        }

        $body = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
            ],
        ];

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($this->model),
            rawurlencode($this->apiKey)
        );

        $res = $this->http->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $body,
            'timeout' => $timeoutSeconds,
        ]);

        $status = $res->getStatusCode();
        $data = $res->toArray(false);

        $text = '';
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $p) {
            if (isset($p['text'])) {
                $text .= (string) $p['text'];
            }
        }

        return [
            'status' => (int) $status,
            'text' => trim($text),
            'raw' => $data,
        ];
    }
}