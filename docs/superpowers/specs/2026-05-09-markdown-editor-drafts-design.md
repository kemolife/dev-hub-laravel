# Markdown Editor + Drafts Management — Design Spec

**Date:** 2026-05-09
**Status:** Approved

---

## Scope

Two features delivered together:

1. Replace the plain `contentEditable` writing area with a CodeMirror 6 markdown editor (write/preview toggle, formatting toolbar)
2. Add a `/drafts` page and a "Write" dropdown in the topbar to manage multiple drafts

---

## 1. Markdown Editor

### Packages Added

```
@codemirror/view
@codemirror/state
@codemirror/lang-markdown
@codemirror/language
@codemirror/commands
@codemirror/theme-one-dark
@lezer/highlight
markdown-it
highlight.js
@types/markdown-it
```

Language support inside fenced code blocks: JavaScript, TypeScript, PHP, Python, bash (via `@codemirror/lang-javascript`, `@codemirror/lang-python`, `@codemirror/lang-php`).

### Modes

- **Write mode** (default): CodeMirror editor with markdown syntax highlighting and inline code-block language highlighting.
- **Preview mode**: `markdown-it` renders stored markdown, `highlight.js` highlights fenced code blocks. Styled to match the public post page exactly.

Mode is `'write' | 'preview'` state in `EditorPage`. The existing "Preview" button in the topbar toggles it.

### Toolbar

Sits above the CodeMirror instance (write mode only). Seven actions:

| Button | Inserts / wraps |
|---|---|
| **B** | `**text**` |
| *I* | `*text*` |
| `Code` | `` `text` `` |
| ```` ``` ```` | Inserts ` ```language\n\n``` ` with cursor on `language` so user types it |
| `H` | `## ` prefix on current line |
| `Link` | `[text](url)` |
| `Image` | `![alt](url)` |

If text is selected, toolbar buttons wrap the selection. If no selection, insert at cursor with placeholder text.

### Files

| File | Action |
|---|---|
| `frontend/src/features/editor/markdown-editor.tsx` | New — wraps CodeMirror, exposes `value` + `onChange` |
| `frontend/src/features/editor/editor-toolbar.tsx` | New — toolbar buttons, receives CodeMirror `EditorView` ref |
| `frontend/src/features/editor/preview-renderer.tsx` | New — renders markdown-it HTML, applies highlight.js |
| `frontend/src/features/editor/writing-area.tsx` | Deleted — replaced by `markdown-editor.tsx` |
| `frontend/src/pages/editor-page.tsx` | Updated — swap `WritingArea` for `MarkdownEditor`, add preview mode state, wire toolbar |

### Content Storage

`EditorPage` keeps a `content` string (raw markdown). `MarkdownEditor` is a controlled component: `value={content}` + `onChange`. Auto-save and publish continue to send the raw markdown string to the backend — no change to API contract.

### Stats

`use-editor-stats.ts` already parses code block count from raw markdown — continues to work unchanged.

---

## 2. Write Dropdown

### Behaviour

The "Write" button in `home-page.tsx` topbar becomes a dropdown trigger. Opens a small menu on click:

- **New post** → navigate to `/editor`
- **My drafts** → navigate to `/drafts`

Closes on: item select, outside click, Escape key.

No new component — implemented inline in `home-page.tsx` using local `isOpen` state.

---

## 3. Drafts Page (`/drafts`)

### Route

Added to `frontend/src/routes.tsx`. Protected: unauthenticated users redirected to `/login`.

### Data

Calls `GET /api/v1/me/posts?status=draft` with Bearer token. Returns paginated `PostResource` collection.

### UI

- Page title: "My drafts"
- List of draft cards: title (fallback "Untitled" if empty), last updated relative timestamp, tag chips
- Click card → `/editor?slug={slug}`
- Empty state: "No drafts yet — " + "Start writing" link to `/editor`
- Loading skeleton while fetching
- Error state if request fails

---

## 4. Backend — `GET /api/v1/me/posts`

### Route

```php
Route::get('me/posts', [UserPostController::class, 'index'])->middleware('auth:sanctum');
```

Added to `routes/api.php` inside the existing `api/v1` prefix group.

### Controller

`app/Http/Controllers/Api/V1/UserPostController.php`

```php
public function index(Request $request): AnonymousResourceCollection
{
    $status = $request->enum('status', PostStatus::class);

    $posts = $request->user()
        ->posts()
        ->when($status, fn ($q) => $q->where('status', $status))
        ->with('tags')
        ->latest('updated_at')
        ->paginate();

    return PostResource::collection($posts);
}
```

No new Action — this is a simple read query scoped to the authenticated user. No business logic.

### Authorization

Route requires `auth:sanctum`. Users can only see their own posts — scoped via `$request->user()->posts()`. No Policy gate needed.

---

## 5. Testing

- `tests/Feature/Api/V1/UserPostControllerTest.php` — new
  - Returns only authenticated user's posts
  - Filters by `?status=draft` correctly
  - Returns 401 when unauthenticated
  - Does not return other users' posts
- `EditorPage` tests: not added (UI-only, covered by manual testing)

---

## Out of Scope

- Published posts tab on `/drafts`
- Inline draft rename
- Draft deletion from the drafts page
- Image upload (toolbar inserts URL placeholder only)
- Table, blockquote, ordered/unordered list toolbar buttons (Phase 2)
