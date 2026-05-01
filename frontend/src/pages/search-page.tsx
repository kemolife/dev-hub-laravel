import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Tag } from '../components/ui/tag';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { relativeTime } from '../lib/utils';
import type { ApiPost, PaginationMeta } from '../types';

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';

interface SearchResponse {
  data: ApiPost[];
  meta: PaginationMeta;
}

function SkeletonCard() {
  return (
    <div
      className="rounded-[var(--radius-lg)] p-4 md:p-5 animate-pulse"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div className="flex items-center gap-2.5 mb-3">
        <div
          className="w-6 h-6 rounded-full"
          style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
        />
        <div
          className="h-3 w-24 rounded"
          style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
        />
        <div
          className="h-3 w-16 rounded"
          style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
        />
      </div>
      <div
        className="h-5 w-3/4 rounded mb-2"
        style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
      />
      <div
        className="h-4 w-full rounded mb-1"
        style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
      />
      <div
        className="h-4 w-2/3 rounded"
        style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
      />
    </div>
  );
}

function ApiPostCard({ post }: { post: ApiPost }) {
  const initials = post.author.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

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
        <Avatar initials={initials} size="sm" />
        <span style={{ color: 'var(--color-text-primary)', fontWeight: 500 }}>
          {post.author.name}
        </span>
        {post.published_at && (
          <>
            <span>·</span>
            <span>{relativeTime(post.published_at)}</span>
          </>
        )}
        <span>·</span>
        <span>{post.reading_time_minutes} min read</span>
      </div>

      <Link
        to={`/posts/${post.slug}`}
        className="block no-underline"
        style={{ color: 'inherit' }}
      >
        <h3
          className="text-[18px] font-medium leading-snug"
          style={{ margin: '0 0 8px' }}
        >
          {post.title}
        </h3>
      </Link>

      <p
        className="text-sm leading-relaxed"
        style={{ color: 'var(--color-text-secondary)', margin: '0 0 12px' }}
      >
        {post.excerpt}
      </p>

      <div className="flex gap-1.5 flex-wrap">
        {post.tags.map((tag) => (
          <Link key={tag.id} to={`/tags/${tag.slug}`} style={{ textDecoration: 'none' }}>
            <Tag>{tag.name}</Tag>
          </Link>
        ))}
      </div>
    </article>
  );
}

export function SearchPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const { user, logout } = useAuth();

  const initialQuery = searchParams.get('q') ?? '';
  const [inputValue, setInputValue] = useState(initialQuery);
  const [posts, setPosts] = useState<ApiPost[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const fetchResults = useCallback(async (query: string) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({ page: '1' });
      if (query) {
        params.set('q', query);
      }
      const res = await fetch(`${BASE_URL}/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) throw new Error('Search failed');
      const json: SearchResponse = await res.json();
      setPosts(json.data);
      setMeta(json.meta);
    } catch {
      setPosts([]);
      setMeta(null);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    const query = searchParams.get('q') ?? '';
    setInputValue(query);
    fetchResults(query);
  }, [searchParams, fetchResults]);

  function handleInputChange(e: React.ChangeEvent<HTMLInputElement>) {
    const value = e.target.value;
    setInputValue(value);

    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    debounceRef.current = setTimeout(() => {
      const next = new URLSearchParams(searchParams);
      if (value) {
        next.set('q', value);
      } else {
        next.delete('q');
      }
      setSearchParams(next, { replace: true });
    }, 300);
  }

  const query = searchParams.get('q') ?? '';

  return (
    <>
      <title>Search — DevHub</title>
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
            user ? (
              <div className="flex items-center gap-2">
                <Avatar
                  initials={user.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .slice(0, 2)
                    .toUpperCase()}
                  size="md"
                />
                <button
                  onClick={() => logout()}
                  style={{
                    background: 'none',
                    border: 'none',
                    cursor: 'pointer',
                    fontSize: 13,
                    color: 'var(--color-text-secondary)',
                  }}
                >
                  Sign out
                </button>
              </div>
            ) : (
              <Link
                to="/login"
                style={{
                  fontSize: 14,
                  color: 'var(--color-text-secondary)',
                  textDecoration: 'none',
                }}
              >
                Sign in
              </Link>
            )
          }
        />

        <div className="p-6" style={{ maxWidth: 680, margin: '0 auto' }}>
          <div className="mb-6">
            <input
              type="search"
              value={inputValue}
              onChange={handleInputChange}
              placeholder="Search posts, topics, authors…"
              autoFocus
              style={{
                width: '100%',
                height: 48,
                padding: '0 16px',
                fontSize: 16,
                borderRadius: 'var(--radius-md)',
                border: '0.5px solid var(--color-border-secondary)',
                backgroundColor: 'var(--color-bg-primary)',
                color: 'var(--color-text-primary)',
                outline: 'none',
                boxSizing: 'border-box',
              }}
            />
          </div>

          {!isLoading && meta !== null && (
            <p
              className="text-sm mb-4"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              {query
                ? `${meta.total} result${meta.total !== 1 ? 's' : ''} for "${query}"`
                : `${meta.total} recent post${meta.total !== 1 ? 's' : ''}`}
            </p>
          )}

          <div className="flex flex-col gap-3">
            {isLoading ? (
              <>
                <SkeletonCard />
                <SkeletonCard />
                <SkeletonCard />
              </>
            ) : posts.length === 0 ? (
              <div
                className="rounded-[var(--radius-lg)] p-10 text-center"
                style={{
                  backgroundColor: 'var(--color-bg-primary)',
                  border: '0.5px solid var(--color-border-tertiary)',
                  color: 'var(--color-text-tertiary)',
                }}
              >
                {query ? `No results. Try a different search.` : 'No posts yet.'}
              </div>
            ) : (
              posts.map((post) => <ApiPostCard key={post.id} post={post} />)
            )}
          </div>
        </div>
      </div>
    </>
  );
}
