export interface ApiTag {
  id: number;
  name: string;
  slug: string;
  posts_count?: number;
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

export interface ApiTagDetail extends ApiTag {
  posts: ApiPost[];
  posts_count: number;
}

export interface PaginationMeta {
  total: number;
  current_page: number;
  last_page: number;
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

export interface ApiComment {
  id: number;
  body: string;
  body_html: string;
  depth: number;
  parent_id: number | null;
  author: {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
  };
  created_at: string;
  updated_at: string;
  children?: ApiComment[];
}

export interface BillingStatus {
  plan: 'free' | 'pro' | 'pro_annual';
  status: 'active' | 'cancelled' | 'on_grace_period' | 'trialing' | null;
  trial_ends_at: string | null;
  renews_at: string | null;
  cancelled_at: string | null;
  ends_at: string | null;
}

export interface Invoice {
  id: string;
  date: string;
  amount: string;
  status: string;
  download_url: string;
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

export type ApiPostAuthor = ApiAuthor;

export type ApiPostTag = Omit<ApiTag, 'posts_count'>;

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
