export interface ApiTag {
  id: number;
  name: string;
  slug: string;
}

export interface ApiAuthor {
  id: number;
  name: string;
  username: string;
  avatar_path: string | null;
}

export interface ApiPost {
  id: number;
  public_id: string;
  slug: string;
  title: string;
  subtitle: string | null;
  body: string;
  body_html: string;
  excerpt: string;
  status: 'draft' | 'published' | 'archived';
  reading_time_minutes: number;
  reactions_count: Record<string, number>;
  tags: ApiTag[];
  author: ApiAuthor;
  published_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface Pagination<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

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
