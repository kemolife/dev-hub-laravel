import { cn } from '../../lib/utils';

interface AvatarProps {
  initials: string;
  bg?: string;
  color?: string;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const sizeClasses = {
  sm: 'w-6 h-6 text-[10px]',
  md: 'w-8 h-8 text-xs',
  lg: 'w-9 h-9 text-[13px]',
};

export function Avatar({ initials, bg, color, size = 'md', className }: AvatarProps) {
  return (
    <div
      className={cn(
        'rounded-full flex items-center justify-center font-medium shrink-0',
        sizeClasses[size],
        className,
      )}
      style={{
        backgroundColor: bg ?? 'var(--color-bg-info)',
        color: color ?? 'var(--color-text-info)',
      }}
    >
      {initials}
    </div>
  );
}
