import { useEffect, useState } from 'react';

export interface TextSelection {
  text: string;
  start: number;
  end: number;
  rect: DOMRect;
}

export function useTextSelection(containerRef: React.RefObject<HTMLElement | null>): TextSelection | null {
  const [selection, setSelection] = useState<TextSelection | null>(null);

  useEffect(() => {
    // Only commit a selection after mouse release. Updating state during drag
    // causes React to re-render mid-selection, which can collapse the browser selection.
    let isMouseDown = false;

    function readSelection() {
      const sel = window.getSelection();

      if (!sel || sel.isCollapsed || sel.toString().trim() === '') {
        setSelection(null);
        return;
      }

      const container = containerRef.current;
      if (!container) return;

      const range = sel.getRangeAt(0);
      if (!container.contains(range.commonAncestorContainer)) {
        setSelection(null);
        return;
      }

      const preRange = document.createRange();
      preRange.selectNodeContents(container);
      preRange.setEnd(range.startContainer, range.startOffset);
      const start = preRange.toString().length;
      const text = sel.toString();

      setSelection({
        text,
        start,
        end: start + text.length,
        rect: range.getBoundingClientRect(),
      });
    }

    function handleMouseDown() { isMouseDown = true; }
    function handleMouseUp() { isMouseDown = false; readSelection(); }
    // Handles keyboard selection (Shift+Arrow, etc.) and selection clears.
    function handleSelectionChange() { if (!isMouseDown) readSelection(); }

    document.addEventListener('mousedown', handleMouseDown);
    document.addEventListener('mouseup', handleMouseUp);
    document.addEventListener('selectionchange', handleSelectionChange);
    return () => {
      document.removeEventListener('mousedown', handleMouseDown);
      document.removeEventListener('mouseup', handleMouseUp);
      document.removeEventListener('selectionchange', handleSelectionChange);
    };
  }, [containerRef]);

  return selection;
}
