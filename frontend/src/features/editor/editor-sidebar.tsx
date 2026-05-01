import { useState } from 'react';
import { Tag } from '../../components/ui/tag';
import type { EditorStats } from './use-editor-stats';

interface EditorSidebarProps {
  stats: EditorStats;
  tags: string[];
  onTagsChange: (tags: string[]) => void;
}

const MAX_TAGS = 5;

export function EditorSidebar({ stats, tags, onTagsChange }: EditorSidebarProps) {
  const [tagInput, setTagInput] = useState('');

  function handleTagKeyDown(e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key !== 'Enter' && e.key !== ',') return;
    e.preventDefault();
    const value = tagInput.trim().toLowerCase().replace(/\s+/g, '-');
    if (value && !tags.includes(value) && tags.length < MAX_TAGS) {
      onTagsChange([...tags, value]);
    }
    setTagInput('');
  }

  function removeTag(tag: string) {
    onTagsChange(tags.filter((t) => t !== tag));
  }

  return (
    <aside
      style={{
        backgroundColor: 'var(--color-bg-secondary)',
        padding: '24px 20px',
        borderLeft: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <StatRow label="Reading time" value={`${stats.readingMinutes} min`} />
      <StatRow label="Word count" value={stats.wordCount.toString()} />
      <StatRow label="Code blocks" value={stats.codeBlockCount.toString()} />

      <div
        style={{
          borderTop: '0.5px solid var(--color-border-tertiary)',
          paddingTop: 20,
          marginBottom: 24,
        }}
      >
        <p
          style={{ color: 'var(--color-text-tertiary)', margin: '0 0 8px', fontSize: 12, fontWeight: 500 }}
        >
          Tags
        </p>
        <div className="flex gap-1 flex-wrap mb-2" style={{ marginBottom: 8 }}>
          {tags.map((tag) => (
            <button
              key={tag}
              onClick={() => removeTag(tag)}
              title="Remove tag"
              style={{
                all: 'unset',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: 4,
              }}
            >
              <Tag className="cursor-pointer">{tag} ×</Tag>
            </button>
          ))}
        </div>
        {tags.length < MAX_TAGS && (
          <input
            type="text"
            placeholder="Add a tag..."
            value={tagInput}
            onChange={(e) => setTagInput(e.target.value)}
            onKeyDown={handleTagKeyDown}
            style={{
              width: '100%',
              height: 30,
              fontSize: 12,
              padding: '0 10px',
              border: '0.5px solid var(--color-border-tertiary)',
              borderRadius: 'var(--radius-md)',
              backgroundColor: 'var(--color-bg-primary)',
              fontFamily: 'inherit',
            }}
          />
        )}
        <p
          style={{ color: 'var(--color-text-tertiary)', margin: '8px 0 0', fontSize: 11 }}
        >
          {tags.length} of {MAX_TAGS} tags used
        </p>
      </div>

      <div style={{ borderTop: '0.5px solid var(--color-border-tertiary)', paddingTop: 20 }}>
        <p
          style={{ color: 'var(--color-text-tertiary)', margin: '0 0 4px', fontSize: 12, fontWeight: 500 }}
        >
          Visibility
        </p>
        <p style={{ fontSize: 13, margin: 0 }}>Public when published</p>
        <p
          style={{ color: 'var(--color-text-tertiary)', margin: '4px 0 0', fontSize: 12, lineHeight: 1.5 }}
        >
          Drafts are only visible to you.
        </p>
      </div>
    </aside>
  );
}

function StatRow({ label, value }: { label: string; value: string }) {
  return (
    <div style={{ marginBottom: 24 }}>
      <p
        style={{ color: 'var(--color-text-tertiary)', margin: '0 0 4px', fontSize: 12, fontWeight: 500 }}
      >
        {label}
      </p>
      <p style={{ fontSize: 18, fontWeight: 500, margin: 0 }}>{value}</p>
    </div>
  );
}
