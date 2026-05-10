import type { TextSelection } from './use-text-selection';

interface AskAiButtonProps {
  selection: TextSelection;
  onAsk: (selection: TextSelection) => void;
}

export function AskAiButton({ selection, onAsk }: AskAiButtonProps) {
  // Use fixed positioning (viewport-relative) so scrollY doesn't affect placement.
  const top = selection.rect.top - 40;
  const left = selection.rect.left + selection.rect.width / 2;

  return (
    <button
      onMouseDown={(e) => {
        e.preventDefault();
        onAsk(selection);
      }}
      style={{
        position: 'fixed',
        top,
        left,
        transform: 'translateX(-50%)',
        zIndex: 1000,
        padding: '4px 10px',
        fontSize: 13,
        fontFamily: 'var(--font-sans)',
        backgroundColor: 'var(--color-bg-inverse)',
        color: 'var(--color-text-inverse)',
        border: 'none',
        borderRadius: 'var(--radius-sm)',
        cursor: 'pointer',
        whiteSpace: 'nowrap',
      }}
    >
      Ask AI
    </button>
  );
}
