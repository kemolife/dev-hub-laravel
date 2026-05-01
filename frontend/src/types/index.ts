export interface Author {
  id: string;
  name: string;
  initials: string;
  avatarBg: string;
  avatarColor: string;
}

export interface Post {
  id: string;
  title: string;
  subtitle?: string;
  excerpt: string;
  content: string;
  author: Author;
  publishedAt: string;
  readingMinutes: number;
  wordCount: number;
  tags: string[];
}

export type Tag = string;

export interface User {
  id: string;
  name: string;
  username: string;
  email: string;
  bio: string | null;
  avatar_path: string | null;
  website_url: string | null;
  role: string;
  email_verified_at: string | null;
  created_at: string;
}

export interface ApiPostAuthor {
  id: number;
  name: string;
  username: string;
  avatar_path: string | null;
}

export interface ApiPostTag {
  id: number;
  name: string;
  slug: string;
}

export interface ApiPost {
  id: number;
  slug: string;
  title: string;
  subtitle: string | null;
  excerpt: string;
  reading_time_minutes: number;
  tags: ApiPostTag[];
  author: ApiPostAuthor;
  published_at: string | null;
  reactions_count: Record<string, number>;
}

export interface ApiUser {
  id: number;
  name: string;
  username: string;
  bio: string | null;
  avatar_path: string | null;
  website_url: string | null;
  followers_count: number;
  following_count: number;
  created_at: string;
}

export interface UserSummary {
  id: number;
  name: string;
  username: string;
  avatar_path: string | null;
}
