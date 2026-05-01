import { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router';
import { Button } from '../../components/ui/button';
import { api } from '../../lib/api';
import type { ApiComment } from '../../types';
import { Comment } from './comment';
import { useAuth } from '../auth/auth-context';

interface CommentsSectionProps {
  postSlug: string;
  token: string | null;
}

function CommentSkeleton() {
  return (
    <div className="flex gap-3 py-4 animate-pulse">
      <div
        className="w-6 h-6 rounded-full shrink-0"
        style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
      />
      <div className="flex-1 flex flex-col gap-2">
        <div
          className="h-3 w-32 rounded"
          style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
        />
        <div
          className="h-3 w-full rounded"
          style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
        />
        <div
          className="h-3 w-3/4 rounded"
          style={{ backgroundColor: 'var(--color-bg-tertiary)' }}
        />
      </div>
    </div>
  );
}

export function CommentsSection({ postSlug, token }: CommentsSectionProps) {
  const { user } = useAuth();
  const [comments, setComments] = useState<ApiComment[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [newBody, setNewBody] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [replyToId, setReplyToId] = useState<number | null>(null);
  const [replyBody, setReplyBody] = useState('');
  const [isSubmittingReply, setIsSubmittingReply] = useState(false);
  const [replyError, setReplyError] = useState<string | null>(null);
  const replyTextareaRef = useRef<HTMLTextAreaElement>(null);

  useEffect(() => {
    setIsLoading(true);
    api
      .get<ApiComment[]>(`/posts/${postSlug}/comments`)
      .then((data) => setComments(data))
      .catch(() => setComments([]))
      .finally(() => setIsLoading(false));
  }, [postSlug]);

  useEffect(() => {
    if (replyToId !== null) {
      replyTextareaRef.current?.focus();
    }
  }, [replyToId]);

  const handleReply = useCallback((parentId: number) => {
    setReplyToId(parentId);
    setReplyBody('');
    setReplyError(null);
  }, []);

  async function handleSubmitTop() {
    if (!newBody.trim() || !token) return;
    setIsSubmitting(true);
    setSubmitError(null);

    const tempId = Date.now();
    const optimistic: ApiComment = {
      id: tempId,
      body: newBody.trim(),
      body_html: `<p>${newBody.trim()}</p>`,
      depth: 0,
      parent_id: null,
      author: {
        id: Number(user!.id),
        name: user!.name,
        username: user!.username,
        avatar_path: user!.avatar_path,
      },
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      children: [],
    };

    setComments((prev) => [...prev, optimistic]);
    setNewBody('');

    try {
      const created = await api.post<ApiComment>(
        `/posts/${postSlug}/comments`,
        { body: newBody.trim() },
        token,
      );
      setComments((prev) => prev.map((c) => (c.id === tempId ? created : c)));
    } catch {
      setComments((prev) => prev.filter((c) => c.id !== tempId));
      setNewBody(optimistic.body);
      setSubmitError('Failed to post comment. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleSubmitReply() {
    if (!replyBody.trim() || !token || replyToId === null) return;
    setIsSubmittingReply(true);
    setReplyError(null);

    const bodySnapshot = replyBody.trim();

    try {
      const created = await api.post<ApiComment>(
        `/posts/${postSlug}/comments`,
        { body: bodySnapshot, parent_id: replyToId },
        token,
      );

      setComments((prev) => insertReply(prev, replyToId, created));
      setReplyToId(null);
      setReplyBody('');
    } catch {
      setReplyError('Failed to post reply. Please try again.');
    } finally {
      setIsSubmittingReply(false);
    }
  }

  const handleEdit = useCallback(
    async (commentId: number, newBodyText: string) => {
      if (!token) return;
      const updated = await api.put<ApiComment>(
        `/posts/${postSlug}/comments/${commentId}`,
        { body: newBodyText },
        token,
      );
      setComments((prev) => updateCommentInTree(prev, updated));
    },
    [postSlug, token],
  );

  const handleDelete = useCallback(
    async (commentId: number) => {
      if (!token) return;
      await api.delete(`/posts/${postSlug}/comments/${commentId}`, token);
      setComments((prev) => removeCommentFromTree(prev, commentId));
    },
    [postSlug, token],
  );

  function handleTopKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
      e.preventDefault();
      handleSubmitTop();
    }
  }

  function handleReplyKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
      e.preventDefault();
      handleSubmitReply();
    }
    if (e.key === 'Escape') {
      setReplyToId(null);
      setReplyBody('');
      setReplyError(null);
    }
  }

  const currentUserId = user ? Number(user.id) : null;

  return (
    <div
      className="px-16 py-8"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        borderTop: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div style={{ maxWidth: 580, margin: '0 auto' }}>
        <h2
          className="text-base font-medium m-0 mb-6"
          style={{ color: 'var(--color-text-primary)' }}
        >
          Comments {!isLoading && comments.length > 0 && `(${countAll(comments)})`}
        </h2>

        {isLoading ? (
          <>
            <CommentSkeleton />
            <CommentSkeleton />
            <CommentSkeleton />
          </>
        ) : (
          <>
            {comments.length === 0 && (
              <p
                className="text-sm m-0 mb-6"
                style={{ color: 'var(--color-text-tertiary)' }}
              >
                No comments yet. Be the first to start the conversation.
              </p>
            )}

            {comments.map((comment) => (
              <div key={comment.id}>
                <Comment
                  comment={comment}
                  currentUserId={currentUserId}
                  postSlug={postSlug}
                  token={token}
                  depth={0}
                  onReply={handleReply}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                />
                {replyToId === comment.id && (
                  <ReplyForm
                    value={replyBody}
                    onChange={setReplyBody}
                    onKeyDown={handleReplyKeyDown}
                    onSubmit={handleSubmitReply}
                    onCancel={() => {
                      setReplyToId(null);
                      setReplyBody('');
                      setReplyError(null);
                    }}
                    isSubmitting={isSubmittingReply}
                    error={replyError}
                    textareaRef={replyTextareaRef}
                    indent={true}
                  />
                )}
                {comment.children?.map((child) =>
                  replyToId === child.id ? (
                    <div key={`reply-form-${child.id}`}>
                      <ReplyForm
                        value={replyBody}
                        onChange={setReplyBody}
                        onKeyDown={handleReplyKeyDown}
                        onSubmit={handleSubmitReply}
                        onCancel={() => {
                          setReplyToId(null);
                          setReplyBody('');
                          setReplyError(null);
                        }}
                        isSubmitting={isSubmittingReply}
                        error={replyError}
                        textareaRef={replyTextareaRef}
                        indent={true}
                      />
                    </div>
                  ) : null,
                )}
              </div>
            ))}
          </>
        )}

        <div
          className="mt-6 pt-6"
          style={{ borderTop: '0.5px solid var(--color-border-tertiary)' }}
        >
          {token ? (
            <div className="flex flex-col gap-3">
              <textarea
                value={newBody}
                onChange={(e) => setNewBody(e.target.value)}
                onKeyDown={handleTopKeyDown}
                placeholder="Share your thoughts… (Cmd+Enter to submit)"
                rows={3}
                style={{
                  width: '100%',
                  padding: '10px 12px',
                  fontSize: 14,
                  lineHeight: 1.6,
                  borderRadius: 'var(--radius-md)',
                  border: '0.5px solid var(--color-border-secondary)',
                  backgroundColor: 'var(--color-bg-primary)',
                  color: 'var(--color-text-primary)',
                  outline: 'none',
                  resize: 'vertical',
                  boxSizing: 'border-box',
                }}
              />
              {submitError && (
                <p className="text-xs m-0" style={{ color: '#dc2626' }}>
                  {submitError}
                </p>
              )}
              <div>
                <Button
                  variant="primary"
                  onClick={handleSubmitTop}
                  disabled={isSubmitting || !newBody.trim()}
                >
                  {isSubmitting ? 'Posting…' : 'Post comment'}
                </Button>
              </div>
            </div>
          ) : (
            <p className="text-sm m-0" style={{ color: 'var(--color-text-secondary)' }}>
              <Link
                to="/login"
                style={{ color: 'var(--color-text-primary)', fontWeight: 500 }}
              >
                Sign in
              </Link>{' '}
              to join the conversation.
            </p>
          )}
        </div>
      </div>
    </div>
  );
}

interface ReplyFormProps {
  value: string;
  onChange: (v: string) => void;
  onKeyDown: (e: React.KeyboardEvent<HTMLTextAreaElement>) => void;
  onSubmit: () => void;
  onCancel: () => void;
  isSubmitting: boolean;
  error: string | null;
  textareaRef: React.RefObject<HTMLTextAreaElement | null>;
  indent?: boolean;
}

function ReplyForm({
  value,
  onChange,
  onKeyDown,
  onSubmit,
  onCancel,
  isSubmitting,
  error,
  textareaRef,
  indent = false,
}: ReplyFormProps) {
  return (
    <div
      className="flex flex-col gap-2 pb-4"
      style={{ marginLeft: indent ? 48 : 0 }}
    >
      <textarea
        ref={textareaRef}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onKeyDown={onKeyDown}
        placeholder="Write a reply… (Cmd+Enter to submit, Esc to cancel)"
        rows={3}
        style={{
          width: '100%',
          padding: '8px 12px',
          fontSize: 14,
          lineHeight: 1.6,
          borderRadius: 'var(--radius-md)',
          border: '0.5px solid var(--color-border-secondary)',
          backgroundColor: 'var(--color-bg-primary)',
          color: 'var(--color-text-primary)',
          outline: 'none',
          resize: 'vertical',
          boxSizing: 'border-box',
        }}
      />
      {error && (
        <p className="text-xs m-0" style={{ color: '#dc2626' }}>
          {error}
        </p>
      )}
      <div className="flex gap-2">
        <Button
          variant="primary"
          onClick={onSubmit}
          disabled={isSubmitting || !value.trim()}
        >
          {isSubmitting ? 'Posting…' : 'Post reply'}
        </Button>
        <Button onClick={onCancel}>Cancel</Button>
      </div>
    </div>
  );
}

function insertReply(comments: ApiComment[], parentId: number, newComment: ApiComment): ApiComment[] {
  return comments.map((c) => {
    if (c.id === parentId) {
      return { ...c, children: [...(c.children ?? []), newComment] };
    }
    if (c.children && c.children.length > 0) {
      return { ...c, children: insertReply(c.children, parentId, newComment) };
    }
    return c;
  });
}

function updateCommentInTree(comments: ApiComment[], updated: ApiComment): ApiComment[] {
  return comments.map((c) => {
    if (c.id === updated.id) return { ...updated, children: c.children };
    if (c.children && c.children.length > 0) {
      return { ...c, children: updateCommentInTree(c.children, updated) };
    }
    return c;
  });
}

function removeCommentFromTree(comments: ApiComment[], id: number): ApiComment[] {
  return comments
    .filter((c) => c.id !== id)
    .map((c) => ({
      ...c,
      children: c.children ? removeCommentFromTree(c.children, id) : undefined,
    }));
}

function countAll(comments: ApiComment[]): number {
  return comments.reduce((acc, c) => acc + 1 + countAll(c.children ?? []), 0);
}
