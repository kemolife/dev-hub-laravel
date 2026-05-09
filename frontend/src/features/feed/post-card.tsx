import { Link } from 'react-router';
import { Avatar } from '../../components/ui/avatar';
import { Tag } from '../../components/ui/tag';
import { relativeTime } from '../../lib/utils';
import type { ApiPost } from '../../types';

interface PostCardProps {
  post: ApiPost;
}

function authorInitials(name: string): string {
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

export function PostCard({ post }: PostCardProps) {
  const publishedDate = post.published_at ?? post.created_at;

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
        <Link to={`/u/${post.author.username}`} className="no-underline" style={{ display: 'inline-block' }}>
          <Avatar
            initials={authorInitials(post.author.name)}
            size="sm"
          />
        </Link>
        <Link
          to={`/u/${post.author.username}`}
          className="no-underline"
          style={{ color: 'var(--color-text-primary)', fontWeight: 500 }}
        >
          {post.author.name}
        </Link>
        <span>·</span>
        <span>{relativeTime(publishedDate)}</span>
        <span>·</span>
        <span>{post.reading_time_minutes} min read</span>
      </div>

      <Link
        to={`/posts/${post.slug}`}
        className="block no-underline"
        style={{ color: 'inherit' }}
      >
        <h3
          className="text-[18px] font-medium leading-snug mb-2"
          style={{ margin: '0 0 8px' }}
        >
          {post.title}
        </h3>
      </Link>

      <p
        className="text-sm leading-relaxed mb-3"
        style={{ color: 'var(--color-text-secondary)', margin: '0 0 12px' }}
      >
        {post.excerpt}
      </p>

      <div className="flex items-center justify-between gap-3">
        <div className="flex gap-1.5 flex-wrap">
          {post.tags.map((tag) => (
            <Tag key={tag.id}>{tag.name}</Tag>
          ))}
        </div>
        <Link
          to={`/posts/${post.slug}#comments`}
          className="flex items-center gap-1 no-underline shrink-0"
          style={{ fontSize: 13, color: 'var(--color-text-tertiary)' }}
        >
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
            <path d="M14 10.5a1.5 1.5 0 0 1-1.5 1.5H4.5L2 14.5V3a1.5 1.5 0 0 1 1.5-1.5h9A1.5 1.5 0 0 1 14 3v7.5Z" strokeLinejoin="round"/>
          </svg>
          {post.comments_count}
        </Link>
      </div>
    </article>
  );
}
