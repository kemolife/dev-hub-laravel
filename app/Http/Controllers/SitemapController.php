<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()->select(['slug', 'updated_at'])->get();

        return response()
            ->view('sitemap', ['posts' => $posts])
            ->header('Content-Type', 'application/xml');
    }
}
