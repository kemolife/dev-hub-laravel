import { Link, useParams } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { PostHeader } from '../features/post/post-header';
import { ProseContent } from '../features/post/prose-content';
import { ReactionBar } from '../features/post/reaction-bar';
import { mockPosts } from '../data/mock';

export function PostDetailPage() {
  const { id } = useParams<{ id: string }>();
  const post = mockPosts.find((p) => p.id === id);

  if (!post) {
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
        right={
          <>
            <Button>Bookmark</Button>
            <Avatar initials="VK" size="md" />
          </>
        }
      />

      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          padding: '40px 64px',
        }}
      >
        <div style={{ maxWidth: 580, margin: '0 auto' }}>
          <PostHeader post={post} />
          <ProseContent html={post.content} />
        </div>
      </div>

      <ReactionBar />
    </div>
  );
}
