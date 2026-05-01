<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notification\UpsertNotificationPreferencesAction;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly UpsertNotificationPreferencesAction $upsertAction,
    ) {}

    /**
     * Returns user's stored preferences, filling in defaults for any
     * type/channel combinations that have not been explicitly set.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $stored = NotificationPreference::where('user_id', $user->id)->get();

        // Build a keyed lookup so we can fall back to defaults
        $indexed = $stored->keyBy(fn (NotificationPreference $p) => $p->type->value.':'.$p->channel->value);

        $preferences = [];

        foreach (NotificationType::cases() as $type) {
            foreach ($type->defaultChannels() as $channel) {
                $key = $type->value.':'.$channel->value;
                $preferences[] = $indexed->get($key) ?? new NotificationPreference([
                    'user_id' => $user->id,
                    'type' => $type,
                    'channel' => $channel,
                    'enabled' => true,
                    'digest' => false,
                ]);
            }
        }

        return NotificationPreferenceResource::collection(collect($preferences));
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $this->upsertAction->execute($request->user(), $request->toData());

        return response()->json(['message' => 'Preferences updated.']);
    }
}
