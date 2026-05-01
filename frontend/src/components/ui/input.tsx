interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
}

export function Input({ label, error, id, className, ...props }: InputProps) {
  return (
    <div className="flex flex-col gap-1.5">
      {label && (
        <label
          htmlFor={id}
          className="text-sm font-medium"
          style={{ color: 'var(--color-text-primary)' }}
        >
          {label}
        </label>
      )}
      <input
        id={id}
        className={className}
        style={{
          height: 36,
          padding: '0 12px',
          fontSize: 14,
          borderRadius: 'var(--radius-md)',
          border: `0.5px solid ${error ? '#dc2626' : 'var(--color-border-secondary)'}`,
          backgroundColor: 'var(--color-bg-primary)',
          color: 'var(--color-text-primary)',
          outline: 'none',
          width: '100%',
          boxSizing: 'border-box',
        }}
        {...props}
      />
      {error && (
        <span className="text-xs" style={{ color: '#dc2626' }}>
          {error}
        </span>
      )}
    </div>
  );
}
