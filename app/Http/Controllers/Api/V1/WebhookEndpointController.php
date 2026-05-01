<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Webhook\CreateWebhookEndpointAction;
use App\Actions\Webhook\UpdateWebhookEndpointAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\StoreWebhookEndpointRequest;
use App\Http\Requests\Webhook\UpdateWebhookEndpointRequest;
use App\Http\Resources\WebhookEndpointResource;
use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WebhookEndpointController extends Controller
{
    public function __construct(
        private readonly CreateWebhookEndpointAction $createWebhookEndpointAction,
        private readonly UpdateWebhookEndpointAction $updateWebhookEndpointAction,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $endpoints = $request->user()->webhookEndpoints()->latest()->paginate();

        return WebhookEndpointResource::collection($endpoints);
    }

    public function store(StoreWebhookEndpointRequest $request): JsonResponse
    {
        $endpoint = $this->createWebhookEndpointAction->execute(
            $request->user(),
            $request->toData(),
        );

        return (new WebhookEndpointResource($endpoint))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateWebhookEndpointRequest $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $endpoint = $this->updateWebhookEndpointAction->execute(
            $webhookEndpoint,
            $request->toData(),
        );

        return (new WebhookEndpointResource($endpoint))->response();
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint): Response
    {
        abort_unless($request->user()?->id === $webhookEndpoint->user_id, 403);

        $webhookEndpoint->delete();

        return response()->noContent();
    }

    public function test(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        abort_unless($request->user()?->id === $webhookEndpoint->user_id, 403);

        dispatch(new DeliverWebhookJob($webhookEndpoint, 'ping', [
            'event' => 'ping',
            'timestamp' => now()->toISOString(),
            'data' => [],
        ]));

        return response()->json(['message' => 'Test event dispatched.']);
    }
}
