import { Avatar } from '../../components/ui/avatar';
import { Tag } from '../../components/ui/tag';
import { relativeTime } from '../../lib/utils';
import type { Post } from '../../types';

interface PostHeaderProps {
  post: Post;
}

export function PostHeader({ post }: PostHeaderProps) {
  return (
    <div>
      <div className="flex gap-1.5 flex-wrap mb-4">
        {post.tags.map((tag) => (
          <Tag key={tag}>{tag}</Tag>
        ))}
      </div>

      <h1
        className="font-medium leading-tight"
        style={{ fontSize: 28, margin: '0 0 16px' }}
      >
        {post.title}
      </h1>

      <div
        className="flex items-center gap-3 pb-6"
        style={{ borderBottom: '0.5px solid var(--color-border-tertiary)', marginBottom: 32 }}
      >
        <Avatar
          initials={post.author.initials}
          bg={post.author.avatarBg}
          color={post.author.avatarColor}
          size="lg"
        />
        <div>
          <p className="m-0 text-sm font-medium">{post.author.name}</p>
          <p className="m-0 text-[13px]" style={{ color: 'var(--color-text-secondary)' }}>
            Published {relativeTime(post.publishedAt)} · {post.readingMinutes} min read ·{' '}
            {post.wordCount.toLocaleString()} words
          </p>
        </div>
      </div>
    </div>
  );
}
