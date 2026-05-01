<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MarkdownCast;
use App\Concerns\HasReactions;
use App\Enums\PostStatus;
use App\Support\ReadingTime;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property PostStatus $status
 * @property int $reading_time_seconds
 */
#[Fillable([
    'user_id',
    'title',
    'slug',
    'excerpt',
    'body_markdown',
    'body_html',
    'reading_time_seconds',
    'status',
    'published_at',
    'view_count',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasReactions, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            $post->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'body_markdown' => MarkdownCast::class,
        ];
    }

    public function readingTime(): ReadingTime
    {
        return new ReadingTime($this->reading_time_seconds ?? 0);
    }

    /** @param Builder<Post> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published);
    }

    /** @param Builder<Post> $query */
    public function scopeDraft(Builder $query): void
    {
        $query->where('status', PostStatus::Draft);
    }

    /** @param Builder<Post> $query */
    public function scopeArchived(Builder $query): void
    {
        $query->where('status', PostStatus::Archived);
    }

    /** @param Builder<Post> $query */
    public function scopeTrending(Builder $query): void
    {
        $query->where('published_at', '>=', now()->subDays(7))
            ->orderByDesc('view_count');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withPivot('weight', 'added_by_user_id')
            ->withTimestamps();
    }
}
