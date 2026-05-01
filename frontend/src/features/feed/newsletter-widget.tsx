import { Button } from '../../components/ui/button';

export function NewsletterWidget() {
  return (
    <div
      className="rounded-[var(--radius-lg)] px-4 py-3.5"
      style={{ backgroundColor: 'var(--color-bg-secondary)' }}
    >
      <p className="text-[13px] font-medium mb-1.5" style={{ margin: '0 0 6px' }}>
        Weekly digest, Mondays
      </p>
      <p
        className="text-xs leading-relaxed mb-2.5"
        style={{ color: 'var(--color-text-secondary)', margin: '0 0 10px' }}
      >
        One email, the deepest things published this week. No daily nags.
      </p>
      <Button className="text-xs h-7">Subscribe</Button>
    </div>
  );
}
