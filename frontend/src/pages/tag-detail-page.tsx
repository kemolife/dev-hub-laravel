import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Tag } from '../components/ui/tag';
import { Topbar } from '../components/layout/topbar';
import { api } from '../lib/api';
import { relativeTime } from '../lib/utils';
import type { ApiPost, ApiTagDetail } from '../types';

function ApiPostCard({ post }: { post: ApiPost }) {
  const initials = post.author.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  return (
    <article
      className="rounded-[var(--radius-lg)] p-4 md:p-5"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div
        className="flex items-center gap-2.5 mb-2.5 text-[13px]"
        style={{ color: 'var(--color-text-secondary)' }}
      >
        <Avatar initials={initials} size="sm" />
        <span style={{ color: 'var(--color-text-primary)', fontWeight: 500 }}>
          {post.author.name}
        </span>
        {post.published_at && (
          <>
            <span>·</span>
            <span>{relativeTime(post.published_at)}</span>
          </>
        )}
        <span>·</span>
        <span>{post.reading_time_minutes} min read</span>
      </div>

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
        style={{ color: 'var(--color-text-secondary)', margin: '0 0 12px' }}
      >
        {post.excerpt}
      </p>

      <div className="flex gap-1.5 flex-wrap">
        {post.tags.map((tag) => (
          <Link key={tag.id} to={`/tags/${tag.slug}`} style={{ textDecoration: 'none' }}>
            <Tag>{tag.name}</Tag>
          </Link>
        ))}
      </div>
    </article>
  );
}

export function TagDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const [tagDetail, setTagDetail] = useState<ApiTagDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!slug) return;

    setIsLoading(true);
    setNotFound(false);

    api
      .get<ApiTagDetail>(`/tags/${slug}`)
      .then((data) => setTagDetail(data))
      .catch(() => setNotFound(true))
      .finally(() => setIsLoading(false));
  }, [slug]);

  return (
    <>
      <title>{tagDetail ? `${tagDetail.name} — DevHub` : 'Tag — DevHub'}</title>
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
            <Link
              to="/tags"
              style={{
                fontSize: 14,
                color: 'var(--color-text-secondary)',
                textDecoration: 'none',
              }}
            >
              ← All tags
            </Link>
          }
        />

        <div className="p-6" style={{ maxWidth: 680, margin: '0 auto' }}>
          {isLoading ? (
            <div className="animate-pulse">
              <div
                className="h-7 w-40 rounded mb-2"
                style={{ backgroundColor: 'var(--color-bg-primary)' }}
              />
              <div
                className="h-4 w-24 rounded mb-6"
                style={{ backgroundColor: 'var(--color-bg-primary)' }}
              />
              <div className="flex flex-col gap-3">
                {Array.from({ length: 3 }).map((_, i) => (
                  <div
                    key={i}
                    className="rounded-[var(--radius-lg)] p-5 h-28"
                    style={{
                      backgroundColor: 'var(--color-bg-primary)',
                      border: '0.5px solid var(--color-border-tertiary)',
                    }}
                  />
                ))}
              </div>
            </div>
          ) : notFound || !tagDetail ? (
            <div className="text-center py-12">
              <p style={{ color: 'var(--color-text-secondary)' }}>Tag not found.</p>
              <Link
                to="/tags"
                className="text-sm"
                style={{ color: 'var(--color-text-primary)' }}
              >
                ← Back to all tags
              </Link>
            </div>
          ) : (
            <>
              <div className="mb-6">
                <h1
                  className="text-[22px] font-medium"
                  style={{ margin: '0 0 4px' }}
                >
                  #{tagDetail.name}
                </h1>
                <p
                  className="text-sm"
                  style={{ color: 'var(--color-text-tertiary)', margin: 0 }}
                >
                  {tagDetail.posts_count} post{tagDetail.posts_count !== 1 ? 's' : ''}
                </p>
              </div>

              <div className="flex flex-col gap-3">
                {tagDetail.posts.length === 0 ? (
                  <p style={{ color: 'var(--color-text-tertiary)' }}>No posts yet.</p>
                ) : (
                  tagDetail.posts.map((post) => <ApiPostCard key={post.id} post={post} />)
                )}
              </div>
            </>
          )}
        </div>
      </div>
    </>
  );
}
