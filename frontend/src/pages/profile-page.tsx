import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Button } from '../components/ui/button';
import { Tag } from '../components/ui/tag';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { FollowButton } from '../features/social/follow-button';
import { api } from '../lib/api';
import { relativeTime } from '../lib/utils';
import type { ApiPost, ApiUser, UserSummary } from '../types';

type ProfileTab = 'posts' | 'followers' | 'following';

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

interface PostListProps {
  posts: ApiPost[];
  isLoading: boolean;
}

function PostList({ posts, isLoading }: PostListProps) {
  if (isLoading) {
    return (
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
    );
  }

  if (posts.length === 0) {
    return (
      <p className="text-sm" style={{ color: 'var(--color-text-tertiary)' }}>
        No posts published yet.
      </p>
    );
  }

  return (
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
            style={{ color: 'var(--color-text-secondary)', margin: '0 0 10px' }}
          >
            {post.excerpt}
          </p>

          <div className="flex items-center gap-3 flex-wrap">
            <div className="flex gap-1.5 flex-wrap">
              {post.tags.map((tag) => (
                <Tag key={tag.id}>{tag.name}</Tag>
              ))}
            </div>
            <span
              className="text-[12px] ml-auto"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              {post.reading_time_minutes} min read
              {post.published_at ? ` · ${relativeTime(post.published_at)}` : ''}
            </span>
          </div>
        </article>
      ))}
    </div>
  );
}

interface UserListProps {
  users: UserSummary[];
  isLoading: boolean;
  emptyMessage: string;
}

function UserList({ users, isLoading, emptyMessage }: UserListProps) {
  if (isLoading) {
    return (
      <div className="flex flex-col gap-2">
        {[1, 2, 3].map((i) => (
          <div
            key={i}
            className="flex items-center gap-3 p-3 rounded-[var(--radius-md)] animate-pulse"
            style={{
              backgroundColor: 'var(--color-bg-primary)',
              border: '0.5px solid var(--color-border-tertiary)',
              height: 56,
            }}
          />
        ))}
      </div>
    );
  }

  if (users.length === 0) {
    return (
      <p className="text-sm" style={{ color: 'var(--color-text-tertiary)' }}>
        {emptyMessage}
      </p>
    );
  }

  return (
    <div className="flex flex-col gap-2">
      {users.map((u) => (
        <Link
          key={u.id}
          to={`/u/${u.username}`}
          className="flex items-center gap-3 p-3 rounded-[var(--radius-md)] no-underline"
          style={{
            backgroundColor: 'var(--color-bg-primary)',
            border: '0.5px solid var(--color-border-tertiary)',
            color: 'inherit',
          }}
        >
          <Avatar initials={getInitials(u.name)} size="md" />
          <div>
            <p className="text-sm font-medium m-0">{u.name}</p>
            <p
              className="text-[12px] m-0"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              @{u.username}
            </p>
          </div>
        </Link>
      ))}
    </div>
  );
}

export function ProfilePage() {
  const { username } = useParams<{ username: string }>();
  const { user: currentUser, token } = useAuth();

  const [profileUser, setProfileUser] = useState<ApiUser | null>(null);
  const [posts, setPosts] = useState<ApiPost[]>([]);
  const [followers, setFollowers] = useState<UserSummary[]>([]);
  const [following, setFollowing] = useState<UserSummary[]>([]);
  const [activeTab, setActiveTab] = useState<ProfileTab>('posts');
  const [isLoadingProfile, setIsLoadingProfile] = useState(true);
  const [isLoadingPosts, setIsLoadingPosts] = useState(true);
  const [isLoadingFollowers, setIsLoadingFollowers] = useState(false);
  const [isLoadingFollowing, setIsLoadingFollowing] = useState(false);
  const [followersCount, setFollowersCount] = useState(0);
  const [isFollowing, setIsFollowing] = useState(false);
  const [notFound, setNotFound] = useState(false);

  const isOwnProfile = currentUser?.username === username;

  useEffect(() => {
    if (!username) return;

    setIsLoadingProfile(true);
    setIsLoadingPosts(true);
    setNotFound(false);

    api
      .get<ApiUser>(`/users/${username}`, token ?? undefined)
      .then((u) => {
        setProfileUser(u);
        setFollowersCount(u.followers_count);
      })
      .catch(() => setNotFound(true))
      .finally(() => setIsLoadingProfile(false));

    api
      .get<ApiPost[]>(`/posts?author=${username}`, token ?? undefined)
      .then((res) => setPosts(res))
      .catch(() => setPosts([]))
      .finally(() => setIsLoadingPosts(false));
  }, [username, token]);

  useEffect(() => {
    if (activeTab === 'followers' && username && followers.length === 0) {
      setIsLoadingFollowers(true);
      api
        .get<UserSummary[]>(`/users/${username}/followers`, token ?? undefined)
        .then((res) => setFollowers(res))
        .catch(() => setFollowers([]))
        .finally(() => setIsLoadingFollowers(false));
    }
    if (activeTab === 'following' && username && following.length === 0) {
      setIsLoadingFollowing(true);
      api
        .get<UserSummary[]>(`/users/${username}/following`, token ?? undefined)
        .then((res) => setFollowing(res))
        .catch(() => setFollowing([]))
        .finally(() => setIsLoadingFollowing(false));
    }
  }, [activeTab, username, token, followers.length, following.length]);

  function handleFollowChange(nowFollowing: boolean): void {
    setIsFollowing(nowFollowing);
    setFollowersCount((c) => (nowFollowing ? c + 1 : c - 1));
  }

  if (notFound) {
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
          right={null}
        />
        <div style={{ padding: 40, textAlign: 'center' }}>
          <p style={{ color: 'var(--color-text-secondary)' }}>User not found.</p>
          <Link to="/" style={{ color: 'var(--color-text-primary)' }}>
            ← Back to home
          </Link>
        </div>
      </div>
    );
  }

  const tabs: { id: ProfileTab; label: string }[] = [
    { id: 'posts', label: 'Posts' },
    { id: 'followers', label: `Followers (${followersCount})` },
    { id: 'following', label: `Following (${profileUser?.following_count ?? 0})` },
  ];

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
          currentUser ? (
            <div className="flex items-center gap-2">
              <Avatar
                initials={getInitials(currentUser.name)}
                size="md"
              />
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

      {/* Profile header */}
      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          borderBottom: '0.5px solid var(--color-border-tertiary)',
          padding: '32px 40px 24px',
        }}
      >
        {isLoadingProfile ? (
          <div className="animate-pulse" style={{ height: 80 }} />
        ) : profileUser ? (
          <div className="flex items-start gap-5">
            <Avatar initials={getInitials(profileUser.name)} size="lg" />

            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-3 flex-wrap">
                <h1
                  className="text-[22px] font-medium m-0 leading-tight"
                >
                  {profileUser.name}
                </h1>
                {!isOwnProfile && token && (
                  <FollowButton
                    username={profileUser.username}
                    initialFollowing={isFollowing}
                    onFollowChange={handleFollowChange}
                  />
                )}
                {isOwnProfile && (
                  <Button variant="default">
                    <Link
                      to="/settings"
                      style={{ textDecoration: 'none', color: 'inherit' }}
                    >
                      Edit profile
                    </Link>
                  </Button>
                )}
              </div>

              <p
                className="text-[13px] mt-0.5 mb-2"
                style={{ color: 'var(--color-text-tertiary)' }}
              >
                @{profileUser.username}
              </p>

              {profileUser.bio && (
                <p
                  className="text-sm leading-relaxed m-0 mb-3"
                  style={{ color: 'var(--color-text-secondary)' }}
                >
                  {profileUser.bio}
                </p>
              )}

              <div
                className="flex items-center gap-4 text-[13px] flex-wrap"
                style={{ color: 'var(--color-text-tertiary)' }}
              >
                {profileUser.website_url && (
                  <a
                    href={profileUser.website_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{ color: 'var(--color-text-primary)' }}
                  >
                    {profileUser.website_url.replace(/^https?:\/\//, '')}
                  </a>
                )}
                <span>Joined {relativeTime(profileUser.created_at)}</span>
                <span>
                  <strong style={{ color: 'var(--color-text-primary)' }}>
                    {followersCount}
                  </strong>{' '}
                  followers
                </span>
                <span>
                  <strong style={{ color: 'var(--color-text-primary)' }}>
                    {profileUser.following_count}
                  </strong>{' '}
                  following
                </span>
              </div>
            </div>
          </div>
        ) : null}
      </div>

      {/* Tabs */}
      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          borderBottom: '0.5px solid var(--color-border-tertiary)',
          paddingLeft: 40,
          display: 'flex',
          gap: 0,
        }}
      >
        {tabs.map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            style={{
              background: 'none',
              border: 'none',
              borderBottom: activeTab === tab.id
                ? '2px solid var(--color-text-primary)'
                : '2px solid transparent',
              cursor: 'pointer',
              padding: '12px 16px',
              fontSize: 14,
              color: activeTab === tab.id
                ? 'var(--color-text-primary)'
                : 'var(--color-text-secondary)',
              fontWeight: activeTab === tab.id ? 500 : undefined,
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Tab content */}
      <div className="p-6" style={{ maxWidth: 680 }}>
        {activeTab === 'posts' && (
          <PostList posts={posts} isLoading={isLoadingPosts} />
        )}
        {activeTab === 'followers' && (
          <UserList
            users={followers}
            isLoading={isLoadingFollowers}
            emptyMessage="No followers yet."
          />
        )}
        {activeTab === 'following' && (
          <UserList
            users={following}
            isLoading={isLoadingFollowing}
            emptyMessage="Not following anyone yet."
          />
        )}
      </div>
    </div>
  );
}
