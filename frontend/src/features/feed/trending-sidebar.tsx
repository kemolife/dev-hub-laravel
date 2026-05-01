import type { ApiTag } from '../../types';

interface TrendingSidebarProps {
  tags: ApiTag[];
}

export function TrendingSidebar({ tags }: TrendingSidebarProps) {
  return (
    <div
      className="rounded-[var(--radius-lg)] p-4 md:p-5"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        border: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <p
        className="text-[13px] font-medium mb-2.5"
        style={{ color: 'var(--color-text-tertiary)', margin: '0 0 10px' }}
      >
        Quietly trending
      </p>
      <div className="flex flex-col gap-1.5">
        {tags.map((tag) => (
          <span key={tag.id} className="text-[13px]">
            {tag.name}
          </span>
        ))}
      </div>
    </div>
  );
}
