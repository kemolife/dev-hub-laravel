import { useCallback, useEffect, useRef, useState } from 'react';

export type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

interface AutoSaveOptions {
  onSave: () => Promise<void>;
  delayMs?: number;
}

interface AutoSaveResult {
  status: AutoSaveStatus;
  label: string;
  isDirty: boolean;
  markDirty: () => void;
}

export function useAutoSave({ onSave, delayMs = 2000 }: AutoSaveOptions): AutoSaveResult {
  const [status, setStatus] = useState<AutoSaveStatus>('idle');
  const [savedAt, setSavedAt] = useState<Date | null>(null);
  const [secondsAgo, setSecondsAgo] = useState(0);
  const [isDirty, setIsDirty] = useState(false);
  const saveTimer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
  const onSaveRef = useRef(onSave);

  useEffect(() => {
    onSaveRef.current = onSave;
  }, [onSave]);

  const markDirty = useCallback(() => {
    setIsDirty(true);
    setStatus('idle');

    clearTimeout(saveTimer.current);
    saveTimer.current = setTimeout(async () => {
      setStatus('saving');
      try {
        await onSaveRef.current();
        setSavedAt(new Date());
        setSecondsAgo(0);
        setStatus('saved');
        setIsDirty(false);
      } catch {
        setStatus('error');
      }
    }, delayMs);
  }, [delayMs]);

  useEffect(() => {
    return () => clearTimeout(saveTimer.current);
  }, []);

  useEffect(() => {
    if (!savedAt || status !== 'saved') return;
    const interval = setInterval(() => {
      const secs = Math.floor((Date.now() - savedAt.getTime()) / 1000);
      setSecondsAgo(secs);
      if (secs > 120) clearInterval(interval);
    }, 1000);
    return () => clearInterval(interval);
  }, [savedAt, status]);

  let label = 'Draft';
  if (isDirty && status === 'idle') label = 'Unsaved changes';
  if (status === 'saving') label = 'Saving…';
  if (status === 'saved') {
    if (secondsAgo < 5) label = 'Saved';
    else if (secondsAgo > 120) label = 'Auto-saved a few minutes ago';
    else label = `Auto-saved ${secondsAgo}s ago`;
  }
  if (status === 'error') label = 'Save failed';

  return { status, label, isDirty, markDirty };
}
