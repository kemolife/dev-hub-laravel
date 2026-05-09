import { useEffect, useRef, useState } from 'react';
import { Link, NavLink, useNavigate } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { NotificationBell } from '../features/notifications/notification-bell';
import { NewsletterWidget } from '../features/feed/newsletter-widget';
import { PostCard } from '../features/feed/post-card';
import { TrendingSidebar } from '../features/feed/trending-sidebar';
import { api } from '../lib/api';
import type { ApiPost, ApiTag } from '../types';

const NAV_LINKS = [
  { label: 'Home', to: '/' },
  { label: 'Following', to: '/feed' },
  { label: 'Tags', to: '/tags' },
  { label: 'Bookmarks', to: '/bookmarks' },
];

function PostCardSkeleton() {
  return (
    <div
      className="rounded-[var(--radius-lg)] p-4 md:p-5 animate-pulse"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div className="flex items-center gap-2.5 mb-2.5">
        <div className="w-6 h-6 rounded-full" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
        <div className="h-3 w-24 rounded" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
      </div>
      <div className="h-5 w-3/4 rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
      <div className="h-4 w-full rounded mb-1" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
      <div className="h-4 w-2/3 rounded" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
    </div>
  );
}

export function HomePage() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [posts, setPosts] = useState<ApiPost[]>([]);
  const [tags, setTags] = useState<ApiTag[]>([]);
  const [isLoadingPosts, setIsLoadingPosts] = useState(true);
  const [writeMenuOpen, setWriteMenuOpen] = useState(false);
  const writeMenuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (writeMenuRef.current && !writeMenuRef.current.contains(e.target as Node)) {
        setWriteMenuOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  useEffect(() => {
    api
      .get<ApiPost[]>('/posts?page=1&per_page=15')
      .then((result) => setPosts(result))
      .catch(() => setPosts([]))
      .finally(() => setIsLoadingPosts(false));

    api
      .get<ApiTag[]>('/tags')
      .then((result) => setTags(result))
      .catch(() => setTags([]));
  }, []);

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
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  const q = (e.currentTarget as HTMLInputElement).value.trim();
                  void navigate(q ? `/search?q=${encodeURIComponent(q)}` : '/search');
                }
              }}
            />
            {user ? (
              <div className="flex items-center gap-2">
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
                <NotificationBell />
                <Link
                  to="/settings/billing"
                  style={{
                    fontSize: 13,
                    color: 'var(--color-text-secondary)',
                    textDecoration: 'none',
                  }}
                >
                  Billing
                </Link>
                <Link to={`/u/${user.username}`} style={{ display: 'flex' }}>
                  <Avatar
                    initials={user.name
                      .split(' ')
                      .map((n) => n[0])
                      .join('')
                      .slice(0, 2)
                      .toUpperCase()}
                    size="md"
                  />
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
                <Button variant="default" onClick={() => {}}>
                  <Link to="/login" style={{ textDecoration: 'none', color: 'inherit' }}>
                    Sign in
                  </Link>
                </Button>
                <Button variant="primary" onClick={() => {}}>
                  <Link to="/register" style={{ textDecoration: 'none', color: 'inherit' }}>
                    Get started
                  </Link>
                </Button>
              </div>
            )}
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
            {isLoadingPosts ? (
              <>
                <PostCardSkeleton />
                <PostCardSkeleton />
                <PostCardSkeleton />
              </>
            ) : posts.length === 0 ? (
              <p
                className="text-sm py-8 text-center"
                style={{ color: 'var(--color-text-secondary)' }}
              >
                No posts yet.
              </p>
            ) : (
              posts.map((post) => (
                <PostCard key={post.id} post={post} />
              ))
            )}
          </div>
        </main>

        <aside className="flex flex-col gap-4">
          <TrendingSidebar tags={tags} />
          <NewsletterWidget />
        </aside>
      </div>
    </div>
  );
}
