<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $reportable = match ($type) {
            'post' => Post::findOrFail($id),
            'comment' => Comment::findOrFail($id),
            default => abort(422, 'Invalid reportable type. Must be one of: post, comment'),
        };

        $report = Report::create([
            'reporter_user_id' => $request->user()->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->id,
            'reason' => $request->string('reason')->toString(),
            'description' => $request->filled('description') ? $request->string('description')->toString() : null,
            'status' => 'open',
        ]);

        return response()->json(['message' => 'Report submitted successfully.', 'id' => $report->id], 201);
    }
}
