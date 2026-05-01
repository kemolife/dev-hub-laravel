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
        <Avatar
          initials={authorInitials(post.author.name)}
          size="sm"
        />
        <span style={{ color: 'var(--color-text-primary)', fontWeight: 500 }}>
          {post.author.name}
        </span>
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

      <div className="flex gap-1.5 flex-wrap">
        {post.tags.map((tag) => (
          <Tag key={tag.id}>{tag.name}</Tag>
        ))}
      </div>
    </article>
  );
}
