import { useEffect, useRef, useState } from 'react';
import { fetchConversation, continueConversationStream } from './api';
import type { ApiAiConversation, ApiAiMessage } from '../../types';

interface ChatPanelProps {
  conversationId: string;
  token: string;
  onClose: () => void;
}

export function ChatPanel({ conversationId, token, onClose }: ChatPanelProps) {
  const [conversation, setConversation] = useState<ApiAiConversation | null>(null);
  const [messages, setMessages] = useState<ApiAiMessage[]>([]);
  const [input, setInput] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const [streamingContent, setStreamingContent] = useState('');
  const [error, setError] = useState<string | null>(null);
  const bottomRef = useRef<HTMLDivElement>(null);
  const isMountedRef = useRef(true);

  useEffect(() => {
    return () => { isMountedRef.current = false; };
  }, []);

  useEffect(() => {
    fetchConversation(conversationId, token)
      .then((conv) => {
        setConversation(conv);
        setMessages(conv.messages);
      })
      .catch(() => setError('Failed to load conversation.'));
  }, [conversationId, token]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, streamingContent]);

  async function handleSend() {
    const content = input.trim();
    if (!content || isStreaming) return;
    setInput('');
    setIsStreaming(true);
    setStreamingContent('');
    setError(null);

    setMessages((prev) => [
      ...prev,
      { id: Date.now(), role: 'user', content, created_at: new Date().toISOString() },
    ]);

    try {
      let full = '';
      const gen = continueConversationStream(conversationId, content, token);
      for await (const chunk of gen) {
        if (!isMountedRef.current) break;
        if (chunk.type === 'content') {
          full += chunk.content;
          setStreamingContent(full);
        } else if (chunk.type === 'done') {
          setMessages((prev) => [
            ...prev,
            { id: Date.now() + 1, role: 'assistant', content: full, created_at: new Date().toISOString() },
          ]);
          setStreamingContent('');
          setIsStreaming(false);
        }
      }
      // Stream ended without a done event (e.g. server-side error after partial data).
      if (isMountedRef.current) setIsStreaming(false);
    } catch {
      if (isMountedRef.current) {
        setError('AI service is unavailable. Please try again.');
        setIsStreaming(false);
      }
    }
  }

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        right: 0,
        width: 360,
        height: '100vh',
        backgroundColor: 'var(--color-bg-primary)',
        borderLeft: '0.5px solid var(--color-border-primary)',
        display: 'flex',
        flexDirection: 'column',
        zIndex: 500,
        fontFamily: 'var(--font-sans)',
      }}
    >
      {/* Header */}
      <div
        style={{
          padding: '12px 16px',
          borderBottom: '0.5px solid var(--color-border-tertiary)',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          gap: 8,
        }}
      >
        <p
          style={{
            margin: 0,
            fontSize: 12,
            color: 'var(--color-text-secondary)',
            fontStyle: 'italic',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap',
            flex: 1,
          }}
        >
          {conversation
            ? conversation.selected_text.length > 60
              ? `"${conversation.selected_text.slice(0, 60)}…"`
              : `"${conversation.selected_text}"`
            : ''}
        </p>
        <button
          onClick={onClose}
          style={{
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            color: 'var(--color-text-secondary)',
            fontSize: 18,
            lineHeight: 1,
            flexShrink: 0,
          }}
        >
          ×
        </button>
      </div>

      {/* Messages */}
      <div style={{ flex: 1, overflowY: 'auto', padding: '12px 16px', display: 'flex', flexDirection: 'column', gap: 12 }}>
        {messages.map((msg) => (
          <MessageBubble key={msg.id} message={msg} />
        ))}
        {isStreaming && streamingContent && (
          <MessageBubble
            message={{ id: 0, role: 'assistant', content: streamingContent, created_at: '' }}
            streaming
          />
        )}
        {error && <p style={{ color: 'var(--color-error, #e53e3e)', fontSize: 13 }}>{error}</p>}
        <div ref={bottomRef} />
      </div>

      {/* Input */}
      <div style={{ padding: '12px 16px', borderTop: '0.5px solid var(--color-border-tertiary)', display: 'flex', gap: 8 }}>
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); void handleSend(); } }}
          placeholder="Ask a follow-up…"
          disabled={isStreaming}
          style={{
            flex: 1,
            padding: '8px 10px',
            fontSize: 13,
            border: '0.5px solid var(--color-border-primary)',
            borderRadius: 'var(--radius-sm)',
            backgroundColor: 'var(--color-bg-secondary)',
            color: 'var(--color-text-primary)',
            outline: 'none',
          }}
        />
        <button
          onClick={() => void handleSend()}
          disabled={isStreaming || input.trim() === ''}
          style={{
            padding: '8px 14px',
            fontSize: 13,
            border: 'none',
            borderRadius: 'var(--radius-sm)',
            backgroundColor: 'var(--color-bg-inverse)',
            color: 'var(--color-text-inverse)',
            cursor: 'pointer',
            opacity: isStreaming || input.trim() === '' ? 0.5 : 1,
          }}
        >
          Send
        </button>
      </div>
    </div>
  );
}

function MessageBubble({ message, streaming = false }: { message: ApiAiMessage; streaming?: boolean }) {
  const isUser = message.role === 'user';
  return (
    <div style={{ display: 'flex', justifyContent: isUser ? 'flex-end' : 'flex-start' }}>
      <div
        style={{
          maxWidth: '85%',
          padding: '8px 12px',
          borderRadius: 'var(--radius-sm)',
          fontSize: 13,
          lineHeight: 1.55,
          whiteSpace: 'pre-wrap',
          backgroundColor: isUser ? 'var(--color-bg-inverse)' : 'var(--color-bg-secondary)',
          color: isUser ? 'var(--color-text-inverse)' : 'var(--color-text-primary)',
        }}
      >
        {message.content}
        {streaming && <span style={{ opacity: 0.5 }}>▍</span>}
      </div>
    </div>
  );
}
