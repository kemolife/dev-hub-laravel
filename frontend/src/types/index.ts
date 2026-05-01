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
