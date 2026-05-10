<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OllamaUnavailableException;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OllamaClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $model,
    ) {}

    /**
     * @param  array<array{role: string, content: string}>  $history
     * @return Generator<int, string>
     */
    public function chat(string $prompt, array $history = []): Generator
    {
        $messages = array_merge($history, [['role' => 'user', 'content' => $prompt]]);

        try {
            $response = Http::withOptions(['timeout' => 120, 'connect_timeout' => 5])
                ->post($this->baseUrl.'/api/chat', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => true,
                ]);
        } catch (ConnectionException $e) {
            throw new OllamaUnavailableException($e->getMessage(), previous: $e);
        }

        yield from $this->parseStreamBody($response->body());
    }

    /**
     * @return Generator<int, string>
     */
    private function parseStreamBody(string $body): Generator
    {
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            $content = $decoded['message']['content'] ?? '';

            if ($content !== '') {
                yield $content;
            }

            if ($decoded['done'] ?? false) {
                break;
            }
        }
    }
}
