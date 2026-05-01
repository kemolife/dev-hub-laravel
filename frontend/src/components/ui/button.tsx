import { cn } from '../../lib/utils';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'primary';
}

export function Button({ variant = 'default', className, children, ...props }: ButtonProps) {
  return (
    <button
      className={cn(
        'h-8 px-3.5 text-sm rounded-[var(--radius-md)] border cursor-pointer transition-colors',
        variant === 'default' && [
          'border-[var(--color-border-tertiary)] bg-transparent text-[var(--color-text-primary)]',
          'hover:bg-[var(--color-bg-secondary)]',
        ],
        variant === 'primary' && [
          'border-[var(--color-text-primary)] bg-[var(--color-text-primary)] text-[var(--color-bg-primary)]',
          'hover:opacity-90',
        ],
        className,
      )}
      {...props}
    >
      {children}
    </button>
  );
}
