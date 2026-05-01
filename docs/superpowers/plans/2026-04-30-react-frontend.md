# DevHub React Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a standalone React SPA in `frontend/` that faithfully implements all three DevHub mockups (homepage feed, post detail, editor) with mock data.

**Architecture:** Standalone Vite + React 19 + TypeScript + Tailwind v4 + React Router v7 app in `frontend/` at project root. No Inertia, no Laravel coupling. Mock data in TypeScript; will swap for API calls in a future phase.

**Tech Stack:** Vite 6, React 19, TypeScript 5.7, Tailwind CSS v4, React Router v7, Vitest, clsx, tailwind-merge

---

## File map

```
frontend/
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── src/
    ├── main.tsx
    ├── app.css
    ├── routes.tsx
    ├── types/
    │   └── index.ts                         # Post, Author, Tag interfaces
    ├── lib/
    │   ├── utils.ts                         # cn, readingTime, wordCount, relativeTime
    │   └── utils.test.ts                    # Vitest unit tests
    ├── data/
    │   └── mock.ts                          # mockPosts, mockAuthors, mockTags
    ├── components/
    │   ├── ui/
    │   │   ├── avatar.tsx                   # Avatar (sm/md/lg sizes, custom bg/color)
    │   │   ├── button.tsx                   # Button (default/primary variants)
    │   │   └── tag.tsx                      # Tag pill
    │   └── layout/
    │       └── topbar.tsx                   # Topbar (left/right slots)
    ├── features/
    │   ├── feed/
    │   │   ├── post-card.tsx               # Single feed item
    │   │   ├── trending-sidebar.tsx         # Tag list widget
    │   │   └── newsletter-widget.tsx        # Subscribe widget
    │   ├── post/
    │   │   ├── post-header.tsx             # Tags + title + author meta
    │   │   ├── prose-content.tsx           # Serif HTML content
    │   │   └── reaction-bar.tsx            # Bottom reaction buttons
    │   └── editor/
    │       ├── use-editor-stats.ts         # wordCount/readingTime/codeBlocks hook
    │       ├── use-auto-save.ts            # Debounced save label hook
    │       ├── writing-area.tsx            # Title + subtitle + contenteditable
    │       └── editor-sidebar.tsx          # Stats + tags + visibility
    └── pages/
        ├── home-page.tsx
        ├── post-detail-page.tsx
        └── editor-page.tsx
```

---

## Task 1: Scaffold the project

**Files:**
- Create: `frontend/package.json`
- Create: `frontend/tsconfig.json`
- Create: `frontend/vite.config.ts`
- Create: `frontend/index.html`
- Create: `frontend/src/main.tsx`
- Create: `frontend/src/app.css`

- [ ] **Step 1: Create `frontend/package.json`**

```json
{
  "name": "devhub-frontend",
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "tsc -b && vite build",
    "preview": "vite preview",
    "test": "vitest run",
    "test:watch": "vitest"
  },
  "dependencies": {
    "clsx": "^2.1.1",
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
    "react-router": "^7.0.0",
    "tailwind-merge": "^3.0.1"
  },
  "devDependencies": {
    "@tailwindcss/vite": "^4.1.0",
    "@testing-library/react": "^16.0.0",
    "@types/react": "^19.0.0",
    "@types/react-dom": "^19.0.0",
    "@vitejs/plugin-react": "^4.3.0",
    "jsdom": "^26.0.0",
    "tailwindcss": "^4.1.0",
    "typescript": "^5.7.2",
    "vite": "^6.3.0",
    "vitest": "^3.0.0"
  }
}
```

- [ ] **Step 2: Create `frontend/tsconfig.json`**

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "useDefineForClassFields": true,
    "lib": ["ES2022", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "isolatedModules": true,
    "moduleDetection": "force",
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "noUncheckedSideEffectImports": true
  },
  "include": ["src"]
}
```

- [ ] **Step 3: Create `frontend/vite.config.ts`**

```typescript
/// <reference types="vitest/config" />
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  test: {
    environment: 'jsdom',
    globals: true,
  },
});
```

- [ ] **Step 4: Create `frontend/index.html`**

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DevHub</title>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
```

- [ ] **Step 5: Create `frontend/src/app.css`** — port design tokens from `design-mock/assets/styles.css`

```css
@import "tailwindcss";

@theme {
  --color-bg-primary: #ffffff;
  --color-bg-secondary: #f8f7f4;
  --color-bg-tertiary: #f1efe8;
  --color-bg-info: #e6f1fb;
  --color-text-primary: #1a1a1a;
  --color-text-secondary: #5f5e5a;
  --color-text-tertiary: #888780;
  --color-text-info: #185fa5;
  --color-border-tertiary: rgba(0, 0, 0, 0.08);
  --color-border-secondary: rgba(0, 0, 0, 0.15);
  --font-sans: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", sans-serif;
  --font-serif: "Tiempos Text", "Charter", Georgia, serif;
  --font-mono: "JetBrains Mono", "Menlo", "Consolas", monospace;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
}

*,
*::before,
*::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: var(--font-sans);
  font-size: 16px;
  line-height: 1.7;
  color: var(--color-text-primary);
  background-color: var(--color-bg-tertiary);
}
```

- [ ] **Step 6: Create `frontend/src/main.tsx`** — placeholder router (real routes added in Task 2)

```tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './app.css';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <div style={{ padding: 24 }}>DevHub — scaffold OK</div>
  </StrictMode>,
);
```

- [ ] **Step 7: Install dependencies**

```bash
cd frontend && npm install
```

Expected: `node_modules/` created, no errors.

- [ ] **Step 8: Verify dev server starts**

```bash
cd frontend && npm run dev
```

Expected: Vite starts on `http://localhost:5173`. Open in browser — "DevHub — scaffold OK" visible. Kill with Ctrl+C.

- [ ] **Step 9: Commit**

```bash
cd frontend && git add -A && git commit -m "feat: scaffold devhub frontend (Vite + React + Tailwind v4)"
```

---

## Task 2: Types and utilities

**Files:**
- Create: `frontend/src/types/index.ts`
- Create: `frontend/src/lib/utils.ts`
- Create: `frontend/src/lib/utils.test.ts`

- [ ] **Step 1: Write failing tests first**

Create `frontend/src/lib/utils.test.ts`:

```typescript
import { describe, expect, it } from 'vitest';
import { cn, readingTime, relativeTime, wordCount } from './utils';

describe('cn', () => {
  it('merges class names', () => {
    expect(cn('foo', 'bar')).toBe('foo bar');
  });

  it('resolves tailwind conflicts, keeping last', () => {
    expect(cn('p-2', 'p-4')).toBe('p-4');
  });
});

describe('readingTime', () => {
  it('returns 1 for exactly 200 words', () => {
    expect(readingTime(200)).toBe(1);
  });

  it('rounds up for partial pages', () => {
    expect(readingTime(201)).toBe(2);
  });

  it('returns 0 for 0 words', () => {
    expect(readingTime(0)).toBe(0);
  });
});

describe('wordCount', () => {
  it('counts words', () => {
    expect(wordCount('hello world foo')).toBe(3);
  });

  it('handles extra whitespace', () => {
    expect(wordCount('  hello   world  ')).toBe(2);
  });

  it('returns 0 for empty string', () => {
    expect(wordCount('')).toBe(0);
  });

  it('returns 0 for whitespace-only string', () => {
    expect(wordCount('   ')).toBe(0);
  });
});

describe('relativeTime', () => {
  it('returns "just now" for current time', () => {
    expect(relativeTime(new Date().toISOString())).toBe('just now');
  });

  it('returns hours ago for same-day times', () => {
    const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString();
    expect(relativeTime(twoHoursAgo)).toBe('2 hours ago');
  });

  it('uses singular for 1 hour', () => {
    const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000).toISOString();
    expect(relativeTime(oneHourAgo)).toBe('1 hour ago');
  });

  it('returns "yesterday" for ~25 hours ago', () => {
    const yesterday = new Date(Date.now() - 25 * 60 * 60 * 1000).toISOString();
    expect(relativeTime(yesterday)).toBe('yesterday');
  });

  it('returns "N days ago" for 2-6 days', () => {
    const twoDaysAgo = new Date(Date.now() - 50 * 60 * 60 * 1000).toISOString();
    expect(relativeTime(twoDaysAgo)).toBe('2 days ago');
  });
});
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
cd frontend && npm test
```

Expected: FAIL — "Cannot find module './utils'"

- [ ] **Step 3: Create `frontend/src/types/index.ts`**

```typescript
export interface Author {
  id: string;
  name: string;
  initials: string;
  avatarBg: string;
  avatarColor: string;
}

export interface Post {
  id: string;
  title: string;
  subtitle?: string;
  excerpt: string;
  content: string;
  author: Author;
  publishedAt: string;
  readingMinutes: number;
  wordCount: number;
  tags: string[];
}

export type Tag = string;
```

- [ ] **Step 4: Create `frontend/src/lib/utils.ts`**

```typescript
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}

export function readingTime(words: number): number {
  return Math.ceil(words / 200);
}

export function wordCount(text: string): number {
  return text.trim().split(/\s+/).filter(Boolean).length;
}

export function relativeTime(date: string): string {
  const diffMs = Date.now() - new Date(date).getTime();
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffHours / 24);

  if (diffHours < 1) return 'just now';
  if (diffHours === 1) return '1 hour ago';
  if (diffHours < 24) return `${diffHours} hours ago`;
  if (diffDays === 1) return 'yesterday';
  if (diffDays < 7) return `${diffDays} days ago`;

  return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
```

- [ ] **Step 5: Run tests — verify they pass**

```bash
cd frontend && npm test
```

Expected: All 12 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/types/index.ts frontend/src/lib/utils.ts frontend/src/lib/utils.test.ts
git commit -m "feat: add types and utility functions with tests"
```

---

## Task 3: Mock data

**Files:**
- Create: `frontend/src/data/mock.ts`

- [ ] **Step 1: Create `frontend/src/data/mock.ts`**

```typescript
import type { Author, Post } from '../types';

export const mockAuthors: Author[] = [
  {
    id: 'a1',
    name: 'Sara Rinaldi',
    initials: 'SR',
    avatarBg: '#e6f1fb',
    avatarColor: '#185fa5',
  },
  {
    id: 'a2',
    name: 'Daniel Kobayashi',
    initials: 'DK',
    avatarBg: '#EEEDFE',
    avatarColor: '#3C3489',
  },
  {
    id: 'a3',
    name: 'Anna Meijer',
    initials: 'AM',
    avatarBg: '#E1F5EE',
    avatarColor: '#085041',
  },
  {
    id: 'a4',
    name: 'Joseph Tan',
    initials: 'JT',
    avatarBg: '#FAECE7',
    avatarColor: '#712B13',
  },
];

export const mockTags: string[] = [
  'postgres',
  'rust',
  'distributed-systems',
  'debugging',
  'postmortem',
];

export const mockPosts: Post[] = [
  {
    id: 'p1',
    title:
      'Why we replaced Redis with Postgres for queues — and the three months it took to find out we were wrong',
    subtitle: 'And the three months it took to find out we were wrong',
    excerpt:
      'A long, honest postmortem from a team that chased simplicity into a corner. Includes the migration script, the metrics that lied to us, and what we shipped instead.',
    content: `<p>In late 2024 we made a decision that felt obvious at the time: we'd consolidate our infrastructure by moving our queue workload from Redis onto Postgres. One database, fewer services, simpler ops. The team agreed in twenty minutes.</p>
<p>What followed was three months of progressively worse production incidents, a rewrite of our worker layer, and a careful walk-back to a setup that, in hindsight, almost any senior engineer should have predicted.</p>
<p>This is the long version, with the metrics that misled us, the moment we knew, and the boring final answer.</p>
<h2>The setup we started with</h2>
<p>Our system processed about 8 million jobs a day across four queues: email delivery, webhook fan-out, search indexing, and a long-running ETL pipeline. Redis was holding all of it through a standard library setup...</p>
<pre><code>QUEUE_DRIVER=redis
REDIS_HOST=redis-primary.internal
REDIS_DB_QUEUE=2
QUEUE_RETRY_AFTER=90</code></pre>
<p>It worked well. So well, in fact, that we'd stopped paying attention to it — which is, I now understand, exactly the kind of system you should not move...</p>`,
    author: mockAuthors[0],
    publishedAt: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 22,
    wordCount: 4200,
    tags: ['postgres', 'infrastructure', 'postmortem'],
  },
  {
    id: 'p2',
    title:
      "A patient walk through Rust's borrow checker for people who already know it intellectually but still fight it daily",
    excerpt:
      'No clever metaphors. Just the rules, the failure modes, and the actual mental model that makes them stop feeling arbitrary.',
    content: `<p>There is a particular kind of frustration reserved for the Rust borrow checker. You understand the rules. You can explain them to someone else. And yet, three times a week, the compiler stops you with an error that feels wrong.</p>
<p>This is a walk through the rules as mental models, not definitions. It assumes you've read the book. It assumes you know what ownership means. What it offers is the frame that finally made the errors feel obvious rather than arbitrary.</p>
<h2>The single rule that explains everything</h2>
<p>At any given moment, for any piece of data, you can have either one mutable reference or any number of immutable references — but not both.</p>`,
    author: mockAuthors[1],
    publishedAt: new Date(Date.now() - 30 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 14,
    wordCount: 2800,
    tags: ['rust', 'deep-dive'],
  },
  {
    id: 'p3',
    title:
      'Notes from running a Postgres database with 4 billion rows on a single machine for five years',
    excerpt:
      "What broke first, what surprised us, what we'd do the same again. Includes vacuum tuning that took us two years to land on.",
    content: `<p>Five years ago we made a bet: run our primary database on a single beefy machine instead of distributed infrastructure. The dataset was large. The queries were complex. The conventional wisdom said we were wrong.</p>
<p>We were not wrong. But we were surprised, repeatedly, by what actually broke and what held together better than expected.</p>
<h2>The machine</h2>
<p>We started with 64 cores, 512GB RAM, and 16TB of NVMe. The database grew from 800M rows to 4.1B rows over the period. At no point did we shard.</p>`,
    author: mockAuthors[2],
    publishedAt: new Date(Date.now() - 54 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 31,
    wordCount: 6200,
    tags: ['postgres', 'scaling', 'war-stories'],
  },
  {
    id: 'p4',
    title: 'Building a small CRDT from scratch to finally understand what one is',
    excerpt:
      'Step-by-step construction with code, diagrams, and three failed attempts before the working version.',
    content: `<p>I spent two years nodding along whenever someone mentioned CRDTs. I'd read the Wikipedia article three times. I understood the acronym. I could not have implemented one.</p>
<p>This is the process of building one from scratch — not the cleanest version, but the version where I finally understood why the rules are what they are.</p>
<h2>What we're building</h2>
<p>A grow-only counter: the simplest possible CRDT. Two nodes. Each can increment independently. Both must eventually agree on the total.</p>`,
    author: mockAuthors[3],
    publishedAt: new Date(Date.now() - 78 * 60 * 60 * 1000).toISOString(),
    readingMinutes: 18,
    wordCount: 3600,
    tags: ['distributed-systems', 'tutorial'],
  },
];
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/data/mock.ts
git commit -m "feat: add mock posts, authors, and tags"
```

---

## Task 4: UI primitives

**Files:**
- Create: `frontend/src/components/ui/avatar.tsx`
- Create: `frontend/src/components/ui/button.tsx`
- Create: `frontend/src/components/ui/tag.tsx`
- Create: `frontend/src/components/layout/topbar.tsx`

- [ ] **Step 1: Create `frontend/src/components/ui/avatar.tsx`**

```tsx
import { cn } from '../../lib/utils';

interface AvatarProps {
  initials: string;
  bg?: string;
  color?: string;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const sizeClasses = {
  sm: 'w-6 h-6 text-[10px]',
  md: 'w-8 h-8 text-xs',
  lg: 'w-9 h-9 text-[13px]',
};

export function Avatar({ initials, bg, color, size = 'md', className }: AvatarProps) {
  return (
    <div
      className={cn(
        'rounded-full flex items-center justify-center font-medium shrink-0',
        sizeClasses[size],
        className,
      )}
      style={{
        backgroundColor: bg ?? 'var(--color-bg-info)',
        color: color ?? 'var(--color-text-info)',
      }}
    >
      {initials}
    </div>
  );
}
```

- [ ] **Step 2: Create `frontend/src/components/ui/button.tsx`**

```tsx
import { cn } from '../../lib/utils';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'primary';
}

export function Button({ variant = 'default', className, children, ...props }: ButtonProps) {
  return (
    <button
      className={cn(
        'h-8 px-3.5 text-sm rounded-[var(--radius-md)] border cursor-pointer transition-colors',
        variant === 'default' && [
          'border-[var(--color-border-tertiary)] bg-transparent text-[var(--color-text-primary)]',
          'hover:bg-[var(--color-bg-secondary)]',
        ],
        variant === 'primary' && [
          'border-[var(--color-text-primary)] bg-[var(--color-text-primary)] text-[var(--color-bg-primary)]',
          'hover:opacity-90',
        ],
        className,
      )}
      {...props}
    >
      {children}
    </button>
  );
}
```

- [ ] **Step 3: Create `frontend/src/components/ui/tag.tsx`**

```tsx
import { cn } from '../../lib/utils';

interface TagProps {
  children: React.ReactNode;
  className?: string;
}

export function Tag({ children, className }: TagProps) {
  return (
    <span
      className={cn(
        'inline-block text-xs px-2.5 py-0.5 rounded-[var(--radius-md)]',
        'bg-[var(--color-bg-secondary)] text-[var(--color-text-secondary)]',
        className,
      )}
    >
      {children}
    </span>
  );
}
```

- [ ] **Step 4: Create `frontend/src/components/layout/topbar.tsx`**

```tsx
interface TopbarProps {
  left: React.ReactNode;
  right: React.ReactNode;
}

export function Topbar({ left, right }: TopbarProps) {
  return (
    <header
      className="flex items-center justify-between px-6 py-3.5"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        borderBottom: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div className="flex items-center gap-7">{left}</div>
      <div className="flex items-center gap-3">{right}</div>
    </header>
  );
}
```

- [ ] **Step 5: Commit**

```bash
git add frontend/src/components/
git commit -m "feat: add Avatar, Button, Tag, Topbar primitives"
```

---

## Task 5: Feed feature components

**Files:**
- Create: `frontend/src/features/feed/post-card.tsx`
- Create: `frontend/src/features/feed/trending-sidebar.tsx`
- Create: `frontend/src/features/feed/newsletter-widget.tsx`

- [ ] **Step 1: Create `frontend/src/features/feed/post-card.tsx`**

```tsx
import { Link } from 'react-router';
import { Avatar } from '../../components/ui/avatar';
import { Tag } from '../../components/ui/tag';
import { relativeTime } from '../../lib/utils';
import type { Post } from '../../types';

interface PostCardProps {
  post: Post;
}

export function PostCard({ post }: PostCardProps) {
  return (
    <article
      className="rounded-[var(--radius-lg)] p-4 md:p-5"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div
        className="flex items-center gap-2.5 mb-2.5 text-[13px]"
        style={{ color: 'var(--color-text-secondary)' }}
      >
        <Avatar
          initials={post.author.initials}
          bg={post.author.avatarBg}
          color={post.author.avatarColor}
          size="sm"
        />
        <span style={{ color: 'var(--color-text-primary)', fontWeight: 500 }}>
          {post.author.name}
        </span>
        <span>·</span>
        <span>{relativeTime(post.publishedAt)}</span>
        <span>·</span>
        <span>{post.readingMinutes} min read</span>
      </div>

      <Link
        to={`/posts/${post.id}`}
        className="block no-underline"
        style={{ color: 'inherit' }}
      >
        <h3
          className="text-[18px] font-medium leading-snug mb-2"
          style={{ margin: '0 0 8px' }}
        >
          {post.title}
        </h3>
      </Link>

      <p
        className="text-sm leading-relaxed mb-3"
        style={{ color: 'var(--color-text-secondary)', margin: '0 0 12px' }}
      >
        {post.excerpt}
      </p>

      <div className="flex gap-1.5 flex-wrap">
        {post.tags.map((tag) => (
          <Tag key={tag}>{tag}</Tag>
        ))}
      </div>
    </article>
  );
}
```

- [ ] **Step 2: Create `frontend/src/features/feed/trending-sidebar.tsx`**

```tsx
interface TrendingSidebarProps {
  tags: string[];
}

export function TrendingSidebar({ tags }: TrendingSidebarProps) {
  return (
    <div
      className="rounded-[var(--radius-lg)] p-4 md:p-5"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <p
        className="text-[13px] font-medium mb-2.5"
        style={{ color: 'var(--color-text-tertiary)' }}
      >
        Quietly trending
      </p>
      <div className="flex flex-col gap-1.5">
        {tags.map((tag) => (
          <span key={tag} className="text-[13px]">
            {tag}
          </span>
        ))}
      </div>
    </div>
  );
}
```

- [ ] **Step 3: Create `frontend/src/features/feed/newsletter-widget.tsx`**

```tsx
import { Button } from '../../components/ui/button';

export function NewsletterWidget() {
  return (
    <div
      className="rounded-[var(--radius-lg)] px-4 py-3.5"
      style={{ backgroundColor: 'var(--color-bg-secondary)' }}
    >
      <p className="text-[13px] font-medium mb-1.5">Weekly digest, Mondays</p>
      <p
        className="text-xs leading-relaxed mb-2.5"
        style={{ color: 'var(--color-text-secondary)' }}
      >
        One email, the deepest things published this week. No daily nags.
      </p>
      <Button className="text-xs h-7">Subscribe</Button>
    </div>
  );
}
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/feed/
git commit -m "feat: add PostCard, TrendingSidebar, NewsletterWidget"
```

---

## Task 6: HomePage

**Files:**
- Create: `frontend/src/pages/home-page.tsx`
- Create: `frontend/src/routes.tsx`
- Modify: `frontend/src/main.tsx`

- [ ] **Step 1: Create `frontend/src/pages/home-page.tsx`**

```tsx
import { Link } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Topbar } from '../components/layout/topbar';
import { NewsletterWidget } from '../features/feed/newsletter-widget';
import { PostCard } from '../features/feed/post-card';
import { TrendingSidebar } from '../features/feed/trending-sidebar';
import { mockPosts, mockTags } from '../data/mock';

const NAV_LINKS = [
  { label: 'Home', to: '/', active: true },
  { label: 'Following', to: '/following' },
  { label: 'Tags', to: '/tags' },
  { label: 'Bookmarks', to: '/bookmarks' },
];

export function HomePage() {
  return (
    <div
      style={{
        maxWidth: 1080,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <>
            <span
              style={{
                fontFamily: 'var(--font-serif)',
                fontSize: 18,
                fontWeight: 500,
              }}
            >
              DevHub
            </span>
            <nav className="flex gap-5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
              {NAV_LINKS.map(({ label, to, active }) => (
                <Link
                  key={label}
                  to={to}
                  style={{
                    color: active ? 'var(--color-text-primary)' : undefined,
                    fontWeight: active ? 500 : undefined,
                    textDecoration: 'none',
                  }}
                >
                  {label}
                </Link>
              ))}
            </nav>
          </>
        }
        right={
          <>
            <input
              type="search"
              placeholder="Search..."
              className="h-8 px-3 text-sm rounded-[var(--radius-md)]"
              style={{
                width: 180,
                border: '0.5px solid var(--color-border-tertiary)',
                backgroundColor: 'var(--color-bg-primary)',
              }}
            />
            <Avatar initials="VK" size="md" />
          </>
        }
      />

      <div
        className="grid gap-6 p-6"
        style={{ gridTemplateColumns: 'minmax(0, 1fr) 220px' }}
      >
        <main>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-medium m-0">Recently published</h2>
            <span
              className="text-[13px]"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              Sorted by depth, not engagement
            </span>
          </div>

          <div className="flex flex-col gap-3">
            {mockPosts.map((post) => (
              <PostCard key={post.id} post={post} />
            ))}
          </div>
        </main>

        <aside className="flex flex-col gap-4">
          <TrendingSidebar tags={mockTags} />
          <NewsletterWidget />
        </aside>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Create `frontend/src/routes.tsx`**

```tsx
import { createBrowserRouter } from 'react-router';
import { HomePage } from './pages/home-page';

export const router = createBrowserRouter([
  {
    path: '/',
    element: <HomePage />,
  },
]);
```

- [ ] **Step 3: Update `frontend/src/main.tsx`** to use RouterProvider

```tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router';
import './app.css';
import { router } from './routes';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <RouterProvider router={router} />
  </StrictMode>,
);
```

- [ ] **Step 4: Start dev server and verify homepage renders**

```bash
cd frontend && npm run dev
```

Open `http://localhost:5173`. Check:
- Brand "DevHub" in serif
- Nav: Home / Following / Tags / Bookmarks
- Search input and avatar in header
- 4 post cards with author avatars, titles, excerpts, tags
- Sidebar with "Quietly trending" tags + newsletter widget

Kill with Ctrl+C.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/pages/home-page.tsx frontend/src/routes.tsx frontend/src/main.tsx
git commit -m "feat: add HomePage with feed, sidebar, and routing"
```

---

## Task 7: Post detail feature components

**Files:**
- Create: `frontend/src/features/post/post-header.tsx`
- Create: `frontend/src/features/post/prose-content.tsx`
- Create: `frontend/src/features/post/reaction-bar.tsx`

- [ ] **Step 1: Create `frontend/src/features/post/post-header.tsx`**

```tsx
import { Avatar } from '../../components/ui/avatar';
import { Tag } from '../../components/ui/tag';
import { relativeTime } from '../../lib/utils';
import type { Post } from '../../types';

interface PostHeaderProps {
  post: Post;
}

export function PostHeader({ post }: PostHeaderProps) {
  return (
    <div>
      <div className="flex gap-1.5 flex-wrap mb-4">
        {post.tags.map((tag) => (
          <Tag key={tag}>{tag}</Tag>
        ))}
      </div>

      <h1
        className="font-medium leading-tight mb-4"
        style={{ fontSize: 28, margin: '0 0 16px' }}
      >
        {post.title}
      </h1>

      <div
        className="flex items-center gap-3 pb-6"
        style={{ borderBottom: '0.5px solid var(--color-border-tertiary)', marginBottom: 32 }}
      >
        <Avatar
          initials={post.author.initials}
          bg={post.author.avatarBg}
          color={post.author.avatarColor}
          size="lg"
        />
        <div>
          <p className="m-0 text-sm font-medium">{post.author.name}</p>
          <p className="m-0 text-[13px]" style={{ color: 'var(--color-text-secondary)' }}>
            Published {relativeTime(post.publishedAt)} · {post.readingMinutes} min read ·{' '}
            {post.wordCount.toLocaleString()} words
          </p>
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Create `frontend/src/features/post/prose-content.tsx`**

```tsx
interface ProseContentProps {
  html: string;
}

export function ProseContent({ html }: ProseContentProps) {
  return (
    <div
      className="prose-content"
      style={{
        fontFamily: 'var(--font-serif)',
        fontSize: 18,
        lineHeight: 1.7,
      }}
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}
```

Add prose styles to `frontend/src/app.css` (append after `body` block):

```css
.prose-content p {
  margin: 0 0 18px;
}

.prose-content h2 {
  font-size: 20px;
  font-weight: 500;
  margin: 32px 0 12px;
  font-family: var(--font-sans);
}

.prose-content pre {
  background: #1a1a1a;
  border-radius: var(--radius-md);
  padding: 16px 20px;
  margin: 20px 0;
  font-family: var(--font-mono);
  font-size: 13px;
  line-height: 1.6;
  color: #e0e0e0;
  overflow-x: auto;
}

.prose-content code {
  font-family: var(--font-mono);
  font-size: 0.9em;
}
```

- [ ] **Step 3: Create `frontend/src/features/post/reaction-bar.tsx`**

```tsx
import { Button } from '../../components/ui/button';

const REACTIONS = ['Insightful', 'Mind-blown', 'Fire', 'Heart', 'Like'];

export function ReactionBar() {
  return (
    <div
      className="px-16 py-6"
      style={{
        backgroundColor: 'var(--color-bg-tertiary)',
        borderTop: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div style={{ maxWidth: 580, margin: '0 auto' }}>
        <div className="flex items-center gap-2 mb-4">
          <p className="m-0 text-sm font-medium">Reactions</p>
          <p className="m-0 text-xs" style={{ color: 'var(--color-text-tertiary)' }}>
            visible after you finish reading
          </p>
        </div>
        <div className="flex gap-2 flex-wrap">
          {REACTIONS.map((reaction) => (
            <Button key={reaction}>{reaction}</Button>
          ))}
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/post/ frontend/src/app.css
git commit -m "feat: add PostHeader, ProseContent, ReactionBar"
```

---

## Task 8: PostDetailPage

**Files:**
- Create: `frontend/src/pages/post-detail-page.tsx`
- Modify: `frontend/src/routes.tsx`

- [ ] **Step 1: Create `frontend/src/pages/post-detail-page.tsx`**

```tsx
import { Link, useParams } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { PostHeader } from '../features/post/post-header';
import { ProseContent } from '../features/post/prose-content';
import { ReactionBar } from '../features/post/reaction-bar';
import { mockPosts } from '../data/mock';

export function PostDetailPage() {
  const { id } = useParams<{ id: string }>();
  const post = mockPosts.find((p) => p.id === id);

  if (!post) {
    return (
      <div style={{ padding: 40, textAlign: 'center' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>Post not found.</p>
        <Link to="/" style={{ color: 'var(--color-text-primary)' }}>
          ← Back to feed
        </Link>
      </div>
    );
  }

  return (
    <div
      style={{
        maxWidth: 1080,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <Link
            to="/"
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 18,
              fontWeight: 500,
              textDecoration: 'none',
              color: 'inherit',
            }}
          >
            DevHub
          </Link>
        }
        right={
          <>
            <Button>Bookmark</Button>
            <Avatar initials="VK" size="md" />
          </>
        }
      />

      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          padding: '40px 64px',
        }}
      >
        <div style={{ maxWidth: 580, margin: '0 auto' }}>
          <PostHeader post={post} />
          <ProseContent html={post.content} />
        </div>
      </div>

      <ReactionBar />
    </div>
  );
}
```

- [ ] **Step 2: Add route to `frontend/src/routes.tsx`**

```tsx
import { createBrowserRouter } from 'react-router';
import { HomePage } from './pages/home-page';
import { PostDetailPage } from './pages/post-detail-page';

export const router = createBrowserRouter([
  {
    path: '/',
    element: <HomePage />,
  },
  {
    path: '/posts/:id',
    element: <PostDetailPage />,
  },
]);
```

- [ ] **Step 3: Start dev server and verify post detail**

```bash
cd frontend && npm run dev
```

Open `http://localhost:5173`. Click first post card title. Check:
- Topbar: "DevHub" brand (links back to `/`), Bookmark button, avatar
- Tags row above title
- Author avatar, name, "Published X ago · 22 min read · 4,200 words"
- Serif prose content with dark code block
- Reactions bar at bottom with 5 buttons

Kill with Ctrl+C.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/pages/post-detail-page.tsx frontend/src/routes.tsx
git commit -m "feat: add PostDetailPage with reading view and reactions"
```

---

## Task 9: Editor hooks

**Files:**
- Create: `frontend/src/features/editor/use-editor-stats.ts`
- Create: `frontend/src/features/editor/use-auto-save.ts`

- [ ] **Step 1: Create `frontend/src/features/editor/use-editor-stats.ts`**

```typescript
import { useMemo } from 'react';
import { readingTime, wordCount } from '../../lib/utils';

interface EditorStats {
  wordCount: number;
  readingMinutes: number;
  codeBlockCount: number;
}

export function useEditorStats(content: string): EditorStats {
  return useMemo(() => {
    const words = wordCount(content);
    const codeBlockMatches = content.match(/```/g) ?? [];
    const codeBlockCount = Math.floor(codeBlockMatches.length / 2);
    return {
      wordCount: words,
      readingMinutes: readingTime(words),
      codeBlockCount,
    };
  }, [content]);
}
```

- [ ] **Step 2: Create `frontend/src/features/editor/use-auto-save.ts`**

```typescript
import { useEffect, useRef, useState } from 'react';

export function useAutoSave(content: string, delayMs = 3000): string {
  const [savedAt, setSavedAt] = useState<Date | null>(null);
  const [secondsAgo, setSecondsAgo] = useState(0);
  const saveTimer = useRef<ReturnType<typeof setTimeout>>();

  useEffect(() => {
    clearTimeout(saveTimer.current);
    saveTimer.current = setTimeout(() => {
      setSavedAt(new Date());
      setSecondsAgo(0);
    }, delayMs);
    return () => clearTimeout(saveTimer.current);
  }, [content, delayMs]);

  useEffect(() => {
    if (!savedAt) return;
    const interval = setInterval(() => {
      setSecondsAgo(Math.floor((Date.now() - savedAt.getTime()) / 1000));
    }, 1000);
    return () => clearInterval(interval);
  }, [savedAt]);

  if (!savedAt) return 'Draft';
  if (secondsAgo < 5) return 'Draft · saved just now';
  return `Draft · saved ${secondsAgo} seconds ago`;
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/editor/
git commit -m "feat: add useEditorStats and useAutoSave hooks"
```

---

## Task 10: Editor feature components

**Files:**
- Create: `frontend/src/features/editor/writing-area.tsx`
- Create: `frontend/src/features/editor/editor-sidebar.tsx`

- [ ] **Step 1: Create `frontend/src/features/editor/writing-area.tsx`**

```tsx
import { useRef } from 'react';

interface WritingAreaProps {
  title: string;
  subtitle: string;
  onTitleChange: (value: string) => void;
  onSubtitleChange: (value: string) => void;
  onContentChange: (value: string) => void;
}

export function WritingArea({
  title,
  subtitle,
  onTitleChange,
  onSubtitleChange,
  onContentChange,
}: WritingAreaProps) {
  const contentRef = useRef<HTMLDivElement>(null);

  function handleContentInput() {
    if (contentRef.current) {
      onContentChange(contentRef.current.innerText);
    }
  }

  return (
    <div
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        padding: '32px 48px',
        minHeight: 520,
      }}
    >
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
          margin: '0 0 24px',
          color: 'var(--color-text-secondary)',
          outline: 'none',
          fontFamily: 'inherit',
        }}
      />

      <div
        style={{ borderTop: '0.5px solid var(--color-border-tertiary)', paddingTop: 24 }}
      >
        <div
          ref={contentRef}
          contentEditable
          suppressContentEditableWarning
          onInput={handleContentInput}
          style={{
            outline: 'none',
            fontFamily: 'var(--font-serif)',
            fontSize: 18,
            lineHeight: 1.7,
            minHeight: 300,
            color: 'var(--color-text-primary)',
          }}
          data-placeholder="Start writing..."
        />
      </div>
    </div>
  );
}
```

Add placeholder style to `frontend/src/app.css`:

```css
[contenteditable][data-placeholder]:empty::before {
  content: attr(data-placeholder);
  color: var(--color-text-tertiary);
  pointer-events: none;
}
```

- [ ] **Step 2: Create `frontend/src/features/editor/editor-sidebar.tsx`**

```tsx
import { useState } from 'react';
import { Button } from '../../components/ui/button';
import { Tag } from '../../components/ui/tag';
import type { EditorStats } from './use-editor-stats';

interface EditorSidebarProps {
  stats: EditorStats;
  tags: string[];
  onTagsChange: (tags: string[]) => void;
}

const MAX_TAGS = 5;

export function EditorSidebar({ stats, tags, onTagsChange }: EditorSidebarProps) {
  const [tagInput, setTagInput] = useState('');

  function handleTagKeyDown(e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key !== 'Enter' && e.key !== ',') return;
    e.preventDefault();
    const value = tagInput.trim().toLowerCase().replace(/\s+/g, '-');
    if (value && !tags.includes(value) && tags.length < MAX_TAGS) {
      onTagsChange([...tags, value]);
    }
    setTagInput('');
  }

  function removeTag(tag: string) {
    onTagsChange(tags.filter((t) => t !== tag));
  }

  return (
    <aside
      style={{
        backgroundColor: 'var(--color-bg-secondary)',
        padding: '24px 20px',
        borderLeft: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <StatRow label="Reading time" value={`${stats.readingMinutes} min`} />
      <StatRow label="Word count" value={stats.wordCount.toString()} />
      <StatRow label="Code blocks" value={stats.codeBlockCount.toString()} />

      <div
        style={{
          borderTop: '0.5px solid var(--color-border-tertiary)',
          paddingTop: 20,
          marginBottom: 24,
        }}
      >
        <p
          className="text-xs font-medium mb-2"
          style={{ color: 'var(--color-text-tertiary)', margin: '0 0 8px' }}
        >
          Tags
        </p>
        <div className="flex gap-1 flex-wrap mb-2">
          {tags.map((tag) => (
            <button
              key={tag}
              onClick={() => removeTag(tag)}
              title="Remove tag"
              style={{
                all: 'unset',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: 4,
              }}
            >
              <Tag className="cursor-pointer">{tag} ×</Tag>
            </button>
          ))}
        </div>
        {tags.length < MAX_TAGS && (
          <input
            type="text"
            placeholder="Add a tag..."
            value={tagInput}
            onChange={(e) => setTagInput(e.target.value)}
            onKeyDown={handleTagKeyDown}
            style={{
              width: '100%',
              height: 30,
              fontSize: 12,
              padding: '0 10px',
              border: '0.5px solid var(--color-border-tertiary)',
              borderRadius: 'var(--radius-md)',
              backgroundColor: 'var(--color-bg-primary)',
              fontFamily: 'inherit',
            }}
          />
        )}
        <p
          className="text-[11px] mt-2"
          style={{ color: 'var(--color-text-tertiary)', margin: '8px 0 0' }}
        >
          {tags.length} of {MAX_TAGS} tags used
        </p>
      </div>

      <div style={{ borderTop: '0.5px solid var(--color-border-tertiary)', paddingTop: 20 }}>
        <p
          className="text-xs font-medium mb-1"
          style={{ color: 'var(--color-text-tertiary)', margin: '0 0 4px' }}
        >
          Visibility
        </p>
        <p className="text-[13px] m-0">Public when published</p>
        <p
          className="text-xs leading-relaxed"
          style={{ color: 'var(--color-text-tertiary)', margin: '4px 0 0' }}
        >
          Drafts are only visible to you.
        </p>
      </div>
    </aside>
  );
}

function StatRow({ label, value }: { label: string; value: string }) {
  return (
    <div style={{ marginBottom: 24 }}>
      <p
        className="text-xs font-medium"
        style={{ color: 'var(--color-text-tertiary)', margin: '0 0 4px' }}
      >
        {label}
      </p>
      <p className="text-lg font-medium m-0">{value}</p>
    </div>
  );
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/editor/ frontend/src/app.css
git commit -m "feat: add WritingArea and EditorSidebar components"
```

---

## Task 11: EditorPage

**Files:**
- Create: `frontend/src/pages/editor-page.tsx`
- Modify: `frontend/src/routes.tsx`

- [ ] **Step 1: Create `frontend/src/pages/editor-page.tsx`**

```tsx
import { useState } from 'react';
import { Link } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { EditorSidebar } from '../features/editor/editor-sidebar';
import { useAutoSave } from '../features/editor/use-auto-save';
import { useEditorStats } from '../features/editor/use-editor-stats';
import { WritingArea } from '../features/editor/writing-area';

export function EditorPage() {
  const [title, setTitle] = useState('');
  const [subtitle, setSubtitle] = useState('');
  const [content, setContent] = useState('');
  const [tags, setTags] = useState<string[]>([]);

  const stats = useEditorStats(content);
  const draftLabel = useAutoSave(title + content);

  return (
    <div
      style={{
        maxWidth: 1080,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <>
            <Link
              to="/"
              style={{
                fontFamily: 'var(--font-serif)',
                fontSize: 16,
                fontWeight: 500,
                textDecoration: 'none',
                color: 'inherit',
              }}
            >
              DevHub
            </Link>
            <span className="text-[13px]" style={{ color: 'var(--color-text-secondary)' }}>
              {draftLabel}
            </span>
          </>
        }
        right={
          <>
            <Button>Preview</Button>
            <Button variant="primary">Publish ↗</Button>
          </>
        }
      />

      <div style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1fr) 240px' }}>
        <WritingArea
          title={title}
          subtitle={subtitle}
          onTitleChange={setTitle}
          onSubtitleChange={setSubtitle}
          onContentChange={setContent}
        />
        <EditorSidebar stats={stats} tags={tags} onTagsChange={setTags} />
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Add editor route to `frontend/src/routes.tsx`**

```tsx
import { createBrowserRouter } from 'react-router';
import { HomePage } from './pages/home-page';
import { PostDetailPage } from './pages/post-detail-page';
import { EditorPage } from './pages/editor-page';

export const router = createBrowserRouter([
  {
    path: '/',
    element: <HomePage />,
  },
  {
    path: '/posts/:id',
    element: <PostDetailPage />,
  },
  {
    path: '/editor',
    element: <EditorPage />,
  },
]);
```

- [ ] **Step 3: Start dev server and verify editor**

```bash
cd frontend && npm run dev
```

Open `http://localhost:5173/editor`. Check:
- Topbar: "DevHub" brand, "Draft" status (updates to "saved just now" after typing + 3s pause), Preview + Publish buttons
- Title input (large, unstyled)
- Subtitle input (smaller, muted)
- Writing area (contenteditable with placeholder "Start writing...")
- Sidebar: word count and reading time update live as you type
- Tags: type a tag and press Enter — it appears; click tag to remove
- Visibility section at bottom

Kill with Ctrl+C.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/pages/editor-page.tsx frontend/src/routes.tsx
git commit -m "feat: add EditorPage with live stats and auto-save label"
```

---

## Task 12: Final build verification

- [ ] **Step 1: Run full test suite**

```bash
cd frontend && npm test
```

Expected: All tests PASS (12 tests in `src/lib/utils.test.ts`).

- [ ] **Step 2: Run TypeScript check**

```bash
cd frontend && npx tsc --noEmit
```

Expected: No errors.

- [ ] **Step 3: Run production build**

```bash
cd frontend && npm run build
```

Expected: `dist/` created, no errors. Output should show JS/CSS bundle sizes.

- [ ] **Step 4: Verify production preview**

```bash
cd frontend && npm run preview
```

Open `http://localhost:4173`. Spot check:
- Homepage loads with 4 posts
- Click a post → detail page renders
- Navigate to `/editor` → editor works
- Browser back/forward navigation works

Kill with Ctrl+C.

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "chore: verify devhub frontend build and tests pass"
```
