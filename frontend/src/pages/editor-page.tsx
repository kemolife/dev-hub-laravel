import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { EditorSidebar } from '../features/editor/editor-sidebar';
import { useAutoSave } from '../features/editor/use-auto-save';
import { useEditorStats } from '../features/editor/use-editor-stats';
import { WritingArea } from '../features/editor/writing-area';
import { api } from '../lib/api';
import type { ApiPost } from '../types';

export function EditorPage() {
  const { token } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const [title, setTitle] = useState('');
  const [subtitle, setSubtitle] = useState('');
  const [content, setContent] = useState('');
  const [tags, setTags] = useState<string[]>([]);
  const [slugRef, setSlugRef] = useState<string | null>(null);
  const [isPublishing, setIsPublishing] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);

  // Keep mutable refs so the save callback always sees latest values without
  // adding them as dependencies (which would re-create the callback too often).
  const titleRef = useRef(title);
  const subtitleRef = useRef(subtitle);
  const contentRef = useRef(content);
  const tagsRef = useRef(tags);
  const slugRefMutable = useRef<string | null>(null);

  useEffect(() => { titleRef.current = title; }, [title]);
  useEffect(() => { subtitleRef.current = subtitle; }, [subtitle]);
  useEffect(() => { contentRef.current = content; }, [content]);
  useEffect(() => { tagsRef.current = tags; }, [tags]);
  useEffect(() => { slugRefMutable.current = slugRef; }, [slugRef]);

  // Load existing post when ?slug= is provided
  useEffect(() => {
    const editSlug = searchParams.get('slug');
    if (!editSlug || !token) return;

    api
      .get<ApiPost>(`/posts/${editSlug}`, token)
      .then((post) => {
        setTitle(post.title);
        setSubtitle(post.subtitle ?? '');
        setContent(post.body);
        setTags(post.tags.map((t) => t.name));
        setSlugRef(post.slug);
      })
      .catch(() => setLoadError('Could not load post for editing.'));
  }, [searchParams, token]);

  const handleSave = useCallback(async () => {
    if (!token) return;

    const body = {
      title: titleRef.current,
      subtitle: subtitleRef.current || undefined,
      body: contentRef.current,
      tags: tagsRef.current,
    };

    if (slugRefMutable.current) {
      await api.put<ApiPost>(`/posts/${slugRefMutable.current}`, body, token);
    } else {
      const created = await api.post<ApiPost>('/posts', { ...body, status: 'draft' }, token);
      setSlugRef(created.slug);
    }
  }, [token]);

  const { label: draftLabel, isDirty, markDirty } = useAutoSave({ onSave: handleSave });

  function handleTitleChange(value: string) {
    setTitle(value);
    markDirty();
  }

  function handleSubtitleChange(value: string) {
    setSubtitle(value);
    markDirty();
  }

  function handleContentChange(value: string) {
    setContent(value);
    markDirty();
  }

  function handleTagsChange(nextTags: string[]) {
    setTags(nextTags);
    markDirty();
  }

  async function handlePublish() {
    if (!token || !slugRef) return;

    setIsPublishing(true);
    try {
      await api.post<ApiPost>(`/posts/${slugRef}/publish`, {}, token);
      await navigate(`/posts/${slugRef}`);
    } finally {
      setIsPublishing(false);
    }
  }

  const stats = useEditorStats(content);

  const autoSaveLabelColor = isDirty
    ? 'var(--color-text-tertiary)'
    : 'var(--color-text-secondary)';

  if (loadError) {
    return (
      <div style={{ padding: 40, textAlign: 'center' }}>
        <p style={{ color: 'var(--color-text-secondary)' }}>{loadError}</p>
        <Link to="/" style={{ color: 'var(--color-text-primary)' }}>
          ← Back to feed
        </Link>
      </div>
    );
  }

  return (
    <div
      style={{
        maxWidth: 1080,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <>
            <Link
              to="/"
              style={{
                fontFamily: 'var(--font-serif)',
                fontSize: 16,
                fontWeight: 500,
                textDecoration: 'none',
                color: 'inherit',
              }}
            >
              DevHub
            </Link>
            <span style={{ fontSize: 13, color: autoSaveLabelColor }}>
              {draftLabel}
            </span>
          </>
        }
        right={
          <>
            <Button>Preview</Button>
            <Button
              variant="primary"
              onClick={() => { void handlePublish(); }}
              disabled={isPublishing || !slugRef}
            >
              {isPublishing ? 'Publishing…' : 'Publish ↗'}
            </Button>
          </>
        }
      />

      <div style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1fr) 240px' }}>
        <WritingArea
          title={title}
          subtitle={subtitle}
          onTitleChange={handleTitleChange}
          onSubtitleChange={handleSubtitleChange}
          onContentChange={handleContentChange}
        />
        <EditorSidebar stats={stats} tags={tags} onTagsChange={handleTagsChange} />
      </div>
    </div>
  );
}
