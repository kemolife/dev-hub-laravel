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

export interface ApiNotification {
  id: string;
  type: string;
  data: {
    message: string;
    url?: string;
    post_slug?: string;
    commenter_name?: string;
  };
  read_at: string | null;
  created_at: string;
}

export interface ApiPreference {
  type: string;
  type_label: string;
  channel: string;
  channel_label: string;
  enabled: boolean;
}
