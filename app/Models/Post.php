<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MarkdownCast;
use App\Concerns\HasReactions;
use App\Enums\PostStatus;
use App\Support\ReadingTime;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @property PostStatus $status
 * @property int $reading_time_seconds
 * @property Carbon|CarbonImmutable|null $published_at
 * @property bool $is_bookmarked
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
    use HasFactory, HasReactions, Searchable, SoftDeletes;

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

    public function searchableAs(): string
    {
        return (config('scout.prefix') ?? '').'posts';
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        $this->loadMissing('user');

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => mb_substr($this->body_markdown ?? '', 0, 5000),
            'author_username' => $this->user?->username,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->timestamp,
            'view_count' => $this->view_count,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === PostStatus::Published;
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

    /** @return HasMany<Bookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
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
