<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\SearchQuery;
use App\Models\User;

class RecordSearchQueryAction
{
    public function execute(string $query, int $resultsCount, ?User $user): void
    {
        if (mb_strlen(trim($query)) === 0) {
            return;
        }

        SearchQuery::create([
            'query' => mb_substr($query, 0, 200),
            'results_count' => $resultsCount,
            'user_id' => $user?->id,
        ]);
    }
}
