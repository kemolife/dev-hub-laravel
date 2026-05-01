import { useState } from 'react';
import { Link } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { EditorSidebar } from '../features/editor/editor-sidebar';
import { useAutoSave } from '../features/editor/use-auto-save';
import { useEditorStats } from '../features/editor/use-editor-stats';
import { WritingArea } from '../features/editor/writing-area';

export function EditorPage() {
  const [title, setTitle] = useState('');
  const [subtitle, setSubtitle] = useState('');
  const [content, setContent] = useState('');
  const [tags, setTags] = useState<string[]>([]);

  const stats = useEditorStats(content);
  const draftLabel = useAutoSave(title + content);

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
            <span style={{ fontSize: 13, color: 'var(--color-text-secondary)' }}>
              {draftLabel}
            </span>
          </>
        }
        right={
          <>
            <Button>Preview</Button>
            <Button variant="primary">Publish ↗</Button>
          </>
        }
      />

      <div style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1fr) 240px' }}>
        <WritingArea
          title={title}
          subtitle={subtitle}
          onTitleChange={setTitle}
          onSubtitleChange={setSubtitle}
          onContentChange={setContent}
        />
        <EditorSidebar stats={stats} tags={tags} onTagsChange={setTags} />
      </div>
    </div>
  );
}
