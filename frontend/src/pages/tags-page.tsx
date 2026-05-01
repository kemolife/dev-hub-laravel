import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { Topbar } from '../components/layout/topbar';
import { api } from '../lib/api';
import type { ApiTag } from '../types';

export function TagsPage() {
  const [tags, setTags] = useState<ApiTag[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    api
      .get<ApiTag[]>('/tags')
      .then((data) => setTags(data))
      .catch(() => setTags([]))
      .finally(() => setIsLoading(false));
  }, []);

  return (
    <>
      <title>Tags — DevHub</title>
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
            <Link
              to="/search"
              style={{
                fontSize: 14,
                color: 'var(--color-text-secondary)',
                textDecoration: 'none',
              }}
            >
              Search
            </Link>
          }
        />

        <div className="p-6" style={{ maxWidth: 720, margin: '0 auto' }}>
          <h1
            className="text-[22px] font-medium mb-1"
            style={{ margin: '0 0 4px' }}
          >
            Tags
          </h1>
          <p
            className="text-sm mb-6"
            style={{ color: 'var(--color-text-secondary)', margin: '0 0 24px' }}
          >
            Browse posts by topic
          </p>

          {isLoading ? (
            <div className="flex flex-wrap gap-2">
              {Array.from({ length: 12 }).map((_, i) => (
                <div
                  key={i}
                  className="h-8 rounded-[var(--radius-md)] animate-pulse"
                  style={{
                    width: 80 + (i % 3) * 20,
                    backgroundColor: 'var(--color-bg-primary)',
                  }}
                />
              ))}
            </div>
          ) : tags.length === 0 ? (
            <p style={{ color: 'var(--color-text-tertiary)' }}>No tags yet.</p>
          ) : (
            <div className="flex flex-wrap gap-2">
              {tags.map((tag) => (
                <Link
                  key={tag.id}
                  to={`/tags/${tag.slug}`}
                  style={{ textDecoration: 'none' }}
                >
                  <span
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[var(--radius-md)] text-sm transition-colors"
                    style={{
                      backgroundColor: 'var(--color-bg-primary)',
                      border: '0.5px solid var(--color-border-tertiary)',
                      color: 'var(--color-text-primary)',
                    }}
                  >
                    {tag.name}
                    {tag.posts_count !== undefined && (
                      <span
                        className="text-xs"
                        style={{ color: 'var(--color-text-tertiary)' }}
                      >
                        {tag.posts_count}
                      </span>
                    )}
                  </span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </>
  );
}
