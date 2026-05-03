import { useEffect, useState } from 'react';
import { Link, NavLink } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Button } from '../components/ui/button';
import { Tag } from '../components/ui/tag';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { api } from '../lib/api';
import { relativeTime } from '../lib/utils';
import type { ApiPost } from '../types';

const NAV_LINKS = [
  { label: 'Home', to: '/' },
  { label: 'Following', to: '/feed' },
  { label: 'Tags', to: '/tags' },
  { label: 'Bookmarks', to: '/bookmarks' },
];

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

export function FeedPage() {
  const { user, token, logout } = useAuth();
  const [posts, setPosts] = useState<ApiPost[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (!token) return;

    setIsLoading(true);
    api
      .get<ApiPost[]>('/feed', token)
      .then((res) => setPosts(res))
      .catch(() => setPosts([]))
      .finally(() => setIsLoading(false));
  }, [token]);

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
                fontSize: 18,
                fontWeight: 500,
                textDecoration: 'none',
                color: 'inherit',
              }}
            >
              DevHub
            </Link>
            <nav className="flex gap-5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
              {NAV_LINKS.map(({ label, to }) => (
                <NavLink
                  key={label}
                  to={to}
                  end
                  style={({ isActive }) => ({
                    color: isActive ? 'var(--color-text-primary)' : undefined,
                    fontWeight: isActive ? 500 : undefined,
                    textDecoration: 'none',
                  })}
                >
                  {label}
                </NavLink>
              ))}
            </nav>
          </>
        }
        right={
          user ? (
            <div className="flex items-center gap-2">
              <Link to={`/u/${user.username}`} style={{ lineHeight: 0 }}>
                <Avatar initials={getInitials(user.name)} size="md" />
              </Link>
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
            <div className="flex items-center gap-2">
              <Button variant="default">
                <Link to="/login" style={{ textDecoration: 'none', color: 'inherit' }}>
                  Sign in
                </Link>
              </Button>
              <Button variant="primary">
                <Link to="/register" style={{ textDecoration: 'none', color: 'inherit' }}>
                  Get started
                </Link>
              </Button>
            </div>
          )
        }
      />

      <div
        className="grid gap-6 p-6"
        style={{ gridTemplateColumns: 'minmax(0, 1fr)' }}
      >
        <main>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-medium m-0">Following feed</h2>
            <span
              className="text-[13px]"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              Posts from people you follow
            </span>
          </div>

          {isLoading ? (
            <div className="flex flex-col gap-3">
              {[1, 2, 3].map((i) => (
                <div
                  key={i}
                  className="rounded-[var(--radius-lg)] p-4 md:p-5 animate-pulse"
                  style={{
                    backgroundColor: 'var(--color-bg-primary)',
                    border: '0.5px solid var(--color-border-tertiary)',
                    height: 120,
                  }}
                />
              ))}
            </div>
          ) : posts.length === 0 ? (
            <div
              className="rounded-[var(--radius-lg)] p-8 text-center"
              style={{
                backgroundColor: 'var(--color-bg-primary)',
                border: '0.5px solid var(--color-border-tertiary)',
              }}
            >
              <p
                className="text-sm m-0 mb-3"
                style={{ color: 'var(--color-text-secondary)' }}
              >
                Follow some authors to see their posts here
              </p>
              <Link
                to="/"
                className="text-sm"
                style={{ color: 'var(--color-text-primary)' }}
              >
                Browse all posts
              </Link>
            </div>
          ) : (
            <div className="flex flex-col gap-3">
              {posts.map((post) => (
                <article
                  key={post.id}
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
                    <Link
                      to={`/u/${post.author.username}`}
                      style={{ lineHeight: 0 }}
                    >
                      <Avatar
                        initials={getInitials(post.author.name)}
                        size="sm"
                      />
                    </Link>
                    <Link
                      to={`/u/${post.author.username}`}
                      style={{
                        color: 'var(--color-text-primary)',
                        fontWeight: 500,
                        textDecoration: 'none',
                      }}
                    >
                      {post.author.name}
                    </Link>
                    <span>·</span>
                    <span>
                      {post.published_at ? relativeTime(post.published_at) : 'Draft'}
                    </span>
                    <span>·</span>
                    <span>{post.reading_time_minutes} min read</span>
                  </div>

                  <Link
                    to={`/posts/${post.slug}`}
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
                      <Tag key={tag.id}>{tag.name}</Tag>
                    ))}
                  </div>
                </article>
              ))}
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
