import { Button } from '../../components/ui/button';

const REACTIONS = ['Insightful', 'Mind-blown', 'Fire', 'Heart', 'Like'];

export function ReactionBar() {
  return (
    <div
      className="px-16 py-6"
      style={{
        backgroundColor: 'var(--color-bg-tertiary)',
        borderTop: '0.5px solid var(--color-border-tertiary)',
      }}
    >
      <div style={{ maxWidth: 580, margin: '0 auto' }}>
        <div className="flex items-center gap-2 mb-4">
          <p className="m-0 text-sm font-medium">Reactions</p>
          <p className="m-0 text-xs" style={{ color: 'var(--color-text-tertiary)' }}>
            visible after you finish reading
          </p>
        </div>
        <div className="flex gap-2 flex-wrap">
          {REACTIONS.map((reaction) => (
            <Button key={reaction}>{reaction}</Button>
          ))}
        </div>
      </div>
    </div>
  );
}
