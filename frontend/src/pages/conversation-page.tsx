import { useParams, Link } from 'react-router';
import { useEffect, useRef, useState } from 'react';
import { Topbar } from '../components/layout/topbar';
import { fetchConversation, continueConversationStream } from '../features/ai/api';
import { useAuth } from '../features/auth/auth-context';
import type { ApiAiConversation, ApiAiMessage } from '../types';

export function ConversationPage() {
  const { id } = useParams<{ id: string }>();
  const { token } = useAuth();
  const [conversation, setConversation] = useState<ApiAiConversation | null>(null);
  const [messages, setMessages] = useState<ApiAiMessage[]>([]);
  const [input, setInput] = useState('');
  const [isStreaming, setIsStreaming] = useState(false);
  const [streamingContent, setStreamingContent] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [notFound, setNotFound] = useState(false);
  const isMountedRef = useRef(true);

  useEffect(() => {
    return () => { isMountedRef.current = false; };
  }, []);

  useEffect(() => {
    if (!id || !token) return;
    fetchConversation(id, token)
      .then((conv) => { setConversation(conv); setMessages(conv.messages); })
      .catch(() => setNotFound(true));
  }, [id, token]);

  async function handleSend() {
    const content = input.trim();
    if (!content || isStreaming || !id || !token) return;
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
      for await (const chunk of continueConversationStream(id, content, token)) {
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
      if (isMountedRef.current) setIsStreaming(false);
    } catch {
      if (isMountedRef.current) {
        setError('AI service is unavailable.');
        setIsStreaming(false);
      }
    }
  }

  if (notFound) {
    return (
      <div style={{ padding: 40, textAlign: 'center' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>Conversation not found.</p>
        <Link to="/">← Back to feed</Link>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: 640, margin: '0 auto', padding: '0 16px' }}>
      <Topbar
        left={<Link to="/" style={{ fontFamily: 'var(--font-serif)', fontSize: 18, fontWeight: 500, textDecoration: 'none', color: 'inherit' }}>DevHub</Link>}
        right={null}
      />

      {conversation && (
        <blockquote style={{ borderLeft: '3px solid var(--color-border-secondary)', paddingLeft: 12, margin: '24px 0 0', color: 'var(--color-text-secondary)', fontSize: 14, fontStyle: 'italic' }}>
          {conversation.selected_text}
        </blockquote>
      )}

      <div style={{ display: 'flex', flexDirection: 'column', gap: 12, padding: '16px 0', minHeight: 300 }}>
        {messages.map((msg) => (
          <div key={msg.id} style={{ display: 'flex', justifyContent: msg.role === 'user' ? 'flex-end' : 'flex-start' }}>
            <div style={{
              maxWidth: '85%',
              padding: '10px 14px',
              borderRadius: 'var(--radius-sm)',
              fontSize: 14,
              lineHeight: 1.6,
              whiteSpace: 'pre-wrap',
              backgroundColor: msg.role === 'user' ? 'var(--color-bg-inverse)' : 'var(--color-bg-secondary)',
              color: msg.role === 'user' ? 'var(--color-text-inverse)' : 'var(--color-text-primary)',
            }}>
              {msg.content}
            </div>
          </div>
        ))}
        {isStreaming && streamingContent && (
          <div style={{ display: 'flex', justifyContent: 'flex-start' }}>
            <div style={{ maxWidth: '85%', padding: '10px 14px', borderRadius: 'var(--radius-sm)', fontSize: 14, lineHeight: 1.6, whiteSpace: 'pre-wrap', backgroundColor: 'var(--color-bg-secondary)', color: 'var(--color-text-primary)' }}>
              {streamingContent}<span style={{ opacity: 0.5 }}>▍</span>
            </div>
          </div>
        )}
        {error && <p style={{ color: 'var(--color-error, #e53e3e)', fontSize: 14 }}>{error}</p>}
      </div>

      <div style={{ position: 'sticky', bottom: 0, backgroundColor: 'var(--color-bg-primary)', padding: '12px 0', display: 'flex', gap: 8 }}>
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); void handleSend(); } }}
          placeholder="Ask a follow-up…"
          disabled={isStreaming}
          style={{ flex: 1, padding: '10px 12px', fontSize: 14, border: '0.5px solid var(--color-border-primary)', borderRadius: 'var(--radius-sm)', backgroundColor: 'var(--color-bg-secondary)', color: 'var(--color-text-primary)', outline: 'none' }}
        />
        <button
          onClick={() => void handleSend()}
          disabled={isStreaming || input.trim() === ''}
          style={{ padding: '10px 16px', fontSize: 14, border: 'none', borderRadius: 'var(--radius-sm)', backgroundColor: 'var(--color-bg-inverse)', color: 'var(--color-text-inverse)', cursor: 'pointer', opacity: isStreaming || input.trim() === '' ? 0.5 : 1 }}
        >
          Send
        </button>
      </div>
    </div>
  );
}
