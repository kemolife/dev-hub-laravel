<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\OllamaUnavailableException;
use App\Services\OllamaClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OllamaClientTest extends TestCase
{
    #[Test]
    public function it_throws_ollama_unavailable_exception_on_connection_failure(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $client = new OllamaClient('http://localhost:11434', 'llama3.2');

        $this->expectException(OllamaUnavailableException::class);

        iterator_to_array($client->chat('Explain this text'));
    }

    #[Test]
    public function it_sends_correct_payload_to_ollama(): void
    {
        Http::fake([
            'localhost:11434/api/chat' => Http::response(
                implode("\n", [
                    json_encode(['message' => ['content' => 'Hello '], 'done' => false]),
                    json_encode(['message' => ['content' => 'world'], 'done' => true]),
                ]),
                200,
            ),
        ]);

        $client = new OllamaClient('http://localhost:11434', 'llama3.2');

        $chunks = iterator_to_array($client->chat('Test prompt'));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['model'] === 'llama3.2'
                && $body['stream'] === true
                && $body['messages'][0]['content'] === 'Test prompt'
                && $body['messages'][0]['role'] === 'user';
        });

        $this->assertSame(['Hello ', 'world'], $chunks);
    }
}
