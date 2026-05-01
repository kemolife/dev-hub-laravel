<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Records every search query made — used for analytics and query suggestions.
 *
 * No `updated_at` — search queries are immutable once recorded.
 *
 * @property int $id
 * @property string $query
 * @property int $results_count
 * @property int|null $user_id
 * @property Carbon $created_at
 */
#[Fillable(['query', 'results_count', 'user_id'])]
class SearchQuery extends Model
{
    /** @var bool Only created_at, no updated_at */
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
