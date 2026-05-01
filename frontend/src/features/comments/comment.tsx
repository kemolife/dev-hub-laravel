import { useState } from 'react';
import { Avatar } from '../../components/ui/avatar';
import { Button } from '../../components/ui/button';
import { relativeTime } from '../../lib/utils';
import type { ApiComment } from '../../types';

function getInitials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

function isWithin15Minutes(dateString: string): boolean {
  const diffMs = Date.now() - new Date(dateString).getTime();
  return diffMs < 15 * 60 * 1000;
}

interface CommentProps {
  comment: ApiComment;
  currentUserId: number | null;
  postSlug: string;
  token: string | null;
  depth: number;
  onReply: (parentId: number) => void;
  onEdit: (commentId: number, newBody: string) => Promise<void>;
  onDelete: (commentId: number) => Promise<void>;
}

export function Comment({
  comment,
  currentUserId,
  postSlug: _postSlug,
  token,
  depth,
  onReply,
  onEdit,
  onDelete,
}: CommentProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [editBody, setEditBody] = useState(comment.body);
  const [isConfirmingDelete, setIsConfirmingDelete] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [editError, setEditError] = useState<string | null>(null);

  const isOwn = currentUserId !== null && comment.author.id === currentUserId;
  const canEdit = isOwn && isWithin15Minutes(comment.created_at);
  const isAuthenticated = token !== null;
  const visibleIndent = Math.min(depth, 2);

  async function handleSaveEdit() {
    if (!editBody.trim()) return;
    setIsSaving(true);
    setEditError(null);
    try {
      await onEdit(comment.id, editBody.trim());
      setIsEditing(false);
    } catch {
      setEditError('Failed to save edit. Please try again.');
    } finally {
      setIsSaving(false);
    }
  }

  async function handleConfirmDelete() {
    setIsDeleting(true);
    try {
      await onDelete(comment.id);
    } catch {
      setIsDeleting(false);
      setIsConfirmingDelete(false);
    }
  }

  function handleEditKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
      e.preventDefault();
      handleSaveEdit();
    }
    if (e.key === 'Escape') {
      setIsEditing(false);
      setEditBody(comment.body);
      setEditError(null);
    }
  }

  return (
    <div
      style={{
        marginLeft: visibleIndent > 0 ? 32 : 0,
        borderLeft: visibleIndent > 0 ? '2px solid var(--color-border-tertiary)' : undefined,
        paddingLeft: visibleIndent > 0 ? 16 : 0,
      }}
    >
      <div className="flex gap-3 py-4">
        <Avatar
          initials={getInitials(comment.author.name)}
          size="sm"
          bg="var(--color-bg-tertiary)"
          color="var(--color-text-secondary)"
        />

        <div className="flex-1 min-w-0">
          <div className="flex items-baseline gap-2 mb-1">
            <span
              className="text-sm font-medium"
              style={{ color: 'var(--color-text-primary)' }}
            >
              {comment.author.name}
            </span>
            <span
              className="text-xs"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              @{comment.author.username}
            </span>
            <span
              className="text-xs"
              style={{ color: 'var(--color-text-tertiary)' }}
            >
              {relativeTime(comment.created_at)}
            </span>
          </div>

          {isEditing ? (
            <div className="flex flex-col gap-2">
              <textarea
                value={editBody}
                onChange={(e) => setEditBody(e.target.value)}
                onKeyDown={handleEditKeyDown}
                autoFocus
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
              {editError && (
                <p className="text-xs m-0" style={{ color: '#dc2626' }}>
                  {editError}
                </p>
              )}
              <div className="flex gap-2">
                <Button
                  variant="primary"
                  onClick={handleSaveEdit}
                  disabled={isSaving || !editBody.trim()}
                >
                  {isSaving ? 'Saving…' : 'Save'}
                </Button>
                <Button
                  onClick={() => {
                    setIsEditing(false);
                    setEditBody(comment.body);
                    setEditError(null);
                  }}
                >
                  Cancel
                </Button>
              </div>
            </div>
          ) : (
            <>
              <div
                className="text-sm prose-content"
                style={{ color: 'var(--color-text-primary)' }}
                dangerouslySetInnerHTML={{ __html: comment.body_html }}
              />

              <div className="flex items-center gap-3 mt-2">
                {isAuthenticated && depth < 2 && (
                  <button
                    onClick={() => onReply(comment.id)}
                    className="text-xs cursor-pointer bg-transparent border-none p-0"
                    style={{ color: 'var(--color-text-tertiary)' }}
                  >
                    Reply
                  </button>
                )}
                {canEdit && (
                  <button
                    onClick={() => setIsEditing(true)}
                    className="text-xs cursor-pointer bg-transparent border-none p-0"
                    style={{ color: 'var(--color-text-tertiary)' }}
                  >
                    Edit
                  </button>
                )}
                {isOwn && !isConfirmingDelete && (
                  <button
                    onClick={() => setIsConfirmingDelete(true)}
                    className="text-xs cursor-pointer bg-transparent border-none p-0"
                    style={{ color: 'var(--color-text-tertiary)' }}
                  >
                    Delete
                  </button>
                )}
                {isConfirmingDelete && (
                  <span className="flex items-center gap-2">
                    <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                      Delete this comment?
                    </span>
                    <button
                      onClick={handleConfirmDelete}
                      disabled={isDeleting}
                      className="text-xs cursor-pointer bg-transparent border-none p-0"
                      style={{ color: '#dc2626' }}
                    >
                      {isDeleting ? 'Deleting…' : 'Yes'}
                    </button>
                    <button
                      onClick={() => setIsConfirmingDelete(false)}
                      className="text-xs cursor-pointer bg-transparent border-none p-0"
                      style={{ color: 'var(--color-text-tertiary)' }}
                    >
                      No
                    </button>
                  </span>
                )}
              </div>
            </>
          )}
        </div>
      </div>

      {comment.children && comment.children.length > 0 && (
        <div>
          {comment.children.map((child) => (
            <Comment
              key={child.id}
              comment={child}
              currentUserId={currentUserId}
              postSlug={_postSlug}
              token={token}
              depth={depth + 1}
              onReply={onReply}
              onEdit={onEdit}
              onDelete={onDelete}
            />
          ))}
        </div>
      )}
    </div>
  );
}
