import { Link } from 'react-router';
import { Avatar } from '../../components/ui/avatar';
import { Button } from '../../components/ui/button';
import { FollowButton } from '../social/follow-button';
import { relativeTime } from '../../lib/utils';
import type { ApiUser } from '../../types';

function getInitials(name: string): string {
  return name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
}

interface ProfileHeaderProps {
  user: ApiUser;
  isOwnProfile: boolean;
  isAuthenticated: boolean;
  isFollowing: boolean;
  followersCount: number;
  onFollowChange: (nowFollowing: boolean) => void;
}

export function ProfileHeader({
  user,
  isOwnProfile,
  isAuthenticated,
  isFollowing,
  followersCount,
  onFollowChange,
}: ProfileHeaderProps) {
  return (
    <div
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        borderBottom: '0.5px solid var(--color-border-tertiary)',
        padding: '32px 40px 24px',
      }}
    >
      <div className="flex items-start gap-5">
        <Avatar initials={getInitials(user.name)} size="lg" />

        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-3 flex-wrap">
            <h1 className="text-[22px] font-medium m-0 leading-tight">{user.name}</h1>

            {!isOwnProfile && isAuthenticated && (
              <FollowButton
                username={user.username}
                initialFollowing={isFollowing}
                onFollowChange={onFollowChange}
              />
            )}

            {isOwnProfile && (
              <Button variant="default">
                <Link to="/settings" style={{ textDecoration: 'none', color: 'inherit' }}>
                  Edit profile
                </Link>
              </Button>
            )}
          </div>

          <p
            className="text-[13px] mt-0.5 mb-2"
            style={{ color: 'var(--color-text-tertiary)' }}
          >
            @{user.username}
          </p>

          {user.bio && (
            <p
              className="text-sm leading-relaxed m-0 mb-3"
              style={{ color: 'var(--color-text-secondary)' }}
            >
              {user.bio}
            </p>
          )}

          <div
            className="flex items-center gap-4 text-[13px] flex-wrap"
            style={{ color: 'var(--color-text-tertiary)' }}
          >
            {user.website_url && (
              <a
                href={user.website_url}
                target="_blank"
                rel="noopener noreferrer"
                style={{ color: 'var(--color-text-primary)' }}
              >
                {user.website_url.replace(/^https?:\/\//, '')}
              </a>
            )}
            <span>Joined {relativeTime(user.created_at)}</span>
            <span>
              <strong style={{ color: 'var(--color-text-primary)' }}>{followersCount}</strong>{' '}
              followers
            </span>
            <span>
              <strong style={{ color: 'var(--color-text-primary)' }}>{user.following_count}</strong>{' '}
              following
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}
