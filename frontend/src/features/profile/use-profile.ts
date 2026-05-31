import { useEffect, useState } from 'react';
import { api } from '../../lib/api';
import type { ApiPost, ApiUser, UserSummary } from '../../types';

export type ProfileTab = 'posts' | 'followers' | 'following';

export interface UseProfileReturn {
  profileUser: ApiUser | null;
  posts: ApiPost[];
  followers: UserSummary[];
  following: UserSummary[];
  activeTab: ProfileTab;
  setActiveTab: (tab: ProfileTab) => void;
  isLoadingProfile: boolean;
  isLoadingPosts: boolean;
  isLoadingFollowers: boolean;
  isLoadingFollowing: boolean;
  followersCount: number;
  isFollowing: boolean;
  notFound: boolean;
  handleFollowChange: (nowFollowing: boolean) => void;
}

export function useProfile(username: string | undefined, token: string | null): UseProfileReturn {
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
        setIsFollowing(u.is_following);
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

  return {
    profileUser,
    posts,
    followers,
    following,
    activeTab,
    setActiveTab,
    isLoadingProfile,
    isLoadingPosts,
    isLoadingFollowers,
    isLoadingFollowing,
    followersCount,
    isFollowing,
    notFound,
    handleFollowChange,
  };
}
