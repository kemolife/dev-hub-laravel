import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { PostHeader } from '../features/post/post-header';
import { ProseContent } from '../features/post/prose-content';
import { ReactionBar } from '../features/post/reaction-bar';
import { api, ApiError } from '../lib/api';
import type { ApiPost } from '../types';

export function PostDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const [post, setPost] = useState<ApiPost | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!slug) return;

    setIsLoading(true);
    setNotFound(false);

    api
      .get<ApiPost>(`/posts/${slug}`)
      .then((result) => setPost(result))
      .catch((err) => {
        if (err instanceof ApiError && err.status === 404) {
          setNotFound(true);
        }
      })
      .finally(() => setIsLoading(false));
  }, [slug]);

  if (isLoading) {
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
          right={<Button>Bookmark</Button>}
        />
        <div
          style={{
            backgroundColor: 'var(--color-bg-primary)',
            padding: '40px 64px',
          }}
        >
          <div style={{ maxWidth: 580, margin: '0 auto' }} className="animate-pulse">
            <div className="h-4 w-16 rounded mb-4" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-8 w-3/4 rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-5 w-1/2 rounded mb-8" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-4 w-full rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-4 w-full rounded mb-2" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
            <div className="h-4 w-5/6 rounded" style={{ backgroundColor: 'var(--color-bg-tertiary)' }} />
          </div>
        </div>
      </div>
    );
  }

  if (notFound || !post) {
    return (
      <div style={{ padding: 40, textAlign: 'center' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>Post not found.</p>
        <Link to="/" style={{ color: 'var(--color-text-primary)' }}>
          ← Back to feed
        </Link>
      </div>
    );
  }

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
        right={<Button>Bookmark</Button>}
      />

      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          padding: '40px 64px',
        }}
      >
        <div style={{ maxWidth: 580, margin: '0 auto' }}>
          <PostHeader post={post} />
          <ProseContent html={post.body_html} />
        </div>
      </div>

      <ReactionBar />
    </div>
  );
}
