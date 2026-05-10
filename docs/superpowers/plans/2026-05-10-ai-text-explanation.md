# AI Text Explanation Feature Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow authenticated readers to select text in a published post, get a streaming AI (Ollama) explanation in a modal, and continue chatting in a persistent right-side panel or dedicated chat page. Conversations are public by default and rendered as inline highlights on the post body.

**Architecture:** Laravel `StreamedResponse` proxies Ollama's token stream directly to the React frontend via SSE. Conversations and messages persist in two new DB tables. Public conversations render as character-offset highlights over the post body. React uses plain `useState`/`useEffect`/`fetch` (no TanStack Query in this project).

**Tech Stack:** PHP 8.4, Laravel 13, Ollama HTTP API, React 19, TypeScript, Tailwind CSS v4, React Router v7

---

## File Map

**New backend files:**
- `app/Enums/MessageRole.php` — `User | Assistant` enum
- `database/migrations/XXXX_create_ai_conversations_table.php`
- `database/migrations/XXXX_create_ai_messages_table.php`
- `app/Models/AiConversation.php`
- `app/Models/AiMessage.php`
- `database/factories/AiConversationFactory.php`
- `database/factories/AiMessageFactory.php`
- `app/Exceptions/OllamaUnavailableException.php`
- `app/Services/OllamaClient.php`
- `app/Data/Ai/StartConversationData.php`
- `app/Data/Ai/ContinueConversationData.php`
- `app/Policies/AiConversationPolicy.php`
- `app/Actions/Ai/StartConversationAction.php`
- `app/Actions/Ai/ContinueConversationAction.php`
- `app/Http/Resources/Api/V1/AiConversationResource.php`
- `app/Http/Resources/Api/V1/AiMessageResource.php`
- `app/Http/Requests/Api/V1/StartConversationRequest.php`
- `app/Http/Requests/Api/V1/ContinueConversationRequest.php`
- `app/Http/Controllers/Api/V1/AiConversationController.php`

**Modified backend files:**
- `routes/api.php` — add 5 AI routes
- `.env.example` — add `OLLAMA_BASE_URL`, `OLLAMA_MODEL`

**New test files:**
- `tests/Unit/Services/OllamaClientTest.php`
- `tests/Unit/Actions/Ai/StartConversationActionTest.php`
- `tests/Feature/Api/V1/AiConversation/StartConversationTest.php`
- `tests/Feature/Api/V1/AiConversation/ContinueConversationTest.php`
- `tests/Feature/Api/V1/AiConversation/ConversationVisibilityTest.php`
- `tests/Feature/Api/V1/AiConversation/TogglePrivacyTest.php`
- `tests/Feature/Api/V1/AiConversation/OllamaUnavailableTest.php`
- `tests/Feature/Api/V1/AiConversation/RateLimitTest.php`

**New frontend files:**
- `frontend/src/features/ai/types.ts`
- `frontend/src/features/ai/api.ts`
- `frontend/src/features/ai/use-text-selection.ts`
- `frontend/src/features/ai/ask-ai-button.tsx`
- `frontend/src/features/ai/explanation-modal.tsx`
- `frontend/src/features/ai/chat-panel.tsx`
- `frontend/src/features/ai/conversation-highlights.tsx`
- `frontend/src/pages/conversation-page.tsx`

**Modified frontend files:**
- `frontend/src/features/post/prose-content.tsx` — wrap with selection + highlight support
- `frontend/src/pages/post-detail-page.tsx` — add chat panel + AskAI button
- `frontend/src/routes.tsx` — add `/conversations/:id` route
- `frontend/src/types/index.ts` — add `ApiConversation`, `ApiMessage`
- `frontend/src/lib/api.ts` — add `patch` method

---

## Task 1: Enum + Migrations + Models + Factories

**Files:**
- Create: `app/Enums/MessageRole.php`
- Create: `database/migrations/XXXX_create_ai_conversations_table.php`
- Create: `database/migrations/XXXX_create_ai_messages_table.php`
- Create: `app/Models/AiConversation.php`
- Create: `app/Models/AiMessage.php`
- Create: `database/factories/AiConversationFactory.php`
- Create: `database/factories/AiMessageFactory.php`

- [ ] **Step 1: Create MessageRole enum**

```bash
php artisan make:enum Enums/MessageRole --no-interaction
```

Replace generated content with:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
```

- [ ] **Step 2: Create migrations**

```bash
php artisan make:migration create_ai_conversations_table --no-interaction
php artisan make:migration create_ai_messages_table --no-interaction
```

Fill `create_ai_conversations_table`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->text('selected_text');
            $table->unsignedInteger('selection_start');
            $table->unsignedInteger('selection_end');
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index(['post_id', 'is_private']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
```

Fill `create_ai_messages_table`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->timestamp('created_at')->useCurrent();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
```

- [ ] **Step 3: Create AiConversation model**

```bash
php artisan make:model AiConversation --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AiConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'post_id',
    'selected_text',
    'selection_start',
    'selection_end',
    'is_private',
])]
class AiConversation extends Model
{
    /** @use HasFactory<AiConversationFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (AiConversation $conversation): void {
            $conversation->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'selection_start' => 'integer',
            'selection_end' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @return HasMany<AiMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id');
    }
}
```

- [ ] **Step 4: Create AiMessage model**

```bash
php artisan make:model AiMessage --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageRole;
use Database\Factories\AiMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'role', 'content'])]
class AiMessage extends Model
{
    /** @use HasFactory<AiMessageFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AiConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
```

- [ ] **Step 5: Create factories**

```bash
php artisan make:factory AiConversationFactory --model=AiConversation --no-interaction
php artisan make:factory AiMessageFactory --model=AiMessage --no-interaction
```

Fill `AiConversationFactory`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AiConversation>
 */
class AiConversationFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->numberBetween(0, 200);

        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'selected_text' => $this->faker->sentence(),
            'selection_start' => $start,
            'selection_end' => $start + $this->faker->numberBetween(10, 100),
            'is_private' => false,
        ];
    }

    public function private(): static
    {
        return $this->state(['is_private' => true]);
    }
}
```

Fill `AiMessageFactory`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageRole;
use App\Models\AiConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AiMessage>
 */
class AiMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => AiConversation::factory(),
            'role' => MessageRole::User,
            'content' => $this->faker->paragraph(),
        ];
    }

    public function assistant(): static
    {
        return $this->state(['role' => MessageRole::Assistant]);
    }
}
```

- [ ] **Step 6: Run migration**

```bash
php artisan migrate --no-interaction
```

Expected: 2 new tables created.

- [ ] **Step 7: Commit**

```bash
git add app/Enums/MessageRole.php app/Models/AiConversation.php app/Models/AiMessage.php \
  database/migrations/*ai_conversations* database/migrations/*ai_messages* \
  database/factories/AiConversationFactory.php database/factories/AiMessageFactory.php
git commit -m "feat: add AiConversation and AiMessage models with migrations"
```

---

## Task 2: OllamaClient Service + Unit Test

**Files:**
- Create: `app/Exceptions/OllamaUnavailableException.php`
- Create: `app/Services/OllamaClient.php`
- Create: `tests/Unit/Services/OllamaClientTest.php`
- Modify: `.env.example`

- [ ] **Step 1: Write the failing unit test**

```bash
php artisan make:test --phpunit --unit Services/OllamaClientTest --no-interaction
```

Replace with:

```php
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
                && $body['messages'][0]['content'] === 'Test prompt';
        });

        $this->assertSame(['Hello ', 'world'], $chunks);
    }
}
```

- [ ] **Step 2: Run test — verify it fails**

```bash
php artisan test --compact tests/Unit/Services/OllamaClientTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create exception**

```bash
php artisan make:exception OllamaUnavailableException --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class OllamaUnavailableException extends RuntimeException {}
```

- [ ] **Step 4: Create OllamaClient**

```bash
php artisan make:class Services/OllamaClient --no-interaction
```

Replace with:

```php
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
            $response = Http::withOptions(['stream' => true, 'timeout' => 120, 'connect_timeout' => 5])
                ->post($this->baseUrl.'/api/chat', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => true,
                ]);
        } catch (ConnectionException $e) {
            throw new OllamaUnavailableException($e->getMessage(), previous: $e);
        }

        $body = $response->toPsrResponse()->getBody();

        while (! $body->eof()) {
            $line = '';
            while (! $body->eof()) {
                $char = $body->read(1);
                if ($char === "\n") {
                    break;
                }
                $line .= $char;
            }

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
```

- [ ] **Step 5: Register OllamaClient in AppServiceProvider**

Open `app/Providers/AppServiceProvider.php`. Add to `register()`:

```php
$this->app->singleton(\App\Services\OllamaClient::class, fn () => new \App\Services\OllamaClient(
    baseUrl: (string) config('services.ollama.base_url', 'http://localhost:11434'),
    model: (string) config('services.ollama.model', 'llama3.2'),
));
```

Add to `config/services.php` under the existing entries:

```php
'ollama' => [
    'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'model' => env('OLLAMA_MODEL', 'llama3.2'),
],
```

Add to `.env.example`:

```
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2
```

- [ ] **Step 6: Run tests — verify they pass**

```bash
php artisan test --compact tests/Unit/Services/OllamaClientTest.php
```

Expected: 2 tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Exceptions/OllamaUnavailableException.php app/Services/OllamaClient.php \
  app/Providers/AppServiceProvider.php config/services.php .env.example \
  tests/Unit/Services/OllamaClientTest.php
git commit -m "feat: add OllamaClient service with streaming support"
```

---

## Task 3: DTOs

**Files:**
- Create: `app/Data/Ai/StartConversationData.php`
- Create: `app/Data/Ai/ContinueConversationData.php`

- [ ] **Step 1: Create StartConversationData**

```bash
php artisan make:class Data/Ai/StartConversationData --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Data\Ai;

readonly class StartConversationData
{
    public function __construct(
        public string $selectedText,
        public int $selectionStart,
        public int $selectionEnd,
    ) {}

    /** @param array{selected_text: string, selection_start: int, selection_end: int} $data */
    public static function from(array $data): self
    {
        return new self(
            selectedText: $data['selected_text'],
            selectionStart: $data['selection_start'],
            selectionEnd: $data['selection_end'],
        );
    }
}
```

- [ ] **Step 2: Create ContinueConversationData**

```bash
php artisan make:class Data/Ai/ContinueConversationData --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Data\Ai;

readonly class ContinueConversationData
{
    public function __construct(
        public string $content,
    ) {}

    /** @param array{content: string} $data */
    public static function from(array $data): self
    {
        return new self(content: $data['content']);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Data/Ai/
git commit -m "feat: add StartConversationData and ContinueConversationData DTOs"
```

---

## Task 4: AiConversationPolicy

**Files:**
- Create: `app/Policies/AiConversationPolicy.php`

- [ ] **Step 1: Create policy**

```bash
php artisan make:policy AiConversationPolicy --model=AiConversation --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

class AiConversationPolicy
{
    public function view(User $user, AiConversation $conversation): bool
    {
        return $conversation->user_id === $user->id || ! $conversation->is_private;
    }

    public function update(User $user, AiConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function addMessage(User $user, AiConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Policies/AiConversationPolicy.php
git commit -m "feat: add AiConversationPolicy"
```

---

## Task 5: Actions

**Files:**
- Create: `app/Actions/Ai/StartConversationAction.php`
- Create: `app/Actions/Ai/ContinueConversationAction.php`
- Create: `tests/Unit/Actions/Ai/StartConversationActionTest.php`

- [ ] **Step 1: Write failing test for StartConversationAction**

```bash
php artisan make:test --phpunit --unit Actions/Ai/StartConversationActionTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Ai;

use App\Actions\Ai\StartConversationAction;
use App\Data\Ai\StartConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartConversationActionTest extends TestCase
{
    #[Test]
    public function it_creates_conversation_and_user_message_then_returns_stream(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $data = new StartConversationData('some text', 10, 20);

        $fakeStream = (function (): Generator { yield 'Hello'; yield ' world'; })();

        $this->mock(OllamaClient::class, function (MockInterface $mock) use ($fakeStream): void {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn($fakeStream);
        });

        $action = app(StartConversationAction::class);
        [$conversation, $stream] = $action->execute($user, $post, $data);

        $this->assertInstanceOf(AiConversation::class, $conversation);
        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'selected_text' => 'some text',
            'selection_start' => 10,
            'selection_end' => 20,
            'is_private' => false,
        ]);
        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User->value,
            'content' => 'some text',
        ]);
        $this->assertSame($fakeStream, $stream);
    }
}
```

- [ ] **Step 2: Run test — verify it fails**

```bash
php artisan test --compact tests/Unit/Actions/Ai/StartConversationActionTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create StartConversationAction**

```bash
php artisan make:class Actions/Ai/StartConversationAction --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Data\Ai\StartConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;

class StartConversationAction
{
    public function __construct(private readonly OllamaClient $ollamaClient) {}

    /** @return array{0: AiConversation, 1: Generator} */
    public function execute(User $user, Post $post, StartConversationData $data): array
    {
        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'selected_text' => $data->selectedText,
            'selection_start' => $data->selectionStart,
            'selection_end' => $data->selectionEnd,
            'is_private' => false,
        ]);

        $conversation->messages()->create([
            'role' => MessageRole::User,
            'content' => $data->selectedText,
        ]);

        $stream = $this->ollamaClient->chat(
            "Please explain the following text in simple terms:\n\n\"{$data->selectedText}\""
        );

        return [$conversation, $stream];
    }
}
```

- [ ] **Step 4: Run test — verify it passes**

```bash
php artisan test --compact tests/Unit/Actions/Ai/StartConversationActionTest.php
```

Expected: 1 test passes.

- [ ] **Step 5: Create ContinueConversationAction**

```bash
php artisan make:class Actions/Ai/ContinueConversationAction --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Data\Ai\ContinueConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\OllamaClient;
use Generator;

class ContinueConversationAction
{
    public function __construct(private readonly OllamaClient $ollamaClient) {}

    /** @return Generator<int, string> */
    public function execute(AiConversation $conversation, ContinueConversationData $data): Generator
    {
        $conversation->messages()->create([
            'role' => MessageRole::User,
            'content' => $data->content,
        ]);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (AiMessage $message): array => [
                'role' => $message->role->value,
                'content' => $message->content,
            ])
            ->toArray();

        return $this->ollamaClient->chat($data->content, $history);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Actions/Ai/ tests/Unit/Actions/Ai/
git commit -m "feat: add StartConversationAction and ContinueConversationAction"
```

---

## Task 6: API Resources

**Files:**
- Create: `app/Http/Resources/Api/V1/AiConversationResource.php`
- Create: `app/Http/Resources/Api/V1/AiMessageResource.php`

- [ ] **Step 1: Create AiMessageResource**

```bash
php artisan make:resource Api/V1/AiMessageResource --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AiMessage $resource */
class AiMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'role' => $this->resource->role->value,
            'content' => $this->resource->content,
            'created_at' => $this->resource->created_at,
        ];
    }
}
```

- [ ] **Step 2: Create AiConversationResource**

```bash
php artisan make:resource Api/V1/AiConversationResource --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AiConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property AiConversation $resource */
class AiConversationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'selected_text' => $this->resource->selected_text,
            'selection_start' => $this->resource->selection_start,
            'selection_end' => $this->resource->selection_end,
            'is_private' => $this->resource->is_private,
            'owner_id' => $this->resource->user_id,
            'messages' => AiMessageResource::collection(
                $this->whenLoaded('messages', $this->resource->messages)
            ),
            'created_at' => $this->resource->created_at,
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Resources/Api/V1/AiConversationResource.php \
  app/Http/Resources/Api/V1/AiMessageResource.php
git commit -m "feat: add AiConversationResource and AiMessageResource"
```

---

## Task 7: Form Requests + Controller + Routes

**Files:**
- Create: `app/Http/Requests/Api/V1/StartConversationRequest.php`
- Create: `app/Http/Requests/Api/V1/ContinueConversationRequest.php`
- Create: `app/Http/Controllers/Api/V1/AiConversationController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Create form requests**

```bash
php artisan make:request Api/V1/StartConversationRequest --no-interaction
php artisan make:request Api/V1/ContinueConversationRequest --no-interaction
```

Fill `StartConversationRequest`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Data\Ai\StartConversationData;
use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'selected_text' => ['required', 'string', 'min:1', 'max:5000'],
            'selection_start' => ['required', 'integer', 'min:0'],
            'selection_end' => ['required', 'integer', 'min:0', 'gt:selection_start'],
        ];
    }

    public function toData(): StartConversationData
    {
        return StartConversationData::from($this->validated());
    }
}
```

Fill `ContinueConversationRequest`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Data\Ai\ContinueConversationData;
use Illuminate\Foundation\Http\FormRequest;

class ContinueConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    public function toData(): ContinueConversationData
    {
        return ContinueConversationData::from($this->validated());
    }
}
```

- [ ] **Step 2: Create controller**

```bash
php artisan make:controller Api/V1/AiConversationController --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ai\ContinueConversationAction;
use App\Actions\Ai\StartConversationAction;
use App\Enums\MessageRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContinueConversationRequest;
use App\Http\Requests\Api\V1\StartConversationRequest;
use App\Http\Resources\Api\V1\AiConversationResource;
use App\Models\AiConversation;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiConversationController extends Controller
{
    public function __construct(
        private readonly StartConversationAction $startConversationAction,
        private readonly ContinueConversationAction $continueConversationAction,
    ) {}

    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        $conversations = AiConversation::where('post_id', $post->id)
            ->where(function ($query) use ($request): void {
                $query->where('is_private', false)
                    ->orWhere('user_id', $request->user()->id);
            })
            ->with('messages')
            ->latest()
            ->get();

        return AiConversationResource::collection($conversations);
    }

    public function store(StartConversationRequest $request, Post $post): StreamedResponse
    {
        [$conversation, $stream] = $this->startConversationAction->execute(
            $request->user(),
            $post,
            $request->toData(),
        );

        return response()->stream(function () use ($conversation, $stream): void {
            $fullContent = '';

            foreach ($stream as $chunk) {
                $fullContent .= $chunk;
                echo 'data: '.json_encode(['content' => $chunk])."\n\n";
                ob_flush();
                flush();
            }

            $conversation->messages()->create([
                'role' => MessageRole::Assistant,
                'content' => $fullContent,
            ]);

            echo 'data: '.json_encode(['done' => true, 'conversation_id' => $conversation->public_id])."\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load('messages');

        return (new AiConversationResource($conversation))->response();
    }

    public function addMessage(ContinueConversationRequest $request, AiConversation $conversation): StreamedResponse
    {
        $this->authorize('addMessage', $conversation);

        $stream = $this->continueConversationAction->execute(
            $conversation,
            $request->toData(),
        );

        return response()->stream(function () use ($conversation, $stream): void {
            $fullContent = '';

            foreach ($stream as $chunk) {
                $fullContent .= $chunk;
                echo 'data: '.json_encode(['content' => $chunk])."\n\n";
                ob_flush();
                flush();
            }

            $conversation->messages()->create([
                'role' => MessageRole::Assistant,
                'content' => $fullContent,
            ]);

            echo 'data: '.json_encode(['done' => true])."\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function togglePrivacy(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->update(['is_private' => ! $conversation->is_private]);

        return (new AiConversationResource($conversation))->response();
    }
}
```

- [ ] **Step 3: Add routes**

Open `routes/api.php`. Add these imports at the top with the other imports:

```php
use App\Http\Controllers\Api\V1\AiConversationController;
```

Inside the `auth:sanctum` + `CheckNotSuspended` middleware group, add:

```php
// AI Conversations
Route::get('/posts/{post:slug}/conversations', [AiConversationController::class, 'index'])->middleware('throttle:60,1');
Route::post('/posts/{post:slug}/conversations', [AiConversationController::class, 'store'])->middleware('throttle:20,1');
Route::get('/conversations/{conversation:public_id}', [AiConversationController::class, 'show']);
Route::post('/conversations/{conversation:public_id}/messages', [AiConversationController::class, 'addMessage'])->middleware('throttle:20,1');
Route::patch('/conversations/{conversation:public_id}', [AiConversationController::class, 'togglePrivacy']);
```

- [ ] **Step 4: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Verify routes registered**

```bash
php artisan route:list --path=conversations --except-vendor
```

Expected: 5 routes listed.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/Api/V1/StartConversationRequest.php \
  app/Http/Requests/Api/V1/ContinueConversationRequest.php \
  app/Http/Controllers/Api/V1/AiConversationController.php \
  routes/api.php
git commit -m "feat: add AiConversationController with streaming endpoints"
```

---

## Task 8: Feature Tests

**Files:**
- Create: `tests/Feature/Api/V1/AiConversation/StartConversationTest.php`
- Create: `tests/Feature/Api/V1/AiConversation/ContinueConversationTest.php`
- Create: `tests/Feature/Api/V1/AiConversation/ConversationVisibilityTest.php`
- Create: `tests/Feature/Api/V1/AiConversation/TogglePrivacyTest.php`
- Create: `tests/Feature/Api/V1/AiConversation/OllamaUnavailableTest.php`
- Create: `tests/Feature/Api/V1/AiConversation/RateLimitTest.php`

- [ ] **Step 1: Create StartConversationTest**

```bash
php artisan make:test --phpunit Api/V1/AiConversation/StartConversationTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Enums\MessageRole;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartConversationTest extends TestCase
{
    private function mockOllama(array $chunks = ['Hello ', 'world']): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock) use ($chunks): void {
            $mock->shouldReceive('chat')->andReturn(
                (function () use ($chunks): Generator { foreach ($chunks as $c) { yield $c; } })()
            );
        });
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/conversations", [
            'selected_text' => 'some text',
            'selection_start' => 0,
            'selection_end' => 9,
        ])->assertUnauthorized();
    }

    #[Test]
    public function it_creates_conversation_and_streams_response(): void
    {
        $this->mockOllama(['Hello ', 'world']);
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'some text',
                'selection_start' => 0,
                'selection_end' => 9,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');

        $body = $response->getContent();
        $this->assertStringContainsString('data: {"content":"Hello "}', $body);
        $this->assertStringContainsString('data: {"content":"world"}', $body);
        $this->assertStringContainsString('"done":true', $body);

        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'selected_text' => 'some text',
        ]);
        $this->assertDatabaseHas('ai_messages', ['role' => MessageRole::User->value, 'content' => 'some text']);
        $this->assertDatabaseHas('ai_messages', ['role' => MessageRole::Assistant->value, 'content' => 'Hello world']);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selected_text', 'selection_start', 'selection_end']);
    }

    #[Test]
    public function it_validates_selection_end_greater_than_start(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'text',
                'selection_start' => 50,
                'selection_end' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selection_end']);
    }
}
```

- [ ] **Step 2: Create ContinueConversationTest**

```bash
php artisan make:test --phpunit Api/V1/AiConversation/ContinueConversationTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContinueConversationTest extends TestCase
{
    #[Test]
    public function owner_can_add_message_and_gets_streamed_reply(): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chat')->andReturn(
                (function (): Generator { yield 'Follow-up reply'; })()
            );
        });

        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->public_id}/messages", [
                'content' => 'Can you elaborate?',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');

        $body = $response->getContent();
        $this->assertStringContainsString('"content":"Follow-up reply"', $body);

        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User->value,
            'content' => 'Can you elaborate?',
        ]);
        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant->value,
            'content' => 'Follow-up reply',
        ]);
    }

    #[Test]
    public function non_owner_cannot_add_message(): void
    {
        $conversation = AiConversation::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->public_id}/messages", [
                'content' => 'Can you elaborate?',
            ])
            ->assertForbidden();
    }
}
```

- [ ] **Step 3: Create ConversationVisibilityTest**

```bash
php artisan make:test --phpunit Api/V1/AiConversation/ConversationVisibilityTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Models\AiConversation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationVisibilityTest extends TestCase
{
    #[Test]
    public function owner_can_view_private_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->private()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk();
    }

    #[Test]
    public function other_user_cannot_view_private_conversation(): void
    {
        $conversation = AiConversation::factory()->private()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertForbidden();
    }

    #[Test]
    public function other_user_can_view_public_conversation(): void
    {
        $conversation = AiConversation::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk();
    }

    #[Test]
    public function post_conversations_list_excludes_other_users_private_conversations(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $post = \App\Models\Post::factory()->published()->create();

        AiConversation::factory()->for($owner)->for($post)->create(['is_private' => false]);
        AiConversation::factory()->for($owner)->for($post)->private()->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/posts/{$post->slug}/conversations")
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }
}
```

- [ ] **Step 4: Create TogglePrivacyTest**

```bash
php artisan make:test --phpunit Api/V1/AiConversation/TogglePrivacyTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Models\AiConversation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TogglePrivacyTest extends TestCase
{
    #[Test]
    public function owner_can_toggle_conversation_to_private(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->create(['is_private' => false]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk()
            ->assertJsonPath('data.is_private', true);

        $this->assertDatabaseHas('ai_conversations', [
            'id' => $conversation->id,
            'is_private' => true,
        ]);
    }

    #[Test]
    public function non_owner_cannot_toggle_privacy(): void
    {
        $conversation = AiConversation::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->patchJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertForbidden();
    }
}
```

- [ ] **Step 5: Create OllamaUnavailableTest**

```bash
php artisan make:test --phpunit Api/V1/AiConversation/OllamaUnavailableTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Exceptions\OllamaUnavailableException;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OllamaUnavailableTest extends TestCase
{
    #[Test]
    public function it_returns_503_when_ollama_is_unavailable(): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chat')->andThrow(new OllamaUnavailableException('Connection refused'));
        });

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'some text',
                'selection_start' => 0,
                'selection_end' => 9,
            ])
            ->assertServiceUnavailable();
    }
}
```

Open `app/Exceptions/Handler.php` (or `bootstrap/app.php` exception handling) and register the exception:

In `bootstrap/app.php`, add inside `->withExceptions(function (Exceptions $exceptions) {`:

```php
$exceptions->render(function (\App\Exceptions\OllamaUnavailableException $e) {
    return response()->json(['message' => 'AI service is currently unavailable. Please try again later.'], 503);
});
```

- [ ] **Step 6: Create RateLimitTest**

```bash
php artisan make:test --phpunit Api/V1/AiConversation/RateLimitTest --no-interaction
```

Replace with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    #[Test]
    public function it_rate_limits_after_20_requests_per_minute(): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chat')->andReturn(
                (function (): Generator { yield 'ok'; })()
            );
        });

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                    'selected_text' => 'some text',
                    'selection_start' => 0,
                    'selection_end' => 9,
                ])
                ->assertStatus(200);
        }

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'some text',
                'selection_start' => 0,
                'selection_end' => 9,
            ])
            ->assertTooManyRequests();
    }
}
```

- [ ] **Step 7: Run all feature tests**

```bash
php artisan test --compact tests/Feature/Api/V1/AiConversation/ tests/Unit/
```

Expected: all pass.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add tests/Feature/Api/V1/AiConversation/ bootstrap/app.php
git commit -m "test: add feature and unit tests for AI conversation endpoints"
```

---

## Task 9: Frontend Types + API Client

**Files:**
- Modify: `frontend/src/types/index.ts`
- Modify: `frontend/src/lib/api.ts`
- Create: `frontend/src/features/ai/types.ts`
- Create: `frontend/src/features/ai/api.ts`

- [ ] **Step 1: Add types to `frontend/src/types/index.ts`**

Append to the end of the file:

```typescript
export interface ApiAiMessage {
  id: number;
  role: 'user' | 'assistant';
  content: string;
  created_at: string;
}

export interface ApiAiConversation {
  id: string;
  selected_text: string;
  selection_start: number;
  selection_end: number;
  is_private: boolean;
  owner_id: number;
  messages: ApiAiMessage[];
  created_at: string;
}
```

- [ ] **Step 2: Add `patch` to `frontend/src/lib/api.ts`**

Add after the `delete` line in the `api` object:

```typescript
  patch: <T>(path: string, token?: string) =>
    request<T>(path, { method: 'PATCH', token }),
```

- [ ] **Step 3: Create `frontend/src/features/ai/types.ts`**

```typescript
export type StreamChunk =
  | { type: 'content'; content: string }
  | { type: 'done'; conversationId?: string };
```

- [ ] **Step 4: Create `frontend/src/features/ai/api.ts`**

```typescript
import type { ApiAiConversation } from '../../types';
import type { StreamChunk } from './types';

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';

export async function fetchPostConversations(
  slug: string,
  token: string,
): Promise<ApiAiConversation[]> {
  const res = await fetch(`${BASE_URL}/posts/${slug}/conversations`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return (data.data ?? data) as ApiAiConversation[];
}

export async function fetchConversation(
  conversationId: string,
  token: string,
): Promise<ApiAiConversation> {
  const res = await fetch(`${BASE_URL}/conversations/${conversationId}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return (data.data ?? data) as ApiAiConversation;
}

export async function* startConversationStream(
  slug: string,
  selectedText: string,
  selectionStart: number,
  selectionEnd: number,
  token: string,
): AsyncGenerator<StreamChunk> {
  const res = await fetch(`${BASE_URL}/posts/${slug}/conversations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
      Accept: 'text/event-stream',
    },
    body: JSON.stringify({ selected_text: selectedText, selection_start: selectionStart, selection_end: selectionEnd }),
  });

  if (!res.ok || !res.body) throw new Error(`HTTP ${res.status}`);

  yield* parseEventStream(res.body);
}

export async function* continueConversationStream(
  conversationId: string,
  content: string,
  token: string,
): AsyncGenerator<StreamChunk> {
  const res = await fetch(`${BASE_URL}/conversations/${conversationId}/messages`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
      Accept: 'text/event-stream',
    },
    body: JSON.stringify({ content }),
  });

  if (!res.ok || !res.body) throw new Error(`HTTP ${res.status}`);

  yield* parseEventStream(res.body);
}

export async function toggleConversationPrivacy(
  conversationId: string,
  token: string,
): Promise<ApiAiConversation> {
  const res = await fetch(`${BASE_URL}/conversations/${conversationId}`, {
    method: 'PATCH',
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return (data.data ?? data) as ApiAiConversation;
}

async function* parseEventStream(body: ReadableStream<Uint8Array>): AsyncGenerator<StreamChunk> {
  const reader = body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    buffer += decoder.decode(value, { stream: true });

    const parts = buffer.split('\n\n');
    buffer = parts.pop() ?? '';

    for (const part of parts) {
      for (const line of part.split('\n')) {
        if (!line.startsWith('data: ')) continue;
        const json = JSON.parse(line.slice(6)) as Record<string, unknown>;
        if (typeof json.content === 'string') {
          yield { type: 'content', content: json.content };
        } else if (json.done === true) {
          yield { type: 'done', conversationId: typeof json.conversation_id === 'string' ? json.conversation_id : undefined };
        }
      }
    }
  }
}
```

- [ ] **Step 5: Commit**

```bash
git add frontend/src/types/index.ts frontend/src/lib/api.ts \
  frontend/src/features/ai/types.ts frontend/src/features/ai/api.ts
git commit -m "feat: add AI conversation TypeScript types and API client"
```

---

## Task 10: useTextSelection Hook + AskAiButton

**Files:**
- Create: `frontend/src/features/ai/use-text-selection.ts`
- Create: `frontend/src/features/ai/ask-ai-button.tsx`

- [ ] **Step 1: Create useTextSelection hook**

```typescript
// frontend/src/features/ai/use-text-selection.ts
import { useEffect, useState } from 'react';

export interface TextSelection {
  text: string;
  start: number;
  end: number;
  rect: DOMRect;
}

export function useTextSelection(containerRef: React.RefObject<HTMLElement | null>): TextSelection | null {
  const [selection, setSelection] = useState<TextSelection | null>(null);

  useEffect(() => {
    function handleSelectionChange() {
      const sel = window.getSelection();

      if (!sel || sel.isCollapsed || sel.toString().trim() === '') {
        setSelection(null);
        return;
      }

      const container = containerRef.current;
      if (!container) return;

      const range = sel.getRangeAt(0);
      if (!container.contains(range.commonAncestorContainer)) {
        setSelection(null);
        return;
      }

      const preRange = document.createRange();
      preRange.selectNodeContents(container);
      preRange.setEnd(range.startContainer, range.startOffset);
      const start = preRange.toString().length;
      const text = sel.toString();

      setSelection({
        text,
        start,
        end: start + text.length,
        rect: range.getBoundingClientRect(),
      });
    }

    document.addEventListener('selectionchange', handleSelectionChange);
    return () => document.removeEventListener('selectionchange', handleSelectionChange);
  }, [containerRef]);

  return selection;
}
```

- [ ] **Step 2: Create AskAiButton**

```typescript
// frontend/src/features/ai/ask-ai-button.tsx
import type { TextSelection } from './use-text-selection';

interface AskAiButtonProps {
  selection: TextSelection;
  onAsk: (selection: TextSelection) => void;
}

export function AskAiButton({ selection, onAsk }: AskAiButtonProps) {
  const top = selection.rect.top + window.scrollY - 40;
  const left = selection.rect.left + selection.rect.width / 2;

  return (
    <button
      onMouseDown={(e) => {
        e.preventDefault();
        onAsk(selection);
      }}
      style={{
        position: 'absolute',
        top,
        left,
        transform: 'translateX(-50%)',
        zIndex: 1000,
        padding: '4px 10px',
        fontSize: 13,
        fontFamily: 'var(--font-sans)',
        backgroundColor: 'var(--color-bg-inverse)',
        color: 'var(--color-text-inverse)',
        border: 'none',
        borderRadius: 'var(--radius-sm)',
        cursor: 'pointer',
        whiteSpace: 'nowrap',
      }}
    >
      Ask AI
    </button>
  );
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/ai/use-text-selection.ts \
  frontend/src/features/ai/ask-ai-button.tsx
git commit -m "feat: add useTextSelection hook and AskAiButton component"
```

---

## Task 11: ExplanationModal

**Files:**
- Create: `frontend/src/features/ai/explanation-modal.tsx`

- [ ] **Step 1: Create ExplanationModal**

```typescript
// frontend/src/features/ai/explanation-modal.tsx
import { useEffect, useRef, useState } from 'react';
import { startConversationStream } from './api';
import type { TextSelection } from './use-text-selection';

interface ExplanationModalProps {
  selection: TextSelection;
  postSlug: string;
  token: string;
  onClose: () => void;
  onOpenChat: (conversationId: string) => void;
}

export function ExplanationModal({ selection, postSlug, token, onClose, onOpenChat }: ExplanationModalProps) {
  const [content, setContent] = useState('');
  const [isDone, setIsDone] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [conversationId, setConversationId] = useState<string | null>(null);
  const abortedRef = useRef(false);

  useEffect(() => {
    abortedRef.current = false;
    setContent('');
    setIsDone(false);
    setError(null);

    async function stream() {
      try {
        const gen = startConversationStream(
          postSlug,
          selection.text,
          selection.start,
          selection.end,
          token,
        );
        for await (const chunk of gen) {
          if (abortedRef.current) break;
          if (chunk.type === 'content') {
            setContent((prev) => prev + chunk.content);
          } else if (chunk.type === 'done') {
            setConversationId(chunk.conversationId ?? null);
            setIsDone(true);
          }
        }
      } catch {
        if (!abortedRef.current) setError('AI service is unavailable. Please try again.');
      }
    }

    void stream();

    return () => { abortedRef.current = true; };
  }, [selection, postSlug, token]);

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 2000,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'rgba(0,0,0,0.4)',
      }}
      onClick={onClose}
    >
      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          border: '0.5px solid var(--color-border-primary)',
          borderRadius: 'var(--radius-lg)',
          padding: '24px',
          maxWidth: 560,
          width: '90%',
          maxHeight: '70vh',
          overflowY: 'auto',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <blockquote
          style={{
            borderLeft: '3px solid var(--color-border-secondary)',
            paddingLeft: 12,
            margin: '0 0 16px',
            color: 'var(--color-text-secondary)',
            fontSize: 14,
            fontStyle: 'italic',
          }}
        >
          {selection.text.length > 200 ? `${selection.text.slice(0, 200)}…` : selection.text}
        </blockquote>

        {error ? (
          <p style={{ color: 'var(--color-error, #e53e3e)', fontSize: 14 }}>{error}</p>
        ) : (
          <p style={{ fontSize: 15, lineHeight: 1.6, margin: 0, whiteSpace: 'pre-wrap' }}>
            {content}
            {!isDone && <span style={{ opacity: 0.5 }}>▍</span>}
          </p>
        )}

        <div style={{ display: 'flex', gap: 8, marginTop: 20, justifyContent: 'flex-end' }}>
          <button
            onClick={onClose}
            style={{
              padding: '6px 14px',
              fontSize: 13,
              border: '0.5px solid var(--color-border-primary)',
              borderRadius: 'var(--radius-sm)',
              backgroundColor: 'transparent',
              cursor: 'pointer',
              color: 'var(--color-text-primary)',
            }}
          >
            Close
          </button>
          {isDone && conversationId && (
            <button
              onClick={() => { onClose(); onOpenChat(conversationId); }}
              style={{
                padding: '6px 14px',
                fontSize: 13,
                border: 'none',
                borderRadius: 'var(--radius-sm)',
                backgroundColor: 'var(--color-bg-inverse)',
                color: 'var(--color-text-inverse)',
                cursor: 'pointer',
              }}
            >
              Continue chatting →
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/ai/explanation-modal.tsx
git commit -m "feat: add ExplanationModal with streaming SSE support"
```

---

## Task 12: ChatPanel

**Files:**
- Create: `frontend/src/features/ai/chat-panel.tsx`

- [ ] **Step 1: Create ChatPanel**

```typescript
// frontend/src/features/ai/chat-panel.tsx
import { useEffect, useRef, useState } from 'react';
import { fetchConversation, continueConversationStream } from './api';
import type { ApiAiConversation, ApiAiMessage } from '../../types';

interface ChatPanelProps {
  conversationId: string;
  token: string;
  onClose: () => void;
}

export function ChatPanel({ conversationId, token, onClose }: ChatPanelProps) {
  const [conversation, setConversation] = useState<ApiAiConversation | null>(null);
  const [messages, setMessages] = useState<ApiAiMessage[]>([]);
  const [input, setInput] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const [streamingContent, setStreamingContent] = useState('');
  const [error, setError] = useState<string | null>(null);
  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    fetchConversation(conversationId, token)
      .then((conv) => {
        setConversation(conv);
        setMessages(conv.messages);
      })
      .catch(() => setError('Failed to load conversation.'));
  }, [conversationId, token]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, streamingContent]);

  async function handleSend() {
    const content = input.trim();
    if (!content || isStreaming) return;
    setInput('');
    setIsStreaming(true);
    setStreamingContent('');
    setError(null);

    setMessages((prev) => [
      ...prev,
      { id: Date.now(), role: 'user', content, created_at: new Date().toISOString() },
    ]);

    try {
      let full = '';
      const gen = continueConversationStream(conversationId, content, token);
      for await (const chunk of gen) {
        if (chunk.type === 'content') {
          full += chunk.content;
          setStreamingContent(full);
        } else if (chunk.type === 'done') {
          setMessages((prev) => [
            ...prev,
            { id: Date.now() + 1, role: 'assistant', content: full, created_at: new Date().toISOString() },
          ]);
          setStreamingContent('');
          setIsStreaming(false);
        }
      }
    } catch {
      setError('AI service is unavailable. Please try again.');
      setIsStreaming(false);
    }
  }

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        right: 0,
        width: 360,
        height: '100vh',
        backgroundColor: 'var(--color-bg-primary)',
        borderLeft: '0.5px solid var(--color-border-primary)',
        display: 'flex',
        flexDirection: 'column',
        zIndex: 500,
        fontFamily: 'var(--font-sans)',
      }}
    >
      {/* Header */}
      <div
        style={{
          padding: '12px 16px',
          borderBottom: '0.5px solid var(--color-border-tertiary)',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          gap: 8,
        }}
      >
        <p
          style={{
            margin: 0,
            fontSize: 12,
            color: 'var(--color-text-secondary)',
            fontStyle: 'italic',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap',
            flex: 1,
          }}
        >
          "{conversation?.selected_text.slice(0, 60)}…"
        </p>
        <button
          onClick={onClose}
          style={{
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            color: 'var(--color-text-secondary)',
            fontSize: 18,
            lineHeight: 1,
            flexShrink: 0,
          }}
        >
          ×
        </button>
      </div>

      {/* Messages */}
      <div style={{ flex: 1, overflowY: 'auto', padding: '12px 16px', display: 'flex', flexDirection: 'column', gap: 12 }}>
        {messages.map((msg) => (
          <MessageBubble key={msg.id} message={msg} />
        ))}
        {isStreaming && streamingContent && (
          <MessageBubble
            message={{ id: 0, role: 'assistant', content: streamingContent, created_at: '' }}
            streaming
          />
        )}
        {error && <p style={{ color: 'var(--color-error, #e53e3e)', fontSize: 13 }}>{error}</p>}
        <div ref={bottomRef} />
      </div>

      {/* Input */}
      <div style={{ padding: '12px 16px', borderTop: '0.5px solid var(--color-border-tertiary)', display: 'flex', gap: 8 }}>
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); void handleSend(); } }}
          placeholder="Ask a follow-up…"
          disabled={isStreaming}
          style={{
            flex: 1,
            padding: '8px 10px',
            fontSize: 13,
            border: '0.5px solid var(--color-border-primary)',
            borderRadius: 'var(--radius-sm)',
            backgroundColor: 'var(--color-bg-secondary)',
            color: 'var(--color-text-primary)',
            outline: 'none',
          }}
        />
        <button
          onClick={() => void handleSend()}
          disabled={isStreaming || input.trim() === ''}
          style={{
            padding: '8px 14px',
            fontSize: 13,
            border: 'none',
            borderRadius: 'var(--radius-sm)',
            backgroundColor: 'var(--color-bg-inverse)',
            color: 'var(--color-text-inverse)',
            cursor: 'pointer',
            opacity: isStreaming || input.trim() === '' ? 0.5 : 1,
          }}
        >
          Send
        </button>
      </div>
    </div>
  );
}

function MessageBubble({ message, streaming = false }: { message: ApiAiMessage; streaming?: boolean }) {
  const isUser = message.role === 'user';
  return (
    <div style={{ display: 'flex', justifyContent: isUser ? 'flex-end' : 'flex-start' }}>
      <div
        style={{
          maxWidth: '85%',
          padding: '8px 12px',
          borderRadius: 'var(--radius-sm)',
          fontSize: 13,
          lineHeight: 1.55,
          whiteSpace: 'pre-wrap',
          backgroundColor: isUser ? 'var(--color-bg-inverse)' : 'var(--color-bg-secondary)',
          color: isUser ? 'var(--color-text-inverse)' : 'var(--color-text-primary)',
        }}
      >
        {message.content}
        {streaming && <span style={{ opacity: 0.5 }}>▍</span>}
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/ai/chat-panel.tsx
git commit -m "feat: add ChatPanel component with follow-up messaging"
```

---

## Task 13: ConversationHighlights

**Files:**
- Create: `frontend/src/features/ai/conversation-highlights.tsx`

- [ ] **Step 1: Create ConversationHighlights**

```typescript
// frontend/src/features/ai/conversation-highlights.tsx
import { useEffect, useState } from 'react';
import { fetchPostConversations } from './api';
import type { ApiAiConversation } from '../../types';

interface ConversationHighlightsProps {
  postSlug: string;
  token: string;
  containerRef: React.RefObject<HTMLElement | null>;
  onSelectConversation: (conversationId: string) => void;
}

interface Highlight {
  conversation: ApiAiConversation;
  rect: DOMRect;
}

export function ConversationHighlights({
  postSlug,
  token,
  containerRef,
  onSelectConversation,
}: ConversationHighlightsProps) {
  const [highlights, setHighlights] = useState<Highlight[]>([]);

  useEffect(() => {
    fetchPostConversations(postSlug, token)
      .then((conversations) => {
        const container = containerRef.current;
        if (!container) return;

        const text = container.textContent ?? '';
        const computed: Highlight[] = [];

        for (const conv of conversations) {
          const rect = getRectForOffset(container, text, conv.selection_start, conv.selection_end);
          if (rect) computed.push({ conversation: conv, rect });
        }

        setHighlights(computed);
      })
      .catch(() => {});
  }, [postSlug, token, containerRef]);

  return (
    <>
      {highlights.map(({ conversation, rect }) => (
        <div
          key={conversation.id}
          onClick={() => onSelectConversation(conversation.id)}
          title={conversation.selected_text.slice(0, 80)}
          style={{
            position: 'absolute',
            top: rect.top + window.scrollY,
            left: rect.left,
            width: rect.width,
            height: rect.height,
            backgroundColor: 'rgba(250, 200, 80, 0.25)',
            borderBottom: '2px solid rgba(250, 200, 80, 0.8)',
            cursor: 'pointer',
            pointerEvents: 'all',
            zIndex: 10,
          }}
        />
      ))}
    </>
  );
}

function getRectForOffset(
  container: HTMLElement,
  fullText: string,
  start: number,
  end: number,
): DOMRect | null {
  const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
  let offset = 0;
  let startNode: Text | null = null;
  let startNodeOffset = 0;
  let endNode: Text | null = null;
  let endNodeOffset = 0;

  while (walker.nextNode()) {
    const node = walker.currentNode as Text;
    const len = node.length;

    if (!startNode && offset + len > start) {
      startNode = node;
      startNodeOffset = start - offset;
    }

    if (!endNode && offset + len >= end) {
      endNode = node;
      endNodeOffset = end - offset;
      break;
    }

    offset += len;
  }

  if (!startNode || !endNode) return null;

  try {
    const range = document.createRange();
    range.setStart(startNode, startNodeOffset);
    range.setEnd(endNode, endNodeOffset);
    return range.getBoundingClientRect();
  } catch {
    return null;
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/ai/conversation-highlights.tsx
git commit -m "feat: add ConversationHighlights component"
```

---

## Task 14: ConversationPage + Route

**Files:**
- Create: `frontend/src/pages/conversation-page.tsx`
- Modify: `frontend/src/routes.tsx`

- [ ] **Step 1: Create ConversationPage**

```typescript
// frontend/src/pages/conversation-page.tsx
import { useParams, Link } from 'react-router';
import { useEffect, useState } from 'react';
import { Topbar } from '../components/layout/topbar';
import { fetchConversation } from '../features/ai/api';
import { continueConversationStream } from '../features/ai/api';
import { useAuth } from '../features/auth/auth-context';
import type { ApiAiConversation, ApiAiMessage } from '../types';

export function ConversationPage() {
  const { id } = useParams<{ id: string }>();
  const { token } = useAuth();
  const [conversation, setConversation] = useState<ApiAiConversation | null>(null);
  const [messages, setMessages] = useState<ApiAiMessage[]>([]);
  const [input, setInput] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const [streamingContent, setStreamingContent] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!id || !token) return;
    fetchConversation(id, token)
      .then((conv) => { setConversation(conv); setMessages(conv.messages); })
      .catch(() => setNotFound(true));
  }, [id, token]);

  async function handleSend() {
    const content = input.trim();
    if (!content || isStreaming || !id || !token) return;
    setInput('');
    setIsStreaming(true);
    setStreamingContent('');
    setError(null);

    setMessages((prev) => [
      ...prev,
      { id: Date.now(), role: 'user', content, created_at: new Date().toISOString() },
    ]);

    try {
      let full = '';
      for await (const chunk of continueConversationStream(id, content, token)) {
        if (chunk.type === 'content') {
          full += chunk.content;
          setStreamingContent(full);
        } else if (chunk.type === 'done') {
          setMessages((prev) => [
            ...prev,
            { id: Date.now() + 1, role: 'assistant', content: full, created_at: new Date().toISOString() },
          ]);
          setStreamingContent('');
          setIsStreaming(false);
        }
      }
    } catch {
      setError('AI service is unavailable.');
      setIsStreaming(false);
    }
  }

  if (notFound) {
    return (
      <div style={{ padding: 40, textAlign: 'center' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>Conversation not found.</p>
        <Link to="/">← Back to feed</Link>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: 640, margin: '0 auto', padding: '0 16px' }}>
      <Topbar
        left={<Link to="/" style={{ fontFamily: 'var(--font-serif)', fontSize: 18, fontWeight: 500, textDecoration: 'none', color: 'inherit' }}>DevHub</Link>}
        right={null}
      />

      {conversation && (
        <blockquote style={{ borderLeft: '3px solid var(--color-border-secondary)', paddingLeft: 12, margin: '24px 0 0', color: 'var(--color-text-secondary)', fontSize: 14, fontStyle: 'italic' }}>
          {conversation.selected_text}
        </blockquote>
      )}

      <div style={{ display: 'flex', flexDirection: 'column', gap: 12, padding: '16px 0', minHeight: 300 }}>
        {messages.map((msg) => (
          <div key={msg.id} style={{ display: 'flex', justifyContent: msg.role === 'user' ? 'flex-end' : 'flex-start' }}>
            <div style={{
              maxWidth: '85%',
              padding: '10px 14px',
              borderRadius: 'var(--radius-sm)',
              fontSize: 14,
              lineHeight: 1.6,
              whiteSpace: 'pre-wrap',
              backgroundColor: msg.role === 'user' ? 'var(--color-bg-inverse)' : 'var(--color-bg-secondary)',
              color: msg.role === 'user' ? 'var(--color-text-inverse)' : 'var(--color-text-primary)',
            }}>
              {msg.content}
            </div>
          </div>
        ))}
        {isStreaming && streamingContent && (
          <div style={{ display: 'flex', justifyContent: 'flex-start' }}>
            <div style={{ maxWidth: '85%', padding: '10px 14px', borderRadius: 'var(--radius-sm)', fontSize: 14, lineHeight: 1.6, whiteSpace: 'pre-wrap', backgroundColor: 'var(--color-bg-secondary)', color: 'var(--color-text-primary)' }}>
              {streamingContent}<span style={{ opacity: 0.5 }}>▍</span>
            </div>
          </div>
        )}
        {error && <p style={{ color: 'var(--color-error, #e53e3e)', fontSize: 14 }}>{error}</p>}
      </div>

      <div style={{ position: 'sticky', bottom: 0, backgroundColor: 'var(--color-bg-primary)', padding: '12px 0', display: 'flex', gap: 8 }}>
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); void handleSend(); } }}
          placeholder="Ask a follow-up…"
          disabled={isStreaming}
          style={{ flex: 1, padding: '10px 12px', fontSize: 14, border: '0.5px solid var(--color-border-primary)', borderRadius: 'var(--radius-sm)', backgroundColor: 'var(--color-bg-secondary)', color: 'var(--color-text-primary)', outline: 'none' }}
        />
        <button
          onClick={() => void handleSend()}
          disabled={isStreaming || input.trim() === ''}
          style={{ padding: '10px 16px', fontSize: 14, border: 'none', borderRadius: 'var(--radius-sm)', backgroundColor: 'var(--color-bg-inverse)', color: 'var(--color-text-inverse)', cursor: 'pointer', opacity: isStreaming || input.trim() === '' ? 0.5 : 1 }}
        >
          Send
        </button>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Add route to routes.tsx**

Add import:
```typescript
import { ConversationPage } from './pages/conversation-page';
```

Add route inside `createBrowserRouter([...]` after the billing route:
```typescript
  {
    path: '/conversations/:id',
    element: (
      <RequireAuth>
        <ConversationPage />
      </RequireAuth>
    ),
  },
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/pages/conversation-page.tsx frontend/src/routes.tsx
git commit -m "feat: add ConversationPage and route"
```

---

## Task 15: Integrate into PostDetailPage

**Files:**
- Modify: `frontend/src/features/post/prose-content.tsx`
- Modify: `frontend/src/pages/post-detail-page.tsx`

- [ ] **Step 1: Update ProseContent to expose ref**

Replace `frontend/src/features/post/prose-content.tsx` with:

```typescript
import { forwardRef } from 'react';

interface ProseContentProps {
  html: string;
}

export const ProseContent = forwardRef<HTMLDivElement, ProseContentProps>(
  function ProseContent({ html }, ref) {
    return (
      <div
        ref={ref}
        className="prose-content"
        style={{
          fontFamily: 'var(--font-serif)',
          fontSize: 18,
          lineHeight: 1.7,
        }}
        // TODO: sanitize html with DOMPurify before connecting to API — mock data only is safe
        dangerouslySetInnerHTML={{ __html: html }}
      />
    );
  }
);
```

- [ ] **Step 2: Update PostDetailPage**

Replace `frontend/src/pages/post-detail-page.tsx` with the following. Key changes: add `useRef` for prose container, integrate `useTextSelection`, `AskAiButton`, `ExplanationModal`, `ChatPanel`, and `ConversationHighlights`:

```typescript
import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { PostHeader } from '../features/post/post-header';
import { ProseContent } from '../features/post/prose-content';
import { ReactionBar } from '../features/post/reaction-bar';
import { CommentsSection } from '../features/comments/comments-section';
import { useAuth } from '../features/auth/auth-context';
import { api, ApiError } from '../lib/api';
import { useTextSelection } from '../features/ai/use-text-selection';
import { AskAiButton } from '../features/ai/ask-ai-button';
import { ExplanationModal } from '../features/ai/explanation-modal';
import { ChatPanel } from '../features/ai/chat-panel';
import { ConversationHighlights } from '../features/ai/conversation-highlights';
import type { ApiPost } from '../types';
import type { TextSelection } from '../features/ai/use-text-selection';

export function PostDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const { token } = useAuth();
  const [post, setPost] = useState<ApiPost | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [isBookmarked, setIsBookmarked] = useState(false);
  const [isTogglingBookmark, setIsTogglingBookmark] = useState(false);

  const proseRef = useRef<HTMLDivElement>(null);
  const selection = useTextSelection(proseRef);
  const [activeSelection, setActiveSelection] = useState<TextSelection | null>(null);
  const [activeChatId, setActiveChatId] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    setIsLoading(true);
    setNotFound(false);
    api
      .get<ApiPost>(`/posts/${slug}`, token ?? undefined)
      .then((result) => { setPost(result); setIsBookmarked(result.is_bookmarked ?? false); })
      .catch((err) => { if (err instanceof ApiError && err.status === 404) setNotFound(true); })
      .finally(() => setIsLoading(false));
  }, [slug, token]);

  async function handleBookmark() {
    if (!token || !post || isTogglingBookmark) return;
    setIsTogglingBookmark(true);
    try {
      const res = await api.post<{ bookmarked: boolean }>(`/posts/${post.slug}/bookmark`, {}, token);
      setIsBookmarked(res.bookmarked);
    } finally {
      setIsTogglingBookmark(false);
    }
  }

  if (isLoading) {
    return (
      <div style={{ maxWidth: 1080, margin: '0 auto', backgroundColor: 'var(--color-bg-tertiary)', borderRadius: 'var(--radius-lg)', border: '0.5px solid var(--color-border-tertiary)', overflow: 'hidden' }}>
        <Topbar
          left={<Link to="/" style={{ fontFamily: 'var(--font-serif)', fontSize: 18, fontWeight: 500, textDecoration: 'none', color: 'inherit' }}>DevHub</Link>}
          right={token ? <Button onClick={() => { void handleBookmark(); }} disabled={isTogglingBookmark}>{isBookmarked ? '★ Saved' : '☆ Bookmark'}</Button> : null}
        />
        <div style={{ backgroundColor: 'var(--color-bg-primary)', padding: '40px 64px' }}>
          <div style={{ maxWidth: 580, margin: '0 auto' }} className="animate-pulse">
            <div className="h-4 w-16 rounded mb-4" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-8 w-3/4 rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-5 w-1/2 rounded mb-8" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-4 w-full rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-4 w-full rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-4 w-5/6 rounded" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
          </div>
        </div>
      </div>
    );
  }

  if (notFound || !post) {
    return (
      <div style={{ padding: 40, textAlign: 'center' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>Post not found.</p>
        <Link to="/" style={{ color: 'var(--color-text-primary)' }}>← Back to feed</Link>
      </div>
    );
  }

  return (
    <div style={{ position: 'relative', maxWidth: 1080, margin: '0 auto', backgroundColor: 'var(--color-bg-tertiary)', borderRadius: 'var(--radius-lg)', border: '0.5px solid var(--color-border-tertiary)', overflow: 'hidden' }}>
      <Topbar
        left={<Link to="/" style={{ fontFamily: 'var(--font-serif)', fontSize: 18, fontWeight: 500, textDecoration: 'none', color: 'inherit' }}>DevHub</Link>}
        right={token ? <Button onClick={() => { void handleBookmark(); }} disabled={isTogglingBookmark}>{isBookmarked ? '★ Saved' : '☆ Bookmark'}</Button> : null}
      />

      <div style={{ backgroundColor: 'var(--color-bg-primary)', padding: '40px 64px', position: 'relative' }}>
        <div style={{ maxWidth: 580, margin: '0 auto', position: 'relative' }}>
          <PostHeader post={post} />
          <ProseContent ref={proseRef} html={post.body_html} />

          {token && proseRef.current && (
            <ConversationHighlights
              postSlug={slug!}
              token={token}
              containerRef={proseRef}
              onSelectConversation={setActiveChatId}
            />
          )}
        </div>
      </div>

      {selection && token && !activeSelection && (
        <AskAiButton
          selection={selection}
          onAsk={(sel) => setActiveSelection(sel)}
        />
      )}

      {activeSelection && token && (
        <ExplanationModal
          selection={activeSelection}
          postSlug={slug!}
          token={token}
          onClose={() => setActiveSelection(null)}
          onOpenChat={(id) => { setActiveSelection(null); setActiveChatId(id); }}
        />
      )}

      {activeChatId && token && (
        <ChatPanel
          conversationId={activeChatId}
          token={token}
          onClose={() => setActiveChatId(null)}
        />
      )}

      <ReactionBar />
      <CommentsSection postSlug={slug!} token={token} />
    </div>
  );
}
```

- [ ] **Step 3: Build frontend to check for type errors**

```bash
cd frontend && npm run build 2>&1 | tail -30
```

Fix any TypeScript errors before proceeding.

- [ ] **Step 4: Run Pint on backend**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Run full test suite**

```bash
php artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/features/post/prose-content.tsx \
  frontend/src/pages/post-detail-page.tsx
git commit -m "feat: integrate AI text explanation into post detail page"
```

---

## Post-Implementation Checklist

- [ ] Ollama running locally: `curl http://localhost:11434/api/tags` returns models
- [ ] Add `OLLAMA_BASE_URL` and `OLLAMA_MODEL` to `.env`
- [ ] Run `npm run build` or `npm run dev` so frontend changes are served
- [ ] Manual test: select text on a published post → "Ask AI" button appears → modal streams → "Continue chatting" opens panel → panel sends follow-up messages
- [ ] Manual test: close and reopen page — public conversation highlights visible on post
- [ ] Manual test: toggle privacy via `PATCH /api/v1/conversations/{id}` — conversation disappears from other users' highlights
