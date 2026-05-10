import { useEffect, useState } from 'react';
import { fetchPostConversations } from './api';
import type { ApiAiConversation } from '../../types';

interface ConversationHighlightsProps {
  postSlug: string;
  token: string;
  containerRef: React.RefObject<HTMLElement | null>;
  onSelectConversation: (conversationId: string) => void;
}

interface Highlight {
  conversation: ApiAiConversation;
  rect: DOMRect;
}

export function ConversationHighlights({
  postSlug,
  token,
  containerRef,
  onSelectConversation,
}: ConversationHighlightsProps) {
  const [highlights, setHighlights] = useState<Highlight[]>([]);

  useEffect(() => {
    fetchPostConversations(postSlug, token)
      .then((conversations) => {
        const container = containerRef.current;
        if (!container) return;

        const computed: Highlight[] = [];

        for (const conv of conversations) {
          const rect = getRectForOffset(container, conv.selection_start, conv.selection_end);
          if (rect) computed.push({ conversation: conv, rect });
        }

        setHighlights(computed);
      })
      .catch(() => {});
  }, [postSlug, token, containerRef]);

  return (
    <>
      {highlights.map(({ conversation, rect }) => (
        <div
          key={conversation.id}
          onClick={() => onSelectConversation(conversation.id)}
          title={conversation.selected_text.slice(0, 80)}
          style={{
            position: 'absolute',
            top: rect.top + window.scrollY,
            left: rect.left,
            width: rect.width,
            height: rect.height,
            backgroundColor: 'rgba(250, 200, 80, 0.25)',
            borderBottom: '2px solid rgba(250, 200, 80, 0.8)',
            cursor: 'pointer',
            pointerEvents: 'all',
            zIndex: 10,
          }}
        />
      ))}
    </>
  );
}

function getRectForOffset(
  container: HTMLElement,
  start: number,
  end: number,
): DOMRect | null {
  const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
  let offset = 0;
  let startNode: Text | null = null;
  let startNodeOffset = 0;
  let endNode: Text | null = null;
  let endNodeOffset = 0;

  while (walker.nextNode()) {
    const node = walker.currentNode as Text;
    const len = node.length;

    if (!startNode && offset + len > start) {
      startNode = node;
      startNodeOffset = start - offset;
    }

    if (!endNode && offset + len >= end) {
      endNode = node;
      endNodeOffset = end - offset;
      break;
    }

    offset += len;
  }

  if (!startNode || !endNode) return null;

  try {
    const range = document.createRange();
    range.setStart(startNode, startNodeOffset);
    range.setEnd(endNode, endNodeOffset);
    return range.getBoundingClientRect();
  } catch {
    return null;
  }
}
