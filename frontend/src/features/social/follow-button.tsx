import { useState } from 'react';
import { Button } from '../../components/ui/button';
import { useAuth } from '../auth/auth-context';
import { api } from '../../lib/api';

interface FollowButtonProps {
  username: string;
  initialFollowing: boolean;
  onFollowChange?: (following: boolean) => void;
}

interface FollowResponse {
  following: boolean;
}

export function FollowButton({
  username,
  initialFollowing,
  onFollowChange,
}: FollowButtonProps) {
  const { token } = useAuth();
  const [isFollowing, setIsFollowing] = useState(initialFollowing);
  const [isHovering, setIsHovering] = useState(false);
  const [isPending, setIsPending] = useState(false);

  if (!token) {
    return (
      <Button variant="default" disabled>
        Follow
      </Button>
    );
  }

  async function handleClick() {
    if (isPending) return;

    const nextFollowing = !isFollowing;

    setIsFollowing(nextFollowing);
    setIsPending(true);

    try {
      const result = await api.post<FollowResponse>(
        `/users/${username}/follow`,
        {},
        token ?? undefined,
      );
      setIsFollowing(result.following);
      onFollowChange?.(result.following);
    } catch {
      setIsFollowing(!nextFollowing);
      onFollowChange?.(!nextFollowing);
    } finally {
      setIsPending(false);
    }
  }

  const label = isFollowing ? (isHovering ? 'Unfollow' : 'Following') : 'Follow';

  return (
    <Button
      variant={isFollowing ? 'default' : 'primary'}
      disabled={isPending}
      onClick={handleClick}
      onMouseEnter={() => setIsHovering(true)}
      onMouseLeave={() => setIsHovering(false)}
      style={
        isFollowing && isHovering
          ? {
              borderColor: 'var(--color-text-secondary)',
              color: 'var(--color-text-secondary)',
            }
          : undefined
      }
    >
      {label}
    </Button>
  );
}
