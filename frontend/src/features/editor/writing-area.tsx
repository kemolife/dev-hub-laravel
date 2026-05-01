import { useRef } from 'react';

interface WritingAreaProps {
  title: string;
  subtitle: string;
  onTitleChange: (value: string) => void;
  onSubtitleChange: (value: string) => void;
  onContentChange: (value: string) => void;
}

export function WritingArea({
  title,
  subtitle,
  onTitleChange,
  onSubtitleChange,
  onContentChange,
}: WritingAreaProps) {
  const contentRef = useRef<HTMLDivElement>(null);

  function handleContentInput() {
    if (contentRef.current) {
      onContentChange(contentRef.current.innerText);
    }
  }

  return (
    <div
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        padding: '32px 48px',
        minHeight: 520,
      }}
    >
      <input
        type="text"
        placeholder="Title"
        value={title}
        onChange={(e) => onTitleChange(e.target.value)}
        style={{
          width: '100%',
          border: 'none',
          background: 'transparent',
          fontSize: 26,
          fontWeight: 500,
          padding: 0,
          margin: '0 0 8px',
          lineHeight: 1.3,
          outline: 'none',
          fontFamily: 'inherit',
        }}
      />

      <input
        type="text"
        placeholder="A short subtitle (optional)"
        value={subtitle}
        onChange={(e) => onSubtitleChange(e.target.value)}
        style={{
          width: '100%',
          border: 'none',
          background: 'transparent',
          fontSize: 16,
          padding: 0,
          margin: '0 0 24px',
          color: 'var(--color-text-secondary)',
          outline: 'none',
          fontFamily: 'inherit',
        }}
      />

      <div
        style={{ borderTop: '0.5px solid var(--color-border-tertiary)', paddingTop: 24 }}
      >
        <div
          ref={contentRef}
          contentEditable
          suppressContentEditableWarning
          onInput={handleContentInput}
          style={{
            outline: 'none',
            fontFamily: 'var(--font-serif)',
            fontSize: 18,
            lineHeight: 1.7,
            minHeight: 300,
            color: 'var(--color-text-primary)',
          }}
          data-placeholder="Start writing..."
        />
      </div>
    </div>
  );
}
