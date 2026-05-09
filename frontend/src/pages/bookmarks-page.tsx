import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { Topbar } from '../components/layout/topbar';
import { PostCard } from '../features/feed/post-card';
import { useAuth } from '../features/auth/auth-context';
import { api } from '../lib/api';
import type { ApiPost } from '../types';

export function BookmarksPage() {
  const { token } = useAuth();
  const [posts, setPosts] = useState<ApiPost[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (!token) return;

    api
      .get<ApiPost[]>('/me/bookmarks', token)
      .then((data) => setPosts(data))
      .catch(() => setPosts([]))
      .finally(() => setIsLoading(false));
  }, [token]);

  return (
    <>
      <title>Bookmarks — DevHub</title>
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
        />

        <div className="p-6" style={{ maxWidth: 680, margin: '0 auto' }}>
          <h2 className="text-lg font-medium mb-4" style={{ margin: '0 0 16px' }}>
            Bookmarks
          </h2>

          {isLoading ? (
            <div className="flex flex-col gap-3">
              {Array.from({ length: 3 }).map((_, i) => (
                <div
                  key={i}
                  className="rounded-[var(--radius-lg)] p-5 h-28 animate-pulse"
                  style={{
                    backgroundColor: 'var(--color-bg-primary)',
                    border: '0.5px solid var(--color-border-tertiary)',
                  }}
                />
              ))}
            </div>
          ) : posts.length === 0 ? (
            <div
              className="rounded-[var(--radius-lg)] p-10 text-center"
              style={{
                backgroundColor: 'var(--color-bg-primary)',
                border: '0.5px solid var(--color-border-tertiary)',
                color: 'var(--color-text-tertiary)',
              }}
            >
              No bookmarks yet. Save posts to read them later.
            </div>
          ) : (
            <div className="flex flex-col gap-3">
              {posts.map((post) => (
                <PostCard key={post.id} post={post} />
              ))}
            </div>
          )}
        </div>
      </div>
    </>
  );
}
