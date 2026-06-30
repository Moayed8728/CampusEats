<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = trim((string) ($_ENV['GEMINI_API_KEY'] ?? ''));
        $this->model = trim((string) ($_ENV['GEMINI_MODEL'] ?? '')) ?: 'gemini-1.5-flash';

        if ($this->apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }
    }

    public function parseFoodPreferences(string $query): array
    {
        $prompt = $this->buildPrompt($query);
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($this->model),
            rawurlencode($this->apiKey)
        );

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = $this->postJson($url, $payload);
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return $this->normalizeParsedJson($text);
    }

    private function buildPrompt(string $query): string
    {
        return <<<PROMPT
You are a food-ordering preference parser for CampusEats.
Extract filters from the user's message.
Return JSON only.
No markdown.
No explanation.

Allowed categories:
Rice, Noodles, Drinks, Snacks

JSON schema:
{
  "budget": number|null,
  "category": "Rice"|"Noodles"|"Drinks"|"Snacks"|null,
  "halal": boolean|null,
  "vegetarian": boolean|null,
  "keyword": string|null
}

Rules:
- If user mentions "under RM10", budget = 10.
- If user mentions halal, halal = true.
- If user mentions vegetarian/veggie/veg, vegetarian = true.
- If user mentions rice, category = "Rice".
- If user mentions noodles, category = "Noodles".
- If user mentions drink/tea/milo/coffee, category = "Drinks".
- If unsure, use null.
- keyword should contain useful food words such as spicy, chicken, nasi, fried, tea, milo, coffee.

User message:
"{$query}"

Return JSON only.
PROMPT;
    }

    private function postJson(string $url, array $payload): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($raw === false || $status < 200 || $status >= 300) {
                throw new RuntimeException($error ?: 'Gemini API request failed.');
            }

            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new RuntimeException('Gemini API request failed.');
        }

        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('Gemini API request failed.');
        }

        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    private function normalizeParsedJson(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        try {
            $data = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            if (preg_match('/\{.*\}/s', $clean, $matches)) {
                $data = json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR);
            } else {
                throw $e;
            }
        }

        if (!is_array($data)) {
            throw new RuntimeException('Gemini response was not JSON.');
        }

        $allowedCategories = ['Rice', 'Noodles', 'Drinks', 'Snacks'];
        $category = $data['category'] ?? null;
        if (!in_array($category, $allowedCategories, true)) {
            $category = null;
        }

        return [
            'budget' => is_numeric($data['budget'] ?? null) ? (float) $data['budget'] : null,
            'category' => $category,
            'halal' => is_bool($data['halal'] ?? null) ? $data['halal'] : null,
            'vegetarian' => is_bool($data['vegetarian'] ?? null) ? $data['vegetarian'] : null,
            'keyword' => is_string($data['keyword'] ?? null) && trim($data['keyword']) !== ''
                ? trim($data['keyword'])
                : null,
        ];
    }
}
