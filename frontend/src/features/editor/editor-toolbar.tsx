import type { RefObject } from 'react';
import type { EditorView } from '@codemirror/view';

type ToolbarAction = 'bold' | 'italic' | 'code' | 'codeblock' | 'heading' | 'link' | 'image';

interface EditorToolbarProps {
  viewRef: RefObject<EditorView | null>;
}

const ACTIONS: { action: ToolbarAction; label: string; title: string }[] = [
  { action: 'bold', label: 'B', title: 'Bold (Ctrl+B)' },
  { action: 'italic', label: 'I', title: 'Italic (Ctrl+I)' },
  { action: 'code', label: '`', title: 'Inline code' },
  { action: 'codeblock', label: '```', title: 'Code block' },
  { action: 'heading', label: 'H', title: 'Heading' },
  { action: 'link', label: 'Link', title: 'Link' },
  { action: 'image', label: 'Img', title: 'Image' },
];

function applyAction(view: EditorView, action: ToolbarAction): void {
  const { from, to } = view.state.selection.main;
  const selected = view.state.sliceDoc(from, to);

  const wrap = (before: string, after: string, placeholder: string) => {
    const text = selected || placeholder;
    view.dispatch({
      changes: { from, to, insert: `${before}${text}${after}` },
      selection: { anchor: from + before.length, head: from + before.length + text.length },
    });
    view.focus();
  };

  switch (action) {
    case 'bold':
      wrap('**', '**', 'bold text');
      break;
    case 'italic':
      wrap('*', '*', 'italic text');
      break;
    case 'code':
      wrap('`', '`', 'code');
      break;
    case 'codeblock': {
      const body = selected || '';
      const insert = `\`\`\`language\n${body}\n\`\`\``;
      view.dispatch({
        changes: { from, to, insert },
        selection: { anchor: from + 3, head: from + 11 },
      });
      view.focus();
      break;
    }
    case 'heading':
      wrap('## ', '', 'Heading');
      break;
    case 'link':
      wrap('[', '](url)', 'link text');
      break;
    case 'image':
      wrap('![', '](url)', 'alt text');
      break;
  }
}

export function EditorToolbar({ viewRef }: EditorToolbarProps) {
  function handleAction(action: ToolbarAction) {
    if (viewRef.current) {
      applyAction(viewRef.current, action);
    }
  }

  return (
    <div
      className="flex gap-1"
      style={{
        padding: '8px 12px',
        borderBottom: '0.5px solid var(--color-border-tertiary)',
        backgroundColor: 'var(--color-bg-secondary)',
      }}
    >
      {ACTIONS.map(({ action, label, title }) => (
        <button
          key={action}
          title={title}
          onMouseDown={(e) => {
            e.preventDefault();
            handleAction(action);
          }}
          style={{
            minWidth: action === 'codeblock' ? 40 : 28,
            height: 28,
            padding: '0 6px',
            border: '0.5px solid var(--color-border-tertiary)',
            borderRadius: 'var(--radius-sm)',
            backgroundColor: 'var(--color-bg-primary)',
            cursor: 'pointer',
            fontSize: action === 'bold' ? 13 : 12,
            fontWeight: action === 'bold' ? 700 : action === 'italic' ? undefined : 400,
            fontStyle: action === 'italic' ? 'italic' : undefined,
            fontFamily: ['code', 'codeblock'].includes(action) ? 'monospace' : 'inherit',
            color: 'var(--color-text-primary)',
          }}
        >
          {label}
        </button>
      ))}
    </div>
  );
}
