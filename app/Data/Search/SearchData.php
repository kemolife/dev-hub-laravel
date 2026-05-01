<?php

declare(strict_types=1);

namespace App\Data\Search;

readonly class SearchData
{
    public function __construct(
        public string $query,
        public ?string $author,
        public ?string $sort,
    ) {}

    /** @param array{q?: ?string, author?: ?string, sort?: ?string} $data */
    public static function from(array $data): self
    {
        return new self(
            query: trim($data['q'] ?? ''),
            author: isset($data['author']) && $data['author'] !== '' ? trim($data['author']) : null,
            sort: isset($data['sort']) && $data['sort'] !== '' ? $data['sort'] : 'published_at:desc',
        );
    }
}
