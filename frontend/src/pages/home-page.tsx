import { NavLink } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Topbar } from '../components/layout/topbar';
import { NewsletterWidget } from '../features/feed/newsletter-widget';
import { PostCard } from '../features/feed/post-card';
import { TrendingSidebar } from '../features/feed/trending-sidebar';
import { mockPosts, mockTags } from '../data/mock';

const NAV_LINKS = [
  { label: 'Home', to: '/' },
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
