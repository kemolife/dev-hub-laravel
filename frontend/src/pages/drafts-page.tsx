import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { useAuth } from '../features/auth/auth-context';
import { api } from '../lib/api';
import { relativeTime } from '../lib/utils';
import type { ApiPost } from '../types';

export function DraftsPage() {
  const { token } = useAuth();
  const [drafts, setDrafts] = useState<ApiPost[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) return;
    api
      .get<ApiPost[]>('/me/posts?status=draft', token)
      .then((res) => setDrafts(res))
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
            display: 'inline-block',
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
                  <span
                    key={tag.id}
                    style={{
                      fontSize: 12,
                      padding: '2px 8px',
                      border: '0.5px solid var(--color-border-tertiary)',
                      borderRadius: 'var(--radius-sm)',
                      color: 'var(--color-text-secondary)',
                    }}
                  >
                    {tag.name}
                  </span>
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
