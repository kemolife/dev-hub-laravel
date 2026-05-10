import { useEffect, useRef, useState } from 'react';
import { startConversationStream } from './api';
import type { TextSelection } from './use-text-selection';

interface ExplanationModalProps {
  selection: TextSelection;
  postSlug: string;
  token: string;
  onClose: () => void;
  onOpenChat: (conversationId: string) => void;
}

export function ExplanationModal({ selection, postSlug, token, onClose, onOpenChat }: ExplanationModalProps) {
  const [content, setContent] = useState('');
  const [isDone, setIsDone] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [conversationId, setConversationId] = useState<string | null>(null);
  const abortedRef = useRef(false);

  useEffect(() => {
    abortedRef.current = false;
    setContent('');
    setIsDone(false);
    setError(null);

    async function stream() {
      try {
        const gen = startConversationStream(
          postSlug,
          selection.text,
          selection.start,
          selection.end,
          token,
        );
        for await (const chunk of gen) {
          if (abortedRef.current) break;
          if (chunk.type === 'content') {
            setContent((prev) => prev + chunk.content);
          } else if (chunk.type === 'done') {
            setConversationId(chunk.conversationId ?? null);
            setIsDone(true);
          }
        }
      } catch {
        if (!abortedRef.current) setError('AI service is unavailable. Please try again.');
      }
    }

    void stream();

    return () => { abortedRef.current = true; };
  }, [selection, postSlug, token]);

  return (
    <>
    {/* Yellow marker over the selected text so user sees what was asked about */}
    <div
      style={{
        position: 'fixed',
        top: selection.rect.top,
        left: selection.rect.left,
        width: selection.rect.width,
        height: selection.rect.height,
        backgroundColor: 'rgba(255, 236, 61, 0.45)',
        pointerEvents: 'none',
        zIndex: 1999,
      }}
    />
    <div
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 2000,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'rgba(0,0,0,0.4)',
      }}
      onClick={onClose}
    >
      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          border: '0.5px solid var(--color-border-primary)',
          borderRadius: 'var(--radius-lg)',
          padding: '24px',
          maxWidth: 560,
          width: '90%',
          maxHeight: '70vh',
          overflowY: 'auto',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <blockquote
          style={{
            borderLeft: '3px solid var(--color-border-secondary)',
            paddingLeft: 12,
            margin: '0 0 16px',
            color: 'var(--color-text-secondary)',
            fontSize: 14,
            fontStyle: 'italic',
          }}
        >
          {selection.text.length > 200 ? `${selection.text.slice(0, 200)}…` : selection.text}
        </blockquote>

        {error ? (
          <p style={{ color: 'var(--color-error, #e53e3e)', fontSize: 14 }}>{error}</p>
        ) : (
          <p style={{ fontSize: 15, lineHeight: 1.6, margin: 0, whiteSpace: 'pre-wrap' }}>
            {content}
            {!isDone && <span style={{ opacity: 0.5 }}>▍</span>}
          </p>
        )}

        <div style={{ display: 'flex', gap: 8, marginTop: 20, justifyContent: 'flex-end' }}>
          <button
            onClick={onClose}
            style={{
              padding: '6px 14px',
              fontSize: 13,
              border: '0.5px solid var(--color-border-primary)',
              borderRadius: 'var(--radius-sm)',
              backgroundColor: 'transparent',
              cursor: 'pointer',
              color: 'var(--color-text-primary)',
            }}
          >
            Close
          </button>
          {isDone && conversationId && (
            <button
              onClick={() => { onClose(); onOpenChat(conversationId); }}
              style={{
                padding: '6px 14px',
                fontSize: 13,
                border: 'none',
                borderRadius: 'var(--radius-sm)',
                backgroundColor: 'var(--color-bg-inverse)',
                color: 'var(--color-text-inverse)',
                cursor: 'pointer',
              }}
            >
              Continue chatting →
            </button>
          )}
        </div>
      </div>
    </div>
    </>
  );
}
