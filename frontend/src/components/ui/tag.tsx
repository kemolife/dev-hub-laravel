import { cn } from '../../lib/utils';

interface TagProps {
  children: React.ReactNode;
  className?: string;
}

export function Tag({ children, className }: TagProps) {
  return (
    <span
      className={cn(
        'inline-block text-xs px-2.5 py-0.5 rounded-[var(--radius-md)]',
        'bg-[var(--color-bg-secondary)] text-[var(--color-text-secondary)]',
        className,
      )}
    >
      {children}
    </span>
  );
}
