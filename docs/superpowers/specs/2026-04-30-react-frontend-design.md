# DevHub React Frontend Design

**Date:** 2026-04-30  
**Status:** Approved  
**Scope:** Standalone React SPA in `frontend/` at project root. Mock data only — no API calls yet.

---

## Context

DevHub is a developer blog platform (homepage feed, post detail, post editor). Design mocks live in `design-mock/`. This spec covers building the React frontend as a standalone app separate from the existing Inertia.js setup in `resources/js/`.

---

## Architecture

**Stack:** Vite + React 19 + TypeScript + Tailwind CSS v4 + React Router v7  
**Location:** `frontend/` at project root  
**Relationship to existing code:** Parallel to `resources/js/`. No shared build. No Inertia. Will connect to Laravel API (`/api/v1/`) in a future phase.

### Folder structure

```
frontend/
├── src/
│   ├── components/
│   │   ├── ui/            # Button, Tag, Avatar, Input — low-level primitives
│   │   └── layout/        # Topbar, AppShell
│   ├── features/
│   │   ├── feed/          # PostCard, FeedList, TrendingSidebar, NewsletterWidget
│   │   ├── post/          # PostHeader, ProseContent, ReactionBar
│   │   └── editor/        # TitleInput, WritingArea, EditorSidebar, TagInput
│   ├── data/              # mockPosts, mockAuthors, mockTags — typed TypeScript
│   ├── lib/               # cn(), readingTime(words), wordCount(text)
│   ├── types/             # Post, Author, Tag interfaces
│   ├── routes.tsx         # React Router v7 route tree
│   └── main.tsx
├── index.html
├── package.json
├── tsconfig.json
└── vite.config.ts
```

---

## Design tokens

All tokens from `design-mock/assets/styles.css` port into Tailwind v4 `@theme` block in `src/app.css`:

```css
@theme {
  --color-bg-primary: #ffffff;
  --color-bg-secondary: #f8f7f4;
  --color-bg-tertiary: #f1efe8;
  --color-text-primary: #1a1a1a;
  --color-text-secondary: #5f5e5a;
  --color-text-tertiary: #888780;
  --font-sans: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", sans-serif;
  --font-serif: "Tiempos Text", "Charter", Georgia, serif;
  --font-mono: "JetBrains Mono", "Menlo", "Consolas", monospace;
  --radius-md: 8px;
  --radius-lg: 12px;
}
```

---

## Pages

### Route table

| Route | Component | Data source |
|---|---|---|
| `/` | `HomePage` | `mockPosts`, `mockTags` |
| `/posts/:id` | `PostDetailPage` | `mockPosts` (find by id) |
| `/editor` | `EditorPage` | local state only |

### Homepage (`/`)

Two-column grid (feed + sidebar), matching `01-homepage.html`:

- **Header:** brand, nav links (Home / Following / Tags / Bookmarks), search input, avatar
- **Feed:** `PostCard` repeated per post — author avatar + initials, author name, relative time, reading time, title, excerpt, tags
- **Sidebar:** `TrendingSidebar` (tag list), `NewsletterWidget` (subscribe button)
- **Feed label:** "Recently published" + "Sorted by depth, not engagement" subtitle

### Post detail (`/posts/:id`)

Narrow reading column (`max-width: 580px`), matching `02-post-detail.html`:

- **Header:** brand only left, Bookmark button + avatar right
- **Tags** row above title
- **Title** (h1, sans-serif, 28px)
- **Author meta:** large avatar, name, "Published X ago · Y min read · Z words"
- **Prose content:** serif font, 18px, 1.7 line-height — static HTML from mock data
- **Reactions section** (pinned bottom bar): "Reactions — visible after you finish reading" + 5 reaction buttons (Insightful, Mind-blown, Fire, Heart, Like)

### Editor (`/editor`)

Two-column split (writing area + metadata sidebar), matching `03-editor.html`:

- **Header:** brand + "Draft · saved N seconds ago" status, Preview button, Publish button (primary)
- **Writing area:**
  - `TitleInput` — unstyled large input (26px, no border)
  - `SubtitleInput` — unstyled smaller input (16px, muted)
  - `WritingArea` — `contenteditable` div with serif prose styles
- **Sidebar:**
  - Reading time (derived: `Math.ceil(wordCount / 200)` min)
  - Word count (live from contenteditable content)
  - Code block count (count of ` ``` ` occurrences)
  - Tags (up to 5, add/remove, "N of 5 tags used")
  - Visibility ("Public when published")
- **Auto-save simulation:** `useEffect` with 3s debounce sets "saved N seconds ago" label

---

## Mock data

4 posts seeded from mockup content:

```typescript
interface Author {
  id: string;
  name: string;
  initials: string;
  avatarBg: string;
  avatarColor: string;
}

interface Post {
  id: string;
  title: string;
  subtitle?: string;
  excerpt: string;
  content: string;        // HTML string for prose rendering
  author: Author;
  publishedAt: string;    // ISO date string
  readingMinutes: number;
  wordCount: number;
  tags: string[];
}
```

Authors have distinct avatar accent colors matching the mockup (blue default, purple, green, orange).

---

## Key utility functions

- `cn(...classes)` — clsx + tailwind-merge
- `readingTime(wordCount: number): number` — `Math.ceil(wordCount / 200)`
- `wordCount(text: string): number` — split on whitespace, filter empty
- `relativeTime(date: string): string` — "3 hours ago", "yesterday", "2 days ago" etc.

---

## What this spec excludes

- Auth / user accounts
- API integration
- Routing guards
- Search functionality (input renders, no logic)
- Following / Bookmarks pages (nav links render, routes 404)
- Rich text editor (no TipTap — contenteditable only)
- Dark mode
