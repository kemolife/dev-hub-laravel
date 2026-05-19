import { Button } from '../../components/ui/button';
import type { BillingStatus } from '../../types';

interface PlanOptionsProps {
  billing: BillingStatus | null;
  checkoutLoading: string | null;
  onCheckout: (plan: 'pro' | 'pro_annual') => void;
}

export function PlanOptions({ billing, checkoutLoading, onCheckout }: PlanOptionsProps) {
  if (billing?.plan === 'pro_annual') return null;

  const isOnGracePeriod = billing?.subscription_status === 'on_grace_period';

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
        {billing?.plan === 'free' ? 'Upgrade your plan' : 'Upgrade to annual'}
      </h2>

      <div className="flex gap-4" style={{ flexWrap: 'wrap' }}>
        {billing?.plan !== 'pro' && (
          <div
            style={{
              flex: '1 1 220px',
              borderRadius: 'var(--radius-lg)',
              border: '0.5px solid var(--color-border-tertiary)',
              padding: '18px 20px',
              backgroundColor: 'var(--color-bg-primary)',
            }}
          >
            <div style={{ fontSize: 15, fontWeight: 600, marginBottom: 4 }}>Pro Monthly</div>
            <div style={{ fontSize: 22, fontWeight: 700, margin: '6px 0 8px' }}>
              $9
              <span style={{ fontSize: 13, fontWeight: 400, color: 'var(--color-text-secondary)' }}>
                /mo
              </span>
            </div>
            <p
              style={{
                fontSize: 13,
                color: 'var(--color-text-secondary)',
                margin: '0 0 16px',
                lineHeight: 1.5,
              }}
            >
              Up to 50 posts/month, API access, no ads
            </p>
            <Button
              variant="primary"
              disabled={checkoutLoading !== null || isOnGracePeriod}
              onClick={() => onCheckout('pro')}
              className="w-full"
            >
              {checkoutLoading === 'pro' ? 'Redirecting…' : 'Upgrade'}
            </Button>
          </div>
        )}

        <div
          style={{
            flex: '1 1 220px',
            borderRadius: 'var(--radius-lg)',
            border: '0.5px solid var(--color-border-secondary)',
            padding: '18px 20px',
            backgroundColor: 'var(--color-bg-tertiary)',
            position: 'relative',
          }}
        >
          <div
            style={{
              position: 'absolute',
              top: -10,
              right: 14,
              fontSize: 11,
              fontWeight: 600,
              backgroundColor: 'var(--color-text-primary)',
              color: 'var(--color-bg-primary)',
              borderRadius: 'var(--radius-md)',
              padding: '2px 8px',
              letterSpacing: '0.04em',
            }}
          >
            Best value
          </div>
          <div style={{ fontSize: 15, fontWeight: 600, marginBottom: 4 }}>Pro Annual</div>
          <div style={{ fontSize: 22, fontWeight: 700, margin: '6px 0 8px' }}>
            $90
            <span style={{ fontSize: 13, fontWeight: 400, color: 'var(--color-text-secondary)' }}>
              /yr
            </span>
          </div>
          <p
            style={{
              fontSize: 13,
              color: 'var(--color-text-secondary)',
              margin: '0 0 16px',
              lineHeight: 1.5,
            }}
          >
            Save 16%, same features
          </p>
          <Button
            variant="primary"
            disabled={checkoutLoading !== null || isOnGracePeriod}
            onClick={() => onCheckout('pro_annual')}
            className="w-full"
          >
            {checkoutLoading === 'pro_annual' ? 'Redirecting…' : 'Upgrade'}
          </Button>
        </div>
      </div>
    </section>
  );
}
