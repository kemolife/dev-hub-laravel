<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Search\RecordSearchQueryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function __construct(
        private readonly RecordSearchQueryAction $recordSearchQuery,
    ) {}

    public function __invoke(SearchRequest $request): AnonymousResourceCollection
    {
        $data = $request->toData();

        if ($data->query === '') {
            $posts = Post::published()
                ->with('user')
                ->latest('published_at')
                ->paginate();

            return PostResource::collection($posts);
        }

        $search = Post::search($data->query)->query(fn ($q) => $q->with('user', 'tags'));

        if ($data->author !== null) {
            $search->where('author_username', $data->author);
        }

        $results = $search->paginate();
        $total = $results->total();

        defer(fn () => $this->recordSearchQuery->execute($data->query, $total, $request->user()));

        return PostResource::collection($results);
    }
}
