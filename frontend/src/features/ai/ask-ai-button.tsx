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
        padding: '6px 14px',
        fontSize: 13,
        fontWeight: 500,
        fontFamily: 'var(--font-sans)',
        backgroundColor: '#1a1a1a',
        color: '#ffffff',
        border: 'none',
        borderRadius: '20px',
        cursor: 'pointer',
        whiteSpace: 'nowrap',
        boxShadow: '0 2px 8px rgba(0,0,0,0.25)',
        letterSpacing: '0.01em',
      }}
    >
      Ask AI
    </button>
  );
}
