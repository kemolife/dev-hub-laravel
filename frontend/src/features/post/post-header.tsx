import { Link } from 'react-router';
import { Avatar } from '../../components/ui/avatar';
import { Tag } from '../../components/ui/tag';
import { relativeTime } from '../../lib/utils';
import type { ApiPost } from '../../types';

interface PostHeaderProps {
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

export function PostHeader({ post }: PostHeaderProps) {
  const publishedDate = post.published_at ?? post.created_at;

  return (
    <div>
      <div className="flex gap-1.5 flex-wrap mb-4">
        {post.tags.map((tag) => (
          <Tag key={tag.id}>{tag.name}</Tag>
        ))}
      </div>

      <h1
        className="font-medium leading-tight"
        style={{ fontSize: 28, margin: '0 0 16px' }}
      >
        {post.title}
      </h1>

      {post.excerpt && (
        <p
          className="leading-snug"
          style={{ fontSize: 18, color: 'var(--color-text-secondary)', margin: '0 0 16px' }}
        >
          {post.excerpt}
        </p>
      )}

      <div
        className="flex items-center gap-3 pb-6"
        style={{ borderBottom: '0.5px solid var(--color-border-tertiary)', marginBottom: 32 }}
      >
        <Link to={`/u/${post.author.username}`} className="no-underline" style={{ display: 'inline-block' }}>
          <Avatar
            initials={authorInitials(post.author.name)}
            size="lg"
          />
        </Link>
        <div>
          <Link
            to={`/u/${post.author.username}`}
            className="no-underline"
            style={{ color: 'inherit' }}
          >
            <p className="m-0 text-sm font-medium">{post.author.name}</p>
          </Link>
          <p className="m-0 text-[13px]" style={{ color: 'var(--color-text-secondary)' }}>
            Published {relativeTime(publishedDate)} · {post.reading_time_minutes} min read
          </p>
        </div>
      </div>
    </div>
  );
}
