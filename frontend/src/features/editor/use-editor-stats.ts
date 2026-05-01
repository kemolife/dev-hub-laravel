import { useMemo } from 'react';
import { readingTime, wordCount } from '../../lib/utils';

export interface EditorStats {
  wordCount: number;
  readingMinutes: number;
  codeBlockCount: number;
}

export function useEditorStats(content: string): EditorStats {
  return useMemo(() => {
    const words = wordCount(content);
    const codeBlockMatches = content.match(/```/g) ?? [];
    const codeBlockCount = Math.floor(codeBlockMatches.length / 2);
    return {
      wordCount: words,
      readingMinutes: readingTime(words),
      codeBlockCount,
    };
  }, [content]);
}
