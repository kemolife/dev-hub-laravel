interface TopbarProps {
  left: React.ReactNode;
  right: React.ReactNode;
}

export function Topbar({ left, right }: TopbarProps) {
  return (
    <header
      className="flex items-center justify-between px-6 py-3.5"
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        borderBottom: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div className="flex items-center gap-7">{left}</div>
      <div className="flex items-center gap-3">{right}</div>
    </header>
  );
}
