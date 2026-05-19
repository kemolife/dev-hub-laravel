import { Button } from '../../components/ui/button';
import type { BillingStatus } from '../../types';

interface ManageSectionProps {
  billing: BillingStatus;
  isResumeLoading: boolean;
  onCancel: () => void;
  onResume: () => void;
}

export function ManageSection({ billing, isResumeLoading, onCancel, onResume }: ManageSectionProps) {
  const isActive =
    billing.subscription_status === 'active' || billing.subscription_status === 'trialing';
  const isOnGracePeriod = billing.subscription_status === 'on_grace_period';

  if (!isActive && !isOnGracePeriod) return null;

  return (
    <section
      style={{
        backgroundColor: 'var(--color-bg-primary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        padding: '20px 24px',
        marginBottom: 16,
      }}
    >
      <h2
        style={{
          fontSize: 13,
          fontWeight: 600,
          textTransform: 'uppercase',
          letterSpacing: '0.06em',
          color: 'var(--color-text-tertiary)',
          margin: '0 0 14px',
        }}
      >
        Manage
      </h2>

      {isActive && (
        <div className="flex items-center justify-between">
          <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
            Cancel your subscription at the end of the current billing period.
          </p>
          <Button
            variant="default"
            onClick={onCancel}
            style={{ marginLeft: 16, flexShrink: 0, color: '#dc2626', borderColor: '#fecaca' }}
          >
            Cancel subscription
          </Button>
        </div>
      )}

      {isOnGracePeriod && (
        <div className="flex items-center justify-between">
          <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
            Your subscription is cancelled but still active until the period ends. Resume to keep
            access.
          </p>
          <Button
            variant="primary"
            onClick={onResume}
            disabled={isResumeLoading}
            style={{ marginLeft: 16, flexShrink: 0 }}
          >
            {isResumeLoading ? 'Resuming…' : 'Resume subscription'}
          </Button>
        </div>
      )}
    </section>
  );
}
