import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { PostHeader } from '../features/post/post-header';
import { ProseContent } from '../features/post/prose-content';
import { ReactionBar } from '../features/post/reaction-bar';
import { CommentsSection } from '../features/comments/comments-section';
import { useAuth } from '../features/auth/auth-context';
import { api, ApiError } from '../lib/api';
import { useTextSelection } from '../features/ai/use-text-selection';
import { AskAiButton } from '../features/ai/ask-ai-button';
import { ExplanationModal } from '../features/ai/explanation-modal';
import { ChatPanel } from '../features/ai/chat-panel';
import { ConversationHighlights } from '../features/ai/conversation-highlights';
import type { ApiPost } from '../types';
import type { TextSelection } from '../features/ai/use-text-selection';

export function PostDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const { token } = useAuth();
  const [post, setPost] = useState<ApiPost | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [isBookmarked, setIsBookmarked] = useState(false);
  const [isTogglingBookmark, setIsTogglingBookmark] = useState(false);

  const proseRef = useRef<HTMLDivElement>(null);
  const selection = useTextSelection(proseRef);
  const [activeSelection, setActiveSelection] = useState<TextSelection | null>(null);
  const [activeChatId, setActiveChatId] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    setIsLoading(true);
    setNotFound(false);
    api
      .get<ApiPost>(`/posts/${slug}`, token ?? undefined)
      .then((result) => { setPost(result); setIsBookmarked(result.is_bookmarked ?? false); })
      .catch((err) => { if (err instanceof ApiError && err.status === 404) setNotFound(true); })
      .finally(() => setIsLoading(false));
  }, [slug, token]);

  async function handleBookmark() {
    if (!token || !post || isTogglingBookmark) return;
    setIsTogglingBookmark(true);
    try {
      const res = await api.post<{ bookmarked: boolean }>(`/posts/${post.slug}/bookmark`, {}, token);
      setIsBookmarked(res.bookmarked);
    } finally {
      setIsTogglingBookmark(false);
    }
  }

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
          right={token ? (
            <Button onClick={() => { void handleBookmark(); }} disabled={isTogglingBookmark}>
              {isBookmarked ? '★ Saved' : '☆ Bookmark'}
            </Button>
          ) : null}
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
    <div style={{ display: 'flex', maxWidth: activeChatId ? 1080 + 360 : 1080, margin: '0 auto', alignItems: 'stretch' }}>
      {/* Post column */}
      <div
        style={{
          flex: 1,
          minWidth: 0,
          position: 'relative',
          backgroundColor: 'var(--color-bg-tertiary)',
          borderRadius: activeChatId ? 'var(--radius-lg) 0 0 var(--radius-lg)' : 'var(--radius-lg)',
          border: '0.5px solid var(--color-border-tertiary)',
          borderRight: activeChatId ? 'none' : '0.5px solid var(--color-border-tertiary)',
          overflow: 'hidden',
          transition: 'border-radius 200ms ease',
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
          right={token ? (
            <Button onClick={() => { void handleBookmark(); }} disabled={isTogglingBookmark}>
              {isBookmarked ? '★ Saved' : '☆ Bookmark'}
            </Button>
          ) : null}
        />

        <div
          style={{
            backgroundColor: 'var(--color-bg-primary)',
            padding: '40px 64px',
            position: 'relative',
          }}
        >
          <div style={{ maxWidth: 580, margin: '0 auto' }}>
            <PostHeader post={post} />
            {/* Wrapper is the offset parent for highlight divs — must match containerRef */}
            <div style={{ position: 'relative' }}>
              <ProseContent ref={proseRef} html={post.body_html} />
              {token && (
                <ConversationHighlights
                  postSlug={slug!}
                  token={token}
                  containerRef={proseRef}
                  onSelectConversation={setActiveChatId}
                />
              )}
            </div>
          </div>
        </div>

        {selection && token && !activeSelection && (
          <AskAiButton
            selection={selection}
            onAsk={(sel) => setActiveSelection(sel)}
          />
        )}

        {activeSelection && token && (
          <ExplanationModal
            selection={activeSelection}
            postSlug={slug!}
            token={token}
            onClose={() => setActiveSelection(null)}
            onOpenChat={(id) => { setActiveSelection(null); setActiveChatId(id); }}
          />
        )}

        <ReactionBar />
        <CommentsSection postSlug={slug!} token={token} />
      </div>

      {/* Chat column */}
      {activeChatId && token && (
        <div style={{ width: 360, flexShrink: 0, position: 'sticky', top: 0, height: '100vh', display: 'flex', flexDirection: 'column' }}>
          <ChatPanel
            conversationId={activeChatId}
            token={token}
            onClose={() => setActiveChatId(null)}
          />
        </div>
      )}
    </div>
  );
}
