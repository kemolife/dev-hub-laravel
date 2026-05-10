import type { ApiAiConversation } from '../../types';
import type { StreamChunk } from './types';

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';

export async function fetchPostConversations(
  slug: string,
  token: string,
): Promise<ApiAiConversation[]> {
  const res = await fetch(`${BASE_URL}/posts/${slug}/conversations`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return (data.data ?? data) as ApiAiConversation[];
}

export async function fetchConversation(
  conversationId: string,
  token: string,
): Promise<ApiAiConversation> {
  const res = await fetch(`${BASE_URL}/conversations/${conversationId}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return (data.data ?? data) as ApiAiConversation;
}

export async function* startConversationStream(
  slug: string,
  selectedText: string,
  selectionStart: number,
  selectionEnd: number,
  token: string,
): AsyncGenerator<StreamChunk> {
  const res = await fetch(`${BASE_URL}/posts/${slug}/conversations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
      Accept: 'text/event-stream',
    },
    body: JSON.stringify({ selected_text: selectedText, selection_start: selectionStart, selection_end: selectionEnd }),
  });

  if (!res.ok || !res.body) throw new Error(`HTTP ${res.status}`);

  yield* parseEventStream(res.body);
}

export async function* continueConversationStream(
  conversationId: string,
  content: string,
  token: string,
): AsyncGenerator<StreamChunk> {
  const res = await fetch(`${BASE_URL}/conversations/${conversationId}/messages`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
      Accept: 'text/event-stream',
    },
    body: JSON.stringify({ content }),
  });

  if (!res.ok || !res.body) throw new Error(`HTTP ${res.status}`);

  yield* parseEventStream(res.body);
}

export async function toggleConversationPrivacy(
  conversationId: string,
  token: string,
): Promise<ApiAiConversation> {
  const res = await fetch(`${BASE_URL}/conversations/${conversationId}`, {
    method: 'PATCH',
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  return (data.data ?? data) as ApiAiConversation;
}

async function* parseEventStream(body: ReadableStream<Uint8Array>): AsyncGenerator<StreamChunk> {
  const reader = body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';

  try {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });

      const parts = buffer.split('\n\n');
      buffer = parts.pop() ?? '';

      for (const part of parts) {
        for (const line of part.split('\n')) {
          if (!line.startsWith('data: ')) continue;
          let json: Record<string, unknown>;
          try {
            json = JSON.parse(line.slice(6)) as Record<string, unknown>;
          } catch {
            continue;
          }
          if (typeof json.content === 'string') {
            yield { type: 'content', content: json.content };
          } else if (json.done === true) {
            yield { type: 'done', conversationId: typeof json.conversation_id === 'string' ? json.conversation_id : undefined };
          }
        }
      }
    }
  } finally {
    reader.releaseLock();
  }
}
