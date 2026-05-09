# Markdown Editor + Drafts Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the plain contentEditable writing area with a CodeMirror 6 markdown editor (write/preview toggle + toolbar), and add a `/drafts` page accessible from a "Write" dropdown in the topbar.

**Architecture:** Backend adds `GET /api/v1/me/posts` scoped to the authenticated user. Frontend replaces `WritingArea` with `MarkdownEditor` (CodeMirror 6 + toolbar + preview renderer). A new `DraftsPage` at `/drafts` and a dropdown on the "Write" button complete the drafts management flow.

**Tech Stack:** CodeMirror 6 (`@codemirror/view`, `@codemirror/state`, `@codemirror/lang-markdown`, `@codemirror/language-data`, `@codemirror/theme-one-dark`, `@codemirror/commands`), `markdown-it`, `highlight.js`, PHP 8.4 / Laravel 13, PHPUnit 12.

---

## File Map

### Backend
| Action | File |
|---|---|
| Create | `app/Http/Controllers/Api/V1/UserPostController.php` |
| Modify | `routes/api.php` |
| Create | `tests/Feature/UserPostControllerTest.php` |

### Frontend
| Action | File |
|---|---|
| Delete | `frontend/src/features/editor/writing-area.tsx` |
| Create | `frontend/src/features/editor/preview-renderer.tsx` |
| Create | `frontend/src/features/editor/editor-toolbar.tsx` |
| Create | `frontend/src/features/editor/markdown-editor.tsx` |
| Modify | `frontend/src/pages/editor-page.tsx` |
| Create | `frontend/src/pages/drafts-page.tsx` |
| Modify | `frontend/src/routes.tsx` |
| Modify | `frontend/src/pages/home-page.tsx` |

---

## Task 1: Backend — UserPostController + Route

**Files:**
- Create: `app/Http/Controllers/Api/V1/UserPostController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Write the failing test**

```bash
php artisan make:test UserPostControllerTest --phpunit
```

Replace the generated file contents with:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserPostControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_authenticated_users_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($other)->draft()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me/posts');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_status(): void
    {
        $user = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($user)->published()->count(1)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me/posts?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/me/posts')
            ->assertUnauthorized();
    }

    public function test_does_not_return_other_users_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->for($other)->draft()->count(5)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me/posts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact tests/Feature/UserPostControllerTest.php
```

Expected: 4 tests fail with `404` (route does not exist yet).

- [ ] **Step 3: Create the controller**

```bash
php artisan make:class app/Http/Controllers/Api/V1/UserPostController
```

Replace the file contents:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserPostController extends Controller
{
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
}
```

- [ ] **Step 4: Register the route**

Open `routes/api.php`. Add this import at the top with the other imports:

```php
use App\Http\Controllers\Api\V1\UserPostController;
```

Inside the `Route::middleware(['auth:sanctum', UpdateLastSeenAt::class])->group(...)` block, after the `Route::get('/me', ...)` line, add:

```php
Route::get('/me/posts', [UserPostController::class, 'index']);
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test --compact tests/Feature/UserPostControllerTest.php
```

Expected: 4 tests pass.

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/V1/UserPostController.php routes/api.php tests/Feature/UserPostControllerTest.php
git commit -m "feat: add GET /api/v1/me/posts endpoint for authenticated user's posts"
```

---

## Task 2: Install Frontend Packages

**Files:**
- Modify: `frontend/package.json` (via npm install)

- [ ] **Step 1: Install packages**

```bash
cd frontend && npm install \
  @codemirror/view \
  @codemirror/state \
  @codemirror/lang-markdown \
  @codemirror/language-data \
  @codemirror/theme-one-dark \
  @codemirror/commands \
  markdown-it \
  highlight.js \
  @types/markdown-it
```

- [ ] **Step 2: Verify install**

```bash
cd frontend && npm run build 2>&1 | tail -5
```

Expected: Build succeeds (no errors from new packages).

- [ ] **Step 3: Commit**

```bash
git add frontend/package.json frontend/package-lock.json
git commit -m "chore: add CodeMirror 6, markdown-it, highlight.js to frontend"
```

---

## Task 3: PreviewRenderer Component

Renders raw markdown string as HTML with code block syntax highlighting.

**Files:**
- Create: `frontend/src/features/editor/preview-renderer.tsx`

- [ ] **Step 1: Create the component**

```tsx
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const md = new MarkdownIt({
  html: false,
  linkify: true,
  typographer: true,
  highlight(str, lang) {
    if (lang && hljs.getLanguage(lang)) {
      return `<pre class="hljs"><code>${hljs.highlight(str, { language: lang, ignoreIllegals: true }).value}</code></pre>`;
    }
    return `<pre class="hljs"><code>${md.utils.escapeHtml(str)}</code></pre>`;
  },
});

interface PreviewRendererProps {
  markdown: string;
}

export function PreviewRenderer({ markdown }: PreviewRendererProps) {
  return (
    <div
      className="prose-preview"
      style={{
        padding: '32px 48px',
        minHeight: 520,
        backgroundColor: 'var(--color-bg-primary)',
        fontFamily: 'var(--font-serif)',
        fontSize: 18,
        lineHeight: 1.7,
        color: 'var(--color-text-primary)',
      }}
      dangerouslySetInnerHTML={{ __html: md.render(markdown || '_Nothing to preview yet._') }}
    />
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/editor/preview-renderer.tsx
git commit -m "feat: add PreviewRenderer component with markdown-it and highlight.js"
```

---

## Task 4: EditorToolbar Component

Renders formatting buttons. Each button calls an action handler. Disabled in preview mode.

**Files:**
- Create: `frontend/src/features/editor/editor-toolbar.tsx`

- [ ] **Step 1: Create the component**

```tsx
import type { EditorView } from '@codemirror/view';

type ToolbarAction = 'bold' | 'italic' | 'code' | 'codeblock' | 'heading' | 'link' | 'image';

interface EditorToolbarProps {
  viewRef: React.RefObject<EditorView | null>;
}

const ACTIONS: { action: ToolbarAction; label: string; title: string }[] = [
  { action: 'bold', label: 'B', title: 'Bold (Ctrl+B)' },
  { action: 'italic', label: 'I', title: 'Italic (Ctrl+I)' },
  { action: 'code', label: '`', title: 'Inline code' },
  { action: 'codeblock', label: '```', title: 'Code block' },
  { action: 'heading', label: 'H', title: 'Heading' },
  { action: 'link', label: 'Link', title: 'Link' },
  { action: 'image', label: 'Img', title: 'Image' },
];

function applyAction(view: EditorView, action: ToolbarAction): void {
  const { from, to } = view.state.selection.main;
  const selected = view.state.sliceDoc(from, to);

  const wrap = (before: string, after: string, placeholder: string) => {
    const text = selected || placeholder;
    view.dispatch({
      changes: { from, to, insert: `${before}${text}${after}` },
      selection: { anchor: from + before.length, head: from + before.length + text.length },
    });
    view.focus();
  };

  switch (action) {
    case 'bold':
      wrap('**', '**', 'bold text');
      break;
    case 'italic':
      wrap('*', '*', 'italic text');
      break;
    case 'code':
      wrap('`', '`', 'code');
      break;
    case 'codeblock': {
      const body = selected || '';
      const insert = `\`\`\`language\n${body}\n\`\`\``;
      view.dispatch({
        changes: { from, to, insert },
        selection: { anchor: from + 3, head: from + 11 }, // select "language"
      });
      view.focus();
      break;
    }
    case 'heading':
      wrap('## ', '', 'Heading');
      break;
    case 'link':
      wrap('[', '](url)', selected || 'link text');
      break;
    case 'image':
      wrap('![', '](url)', selected || 'alt text');
      break;
  }
}

export function EditorToolbar({ viewRef }: EditorToolbarProps) {
  function handleAction(action: ToolbarAction) {
    if (viewRef.current) {
      applyAction(viewRef.current, action);
    }
  }

  return (
    <div
      className="flex gap-1"
      style={{
        padding: '8px 12px',
        borderBottom: '0.5px solid var(--color-border-tertiary)',
        backgroundColor: 'var(--color-bg-secondary)',
      }}
    >
      {ACTIONS.map(({ action, label, title }) => (
        <button
          key={action}
          title={title}
          onMouseDown={(e) => {
            e.preventDefault(); // prevent editor losing focus
            handleAction(action);
          }}
          style={{
            minWidth: action === 'codeblock' ? 40 : 28,
            height: 28,
            padding: '0 6px',
            border: '0.5px solid var(--color-border-tertiary)',
            borderRadius: 'var(--radius-sm)',
            backgroundColor: 'var(--color-bg-primary)',
            cursor: 'pointer',
            fontSize: action === 'bold' ? 13 : 12,
            fontWeight: action === 'bold' ? 700 : action === 'italic' ? undefined : 400,
            fontStyle: action === 'italic' ? 'italic' : undefined,
            fontFamily: ['code', 'codeblock'].includes(action) ? 'monospace' : 'inherit',
            color: 'var(--color-text-primary)',
          }}
        >
          {label}
        </button>
      ))}
    </div>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/editor/editor-toolbar.tsx
git commit -m "feat: add EditorToolbar component with markdown formatting actions"
```

---

## Task 5: MarkdownEditor Component

CodeMirror 6 editor with toolbar and preview toggle support.

**Files:**
- Create: `frontend/src/features/editor/markdown-editor.tsx`
- Delete: `frontend/src/features/editor/writing-area.tsx`

- [ ] **Step 1: Create the component**

```tsx
import { useEffect, useRef } from 'react';
import { EditorView, keymap } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { markdown, markdownLanguage } from '@codemirror/lang-markdown';
import { languages } from '@codemirror/language-data';
import { oneDark } from '@codemirror/theme-one-dark';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { EditorToolbar } from './editor-toolbar';
import { PreviewRenderer } from './preview-renderer';

interface MarkdownEditorProps {
  title: string;
  subtitle: string;
  value: string;
  mode: 'write' | 'preview';
  onTitleChange: (value: string) => void;
  onSubtitleChange: (value: string) => void;
  onChange: (value: string) => void;
}

export function MarkdownEditor({
  title,
  subtitle,
  value,
  mode,
  onTitleChange,
  onSubtitleChange,
  onChange,
}: MarkdownEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);
  const viewRef = useRef<EditorView | null>(null);

  useEffect(() => {
    if (!editorRef.current) return;

    const state = EditorState.create({
      doc: value,
      extensions: [
        markdown({ base: markdownLanguage, codeLanguages: languages }),
        oneDark,
        history(),
        EditorView.lineWrapping,
        keymap.of([...defaultKeymap, ...historyKeymap]),
        EditorView.updateListener.of((update) => {
          if (update.docChanged) {
            onChange(update.state.doc.toString());
          }
        }),
        EditorView.theme({
          '&': { fontSize: '16px', fontFamily: 'var(--font-mono, monospace)' },
          '.cm-content': { padding: '16px 0', minHeight: '400px' },
          '.cm-focused': { outline: 'none' },
          '.cm-editor': { backgroundColor: 'var(--color-bg-primary)' },
          '&.cm-focused .cm-cursor': { borderLeftColor: 'var(--color-text-primary)' },
        }),
      ],
    });

    const view = new EditorView({ state, parent: editorRef.current });
    viewRef.current = view;

    return () => {
      view.destroy();
      viewRef.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // intentionally run once — value sync handled below

  // Sync external value changes (e.g. loading an existing draft) without re-creating the editor
  useEffect(() => {
    const view = viewRef.current;
    if (!view) return;
    const current = view.state.doc.toString();
    if (current !== value) {
      view.dispatch({
        changes: { from: 0, to: current.length, insert: value },
      });
    }
  }, [value]);

  return (
    <div style={{ backgroundColor: 'var(--color-bg-primary)', minHeight: 520 }}>
      {/* Title + subtitle always visible */}
      <div style={{ padding: '32px 48px 0' }}>
        <input
          type="text"
          placeholder="Title"
          value={title}
          onChange={(e) => onTitleChange(e.target.value)}
          style={{
            width: '100%',
            border: 'none',
            background: 'transparent',
            fontSize: 26,
            fontWeight: 500,
            padding: 0,
            margin: '0 0 8px',
            lineHeight: 1.3,
            outline: 'none',
            fontFamily: 'inherit',
            color: 'var(--color-text-primary)',
          }}
        />
        <input
          type="text"
          placeholder="A short subtitle (optional)"
          value={subtitle}
          onChange={(e) => onSubtitleChange(e.target.value)}
          style={{
            width: '100%',
            border: 'none',
            background: 'transparent',
            fontSize: 16,
            padding: 0,
            margin: '0 0 16px',
            color: 'var(--color-text-secondary)',
            outline: 'none',
            fontFamily: 'inherit',
          }}
        />
        <div style={{ borderTop: '0.5px solid var(--color-border-tertiary)' }} />
      </div>

      {mode === 'write' ? (
        <>
          <EditorToolbar viewRef={viewRef} />
          <div ref={editorRef} style={{ padding: '0 48px' }} />
        </>
      ) : (
        <PreviewRenderer markdown={value} />
      )}
    </div>
  );
}
```

- [ ] **Step 2: Delete writing-area.tsx**

```bash
rm frontend/src/features/editor/writing-area.tsx
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/editor/markdown-editor.tsx
git add -u frontend/src/features/editor/writing-area.tsx
git commit -m "feat: add MarkdownEditor with CodeMirror 6 replacing plain contentEditable"
```

---

## Task 6: Update EditorPage

Wire `MarkdownEditor` into `EditorPage`. Add `mode` state for the Preview button.

**Files:**
- Modify: `frontend/src/pages/editor-page.tsx`

- [ ] **Step 1: Replace WritingArea import and add mode state**

Open `frontend/src/pages/editor-page.tsx`. Make these changes:

Replace:
```tsx
import { WritingArea } from '../features/editor/writing-area';
```
With:
```tsx
import { MarkdownEditor } from '../features/editor/markdown-editor';
```

After the existing state declarations (after `const [loadError, setLoadError] = useState...`), add:
```tsx
const [mode, setMode] = useState<'write' | 'preview'>('write');
```

- [ ] **Step 2: Wire the Preview button**

Find the Preview button in the `right` prop of `<Topbar>`:
```tsx
<Button>Preview</Button>
```

Replace with:
```tsx
<Button onClick={() => setMode((m) => (m === 'write' ? 'preview' : 'write'))}>
  {mode === 'write' ? 'Preview' : 'Edit'}
</Button>
```

- [ ] **Step 3: Replace WritingArea with MarkdownEditor**

Find:
```tsx
<WritingArea
  title={title}
  subtitle={subtitle}
  onTitleChange={handleTitleChange}
  onSubtitleChange={handleSubtitleChange}
  onContentChange={handleContentChange}
/>
```

Replace with:
```tsx
<MarkdownEditor
  title={title}
  subtitle={subtitle}
  value={content}
  mode={mode}
  onTitleChange={handleTitleChange}
  onSubtitleChange={handleSubtitleChange}
  onChange={handleContentChange}
/>
```

- [ ] **Step 4: Verify TypeScript compiles**

```bash
cd frontend && npx tsc --noEmit 2>&1 | head -20
```

Expected: No errors.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/pages/editor-page.tsx
git commit -m "feat: wire MarkdownEditor into EditorPage with write/preview toggle"
```

---

## Task 7: DraftsPage

A protected page at `/drafts` listing the authenticated user's draft posts.

**Files:**
- Create: `frontend/src/pages/drafts-page.tsx`

- [ ] **Step 1: Create the page**

```tsx
import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { useAuth } from '../features/auth/auth-context';
import { api } from '../lib/api';
import { Tag } from '../components/ui/tag';
import type { ApiPost, Pagination } from '../types';

function relativeTime(dateString: string): string {
  const diff = Date.now() - new Date(dateString).getTime();
  const minutes = Math.floor(diff / 60000);
  if (minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  return `${Math.floor(hours / 24)}d ago`;
}

export function DraftsPage() {
  const { token } = useAuth();
  const [drafts, setDrafts] = useState<ApiPost[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) return;
    api
      .get<Pagination<ApiPost>>('/me/posts?status=draft', token)
      .then((res) => setDrafts(res.data))
      .catch(() => setError('Could not load drafts.'))
      .finally(() => setIsLoading(false));
  }, [token]);

  return (
    <div
      style={{
        maxWidth: 720,
        margin: '0 auto',
        padding: '40px 24px',
      }}
    >
      <div className="flex items-center justify-between" style={{ marginBottom: 32 }}>
        <h1
          style={{
            fontFamily: 'var(--font-serif)',
            fontSize: 24,
            fontWeight: 500,
            margin: 0,
          }}
        >
          My drafts
        </h1>
        <Link
          to="/editor"
          style={{
            fontSize: 14,
            color: 'var(--color-text-primary)',
            textDecoration: 'none',
            border: '0.5px solid var(--color-border-tertiary)',
            borderRadius: 'var(--radius-md)',
            padding: '6px 14px',
          }}
        >
          New post
        </Link>
      </div>

      {isLoading && (
        <p style={{ color: 'var(--color-text-secondary)', fontSize: 14 }}>Loading…</p>
      )}

      {error && (
        <p style={{ color: '#dc2626', fontSize: 14 }}>{error}</p>
      )}

      {!isLoading && !error && drafts.length === 0 && (
        <div style={{ textAlign: 'center', paddingTop: 60 }}>
          <p style={{ color: 'var(--color-text-secondary)', marginBottom: 16 }}>
            No drafts yet.
          </p>
          <Link
            to="/editor"
            style={{ color: 'var(--color-text-primary)', textDecoration: 'underline' }}
          >
            Start writing
          </Link>
        </div>
      )}

      <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
        {drafts.map((draft) => (
          <li
            key={draft.slug}
            style={{
              borderBottom: '0.5px solid var(--color-border-tertiary)',
              padding: '20px 0',
            }}
          >
            <Link
              to={`/editor?slug=${draft.slug}`}
              style={{ textDecoration: 'none', display: 'block' }}
            >
              <p
                style={{
                  fontFamily: 'var(--font-serif)',
                  fontSize: 18,
                  fontWeight: 500,
                  margin: '0 0 6px',
                  color: 'var(--color-text-primary)',
                }}
              >
                {draft.title || 'Untitled'}
              </p>
              <div className="flex items-center gap-2 flex-wrap" style={{ marginBottom: 6 }}>
                {draft.tags.map((tag) => (
                  <Tag key={tag.slug}>{tag.name}</Tag>
                ))}
              </div>
              <p style={{ fontSize: 12, color: 'var(--color-text-tertiary)', margin: 0 }}>
                Last saved {relativeTime(draft.updated_at)}
              </p>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/pages/drafts-page.tsx
git commit -m "feat: add DraftsPage listing authenticated user's draft posts"
```

---

## Task 8: Add /drafts Route + Write Dropdown

Register `/drafts` in the router and convert the "Write" button to a dropdown.

**Files:**
- Modify: `frontend/src/routes.tsx`
- Modify: `frontend/src/pages/home-page.tsx`

- [ ] **Step 1: Add /drafts route to routes.tsx**

Add the import after the existing page imports:
```tsx
import { DraftsPage } from './pages/drafts-page';
```

Add the route after the `/editor` route entry:
```tsx
{
  path: '/drafts',
  element: (
    <RequireAuth>
      <DraftsPage />
    </RequireAuth>
  ),
},
```

- [ ] **Step 2: Add Write dropdown to home-page.tsx**

Open `frontend/src/pages/home-page.tsx`. Add `useRef` to the existing React import:
```tsx
import { useEffect, useRef, useState } from 'react';
```

Find the Write button in the topbar `right` section (inside the `{user ? (...) : (...)}` branch):
```tsx
<Button variant="primary">
  <Link to="/editor" style={{ textDecoration: 'none', color: 'inherit' }}>
    Write
  </Link>
</Button>
```

Replace it with the following (add a `writeMenuOpen` state at the top of the component alongside the other state declarations, and a `writeMenuRef` ref):

Add near the top of the `HomePage` function, alongside existing state:
```tsx
const [writeMenuOpen, setWriteMenuOpen] = useState(false);
const writeMenuRef = useRef<HTMLDivElement>(null);
```

Add a `useEffect` for outside-click closing (alongside the existing `useEffect` for posts):
```tsx
useEffect(() => {
  function handleClick(e: MouseEvent) {
    if (writeMenuRef.current && !writeMenuRef.current.contains(e.target as Node)) {
      setWriteMenuOpen(false);
    }
  }
  document.addEventListener('mousedown', handleClick);
  return () => document.removeEventListener('mousedown', handleClick);
}, []);
```

Replace the Write button JSX with:
```tsx
<div ref={writeMenuRef} style={{ position: 'relative' }}>
  <Button variant="primary" onClick={() => setWriteMenuOpen((o) => !o)}>
    Write ▾
  </Button>
  {writeMenuOpen && (
    <div
      style={{
        position: 'absolute',
        top: 'calc(100% + 6px)',
        right: 0,
        width: 160,
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
        borderRadius: 'var(--radius-md)',
        boxShadow: '0 4px 16px rgba(0,0,0,0.12)',
        zIndex: 50,
        overflow: 'hidden',
      }}
    >
      {[
        { label: 'New post', to: '/editor' },
        { label: 'My drafts', to: '/drafts' },
      ].map(({ label, to }) => (
        <Link
          key={to}
          to={to}
          onClick={() => setWriteMenuOpen(false)}
          style={{
            display: 'block',
            padding: '10px 14px',
            fontSize: 14,
            color: 'var(--color-text-primary)',
            textDecoration: 'none',
          }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLAnchorElement).style.backgroundColor =
              'var(--color-bg-secondary)';
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLAnchorElement).style.backgroundColor = 'transparent';
          }}
        >
          {label}
        </Link>
      ))}
    </div>
  )}
</div>
```

- [ ] **Step 3: Verify TypeScript compiles**

```bash
cd frontend && npx tsc --noEmit 2>&1 | head -20
```

Expected: No errors.

- [ ] **Step 4: Run backend tests to confirm nothing broken**

```bash
php artisan test --compact
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/routes.tsx frontend/src/pages/home-page.tsx
git commit -m "feat: add /drafts route and Write dropdown with New post / My drafts"
```

---

## Manual Verification Checklist

- [ ] `/editor` — CodeMirror loads, markdown syntax highlighted while typing
- [ ] Fenced code block (` ```js `) — JS syntax highlighted inside the block
- [ ] Toolbar Bold button wraps selected text in `**...**`
- [ ] Toolbar Code Block button selects `language` placeholder text
- [ ] Preview button switches to rendered HTML; Edit button switches back
- [ ] Auto-save creates a post; URL updates to `/editor?slug=...`
- [ ] Refreshing `/editor?slug=my-post` reloads draft content
- [ ] "Write ▾" dropdown opens/closes; closes on outside click
- [ ] "My drafts" navigates to `/drafts`; unauthenticated users redirected to `/login`
- [ ] Drafts list shows title (or "Untitled"), tags, relative timestamp
- [ ] Clicking a draft opens it in the editor
- [ ] `GET /api/v1/me/posts?status=draft` returns only the auth user's drafts
