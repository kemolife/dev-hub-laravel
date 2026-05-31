import type { BillingStatus } from '../../types';

function formatDate(iso: string | null): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

function StatusBadge({ status }: { status: BillingStatus['subscription_status'] }) {
  if (!status) return null;

  const config: Record<
    NonNullable<BillingStatus['subscription_status']>,
    { label: string; bg: string; color: string }
  > = {
    active: { label: 'Active', bg: '#dcfce7', color: '#166534' },
    trialing: { label: 'Trialing', bg: '#dbeafe', color: '#1e40af' },
    cancelled: { label: 'Cancelled', bg: '#fee2e2', color: '#991b1b' },
    on_grace_period: { label: 'Grace period', bg: '#fef9c3', color: '#854d0e' },
  };

  const { label, bg, color } = config[status];

  return (
    <span
      style={{
        display: 'inline-block',
        padding: '2px 8px',
        borderRadius: 'var(--radius-md)',
        fontSize: 12,
        fontWeight: 500,
        backgroundColor: bg,
        color,
      }}
    >
      {label}
    </span>
  );
}

interface CurrentPlanProps {
  billing: BillingStatus | null;
  isLoading: boolean;
}

export function CurrentPlan({ billing, isLoading }: CurrentPlanProps) {
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
        Current plan
      </h2>

      {isLoading ? (
        <p style={{ fontSize: 14, color: 'var(--color-text-secondary)', margin: 0 }}>Loading…</p>
      ) : billing ? (
        <div className="flex flex-col gap-2">
          <div className="flex items-center gap-3">
            <span style={{ fontSize: 17, fontWeight: 500 }}>{billing.plan_name}</span>
            <StatusBadge status={billing.subscription_status} />
          </div>

          {billing.subscription_status === 'trialing' && billing.trial_ends_at && (
            <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
              Trial ends {formatDate(billing.trial_ends_at)}
            </p>
          )}

          {billing.subscription_status === 'active' && billing.renews_at && (
            <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
              Renews {formatDate(billing.renews_at)}
            </p>
          )}

          {billing.subscription_status === 'on_grace_period' && billing.ends_at && (
            <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
              Access until {formatDate(billing.ends_at)} — subscription cancelled
            </p>
          )}

          {billing.subscription_status === 'cancelled' && (
            <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
              Subscription cancelled
            </p>
          )}
        </div>
      ) : null}
    </section>
  );
}
