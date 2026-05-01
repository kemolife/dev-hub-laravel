<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Data\Notification\UpdatePreferencesData;
use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array'],
            'preferences.*.type' => ['required', new Enum(NotificationType::class)],
            'preferences.*.channel' => ['required', new Enum(NotificationChannel::class)],
            'preferences.*.enabled' => ['required', 'boolean'],
            'preferences.*.digest' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, UpdatePreferencesData>
     */
    public function toData(): array
    {
        return array_map(
            fn (array $pref): UpdatePreferencesData => UpdatePreferencesData::from($pref),
            $this->validated('preferences'),
        );
    }
}
