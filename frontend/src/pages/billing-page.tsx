import { Link } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { useBilling } from '../features/billing/use-billing';
import { CurrentPlan } from '../features/billing/current-plan';
import { PlanOptions } from '../features/billing/plan-options';
import { ManageSection } from '../features/billing/manage-section';
import { InvoiceList } from '../features/billing/invoice-list';
import { CancelDialog } from '../features/billing/cancel-dialog';

export function BillingPage() {
  const { user, token, logout } = useAuth();
  const {
    billing,
    invoices,
    isLoadingBilling,
    isLoadingInvoices,
    actionError,
    checkoutLoading,
    isCancelLoading,
    isResumeLoading,
    showCancelConfirm,
    setShowCancelConfirm,
    handleCheckout,
    handleCancel,
    handleResume,
    handleDownloadInvoice,
  } = useBilling(token);

  const isPaid = billing?.plan !== 'free' && billing?.plan != null;

  return (
    <div
      style={{
        maxWidth: 1080,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <Link
            to="/"
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 18,
              fontWeight: 500,
              textDecoration: 'none',
              color: 'var(--color-text-primary)',
            }}
          >
            DevHub
          </Link>
        }
        right={
          user ? (
            <div className="flex items-center gap-3">
              <Link
                to="/settings/billing"
                style={{
                  fontSize: 13,
                  color: 'var(--color-text-secondary)',
                  textDecoration: 'none',
                  fontWeight: 500,
                }}
              >
                Billing
              </Link>
              <Link to={`/u/${user.username}`} style={{ display: 'flex' }}>
                <Avatar
                  initials={user.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .slice(0, 2)
                    .toUpperCase()}
                  size="md"
                />
              </Link>
              <button
                onClick={() => logout()}
                style={{
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: 13,
                  color: 'var(--color-text-secondary)',
                }}
              >
                Sign out
              </button>
            </div>
          ) : null
        }
      />

      <div className="p-6" style={{ maxWidth: 720 }}>
        <h1
          style={{
            fontFamily: 'var(--font-serif)',
            fontSize: 24,
            fontWeight: 500,
            margin: '0 0 24px',
          }}
        >
          Billing &amp; Subscription
        </h1>

        {actionError && (
          <div
            style={{
              padding: '10px 12px',
              borderRadius: 'var(--radius-md)',
              backgroundColor: '#fef2f2',
              border: '0.5px solid #fecaca',
              color: '#dc2626',
              fontSize: 13,
              marginBottom: 20,
            }}
          >
            {actionError}
          </div>
        )}

        <CurrentPlan billing={billing} isLoading={isLoadingBilling} />

        <PlanOptions
          billing={billing}
          checkoutLoading={checkoutLoading}
          onCheckout={(plan) => void handleCheckout(plan)}
        />

        {isPaid && billing && (
          <ManageSection
            billing={billing}
            isResumeLoading={isResumeLoading}
            onCancel={() => setShowCancelConfirm(true)}
            onResume={() => void handleResume()}
          />
        )}

        <InvoiceList
          invoices={invoices}
          isLoading={isLoadingInvoices}
          onDownload={(url, id) => void handleDownloadInvoice(url, id)}
        />
      </div>

      {showCancelConfirm && (
        <CancelDialog
          isLoading={isCancelLoading}
          onConfirm={() => void handleCancel()}
          onCancel={() => setShowCancelConfirm(false)}
        />
      )}
    </div>
  );
}
