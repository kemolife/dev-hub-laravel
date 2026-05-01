import { useEffect, useRef, useState } from 'react';

export function useAutoSave(content: string, delayMs = 3000): string {
  const [savedAt, setSavedAt] = useState<Date | null>(null);
  const [secondsAgo, setSecondsAgo] = useState(0);
  const saveTimer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

  useEffect(() => {
    clearTimeout(saveTimer.current);
    saveTimer.current = setTimeout(() => {
      setSavedAt(new Date());
      setSecondsAgo(0);
    }, delayMs);
    return () => clearTimeout(saveTimer.current);
  }, [content, delayMs]);

  useEffect(() => {
    if (!savedAt) return;
    const interval = setInterval(() => {
      const secs = Math.floor((Date.now() - savedAt.getTime()) / 1000);
      setSecondsAgo(secs);
      if (secs > 120) clearInterval(interval);
    }, 1000);
    return () => clearInterval(interval);
  }, [savedAt]);

  if (!savedAt) return 'Draft';
  if (secondsAgo < 5) return 'Draft · saved just now';
  if (secondsAgo > 120) return 'Draft · saved a few minutes ago';
  return `Draft · saved ${secondsAgo} seconds ago`;
}
