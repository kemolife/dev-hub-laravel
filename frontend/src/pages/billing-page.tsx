import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router';
import { Avatar } from '../components/ui/avatar';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { api, ApiError } from '../lib/api';
import type { BillingStatus, Invoice } from '../types';

function StatusBadge({ status }: { status: BillingStatus['status'] }) {
  if (!status) return null;

  const config: Record<
    NonNullable<BillingStatus['status']>,
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

function formatDate(iso: string | null): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

function planLabel(plan: BillingStatus['plan']): string {
  if (plan === 'pro') return 'Pro Monthly';
  if (plan === 'pro_annual') return 'Pro Annual';
  return 'Free';
}

interface ConfirmDialogProps {
  message: string;
  onConfirm: () => void;
  onCancel: () => void;
  isLoading: boolean;
}

function ConfirmDialog({ message, onConfirm, onCancel, isLoading }: ConfirmDialogProps) {
  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        backgroundColor: 'rgba(0,0,0,0.35)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 50,
      }}
    >
      <div
        style={{
          backgroundColor: 'var(--color-bg-primary)',
          borderRadius: 'var(--radius-lg)',
          border: '0.5px solid var(--color-border-tertiary)',
          padding: '28px 24px',
          width: '100%',
          maxWidth: 380,
        }}
      >
        <p
          style={{
            fontSize: 15,
            margin: '0 0 20px',
            color: 'var(--color-text-primary)',
          }}
        >
          {message}
        </p>
        <div className="flex gap-3 justify-end">
          <Button variant="default" onClick={onCancel} disabled={isLoading}>
            Keep subscription
          </Button>
          <Button
            variant="primary"
            onClick={onConfirm}
            disabled={isLoading}
            style={{
              backgroundColor: '#dc2626',
              borderColor: '#dc2626',
            }}
          >
            {isLoading ? 'Cancelling…' : 'Yes, cancel'}
          </Button>
        </div>
      </div>
    </div>
  );
}

export function BillingPage() {
  const { user, token, logout } = useAuth();

  const [billing, setBilling] = useState<BillingStatus | null>(null);
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [isLoadingBilling, setIsLoadingBilling] = useState(true);
  const [isLoadingInvoices, setIsLoadingInvoices] = useState(true);
  const [actionError, setActionError] = useState<string | null>(null);
  const [checkoutLoading, setCheckoutLoading] = useState<string | null>(null);
  const [isCancelLoading, setIsCancelLoading] = useState(false);
  const [isResumeLoading, setIsResumeLoading] = useState(false);
  const [showCancelConfirm, setShowCancelConfirm] = useState(false);

  const fetchBilling = useCallback(async () => {
    if (!token) return;
    try {
      const data = await api.get<BillingStatus>('/billing', token);
      setBilling(data);
    } finally {
      setIsLoadingBilling(false);
    }
  }, [token]);

  const fetchInvoices = useCallback(async () => {
    if (!token) return;
    try {
      const data = await api.get<Invoice[]>('/billing/invoices', token);
      setInvoices(data);
    } finally {
      setIsLoadingInvoices(false);
    }
  }, [token]);

  useEffect(() => {
    fetchBilling();
    fetchInvoices();
  }, [fetchBilling, fetchInvoices]);

  async function handleCheckout(plan: 'pro' | 'pro_annual') {
    if (!token) return;
    setActionError(null);
    setCheckoutLoading(plan);
    try {
      const { url } = await api.post<{ url: string }>('/billing/checkout', { plan }, token);
      window.location.href = url;
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Something went wrong.');
      setCheckoutLoading(null);
    }
  }

  async function handleCancel() {
    if (!token) return;
    setActionError(null);
    setIsCancelLoading(true);
    try {
      await api.post('/billing/cancel', {}, token);
      setShowCancelConfirm(false);
      await fetchBilling();
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Something went wrong.');
    } finally {
      setIsCancelLoading(false);
    }
  }

  async function handleResume() {
    if (!token) return;
    setActionError(null);
    setIsResumeLoading(true);
    try {
      await api.post('/billing/resume', {}, token);
      await fetchBilling();
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Something went wrong.');
    } finally {
      setIsResumeLoading(false);
    }
  }

  const isFree = billing?.plan === 'free' || !billing?.plan;
  const isPaid = !isFree;
  const isActive = billing?.status === 'active' || billing?.status === 'trialing';
  const isOnGracePeriod = billing?.status === 'on_grace_period';

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
          <>
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
          </>
        }
        right={
          <>
            {user && (
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
                <Avatar
                  initials={user.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .slice(0, 2)
                    .toUpperCase()}
                  size="md"
                />
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
            )}
          </>
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

        {/* Current plan */}
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

          {isLoadingBilling ? (
            <p style={{ fontSize: 14, color: 'var(--color-text-secondary)', margin: 0 }}>
              Loading…
            </p>
          ) : billing ? (
            <div className="flex flex-col gap-2">
              <div className="flex items-center gap-3">
                <span style={{ fontSize: 17, fontWeight: 500 }}>{planLabel(billing.plan)}</span>
                <StatusBadge status={billing.status} />
              </div>

              {billing.status === 'trialing' && billing.trial_ends_at && (
                <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
                  Trial ends {formatDate(billing.trial_ends_at)}
                </p>
              )}

              {billing.status === 'active' && billing.renews_at && (
                <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
                  Renews {formatDate(billing.renews_at)}
                </p>
              )}

              {billing.status === 'on_grace_period' && billing.ends_at && (
                <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
                  Access until {formatDate(billing.ends_at)} — subscription cancelled
                </p>
              )}

              {billing.status === 'cancelled' && (
                <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
                  Subscription cancelled
                </p>
              )}
            </div>
          ) : null}
        </section>

        {/* Plan options */}
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
            {isFree ? 'Upgrade your plan' : 'Available plans'}
          </h2>

          <div className="flex gap-4" style={{ flexWrap: 'wrap' }}>
            {/* Pro Monthly */}
            <div
              style={{
                flex: '1 1 220px',
                borderRadius: 'var(--radius-lg)',
                border: `0.5px solid ${billing?.plan === 'pro' ? 'var(--color-text-primary)' : 'var(--color-border-tertiary)'}`,
                padding: '18px 20px',
                backgroundColor:
                  billing?.plan === 'pro'
                    ? 'var(--color-bg-secondary)'
                    : 'var(--color-bg-primary)',
              }}
            >
              <div
                style={{
                  fontSize: 15,
                  fontWeight: 600,
                  marginBottom: 4,
                }}
              >
                Pro Monthly
              </div>
              <div
                style={{
                  fontSize: 22,
                  fontWeight: 700,
                  margin: '6px 0 8px',
                }}
              >
                $9
                <span
                  style={{ fontSize: 13, fontWeight: 400, color: 'var(--color-text-secondary)' }}
                >
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
                variant={billing?.plan === 'pro' ? 'default' : 'primary'}
                disabled={
                  billing?.plan === 'pro' ||
                  checkoutLoading !== null ||
                  billing?.status === 'on_grace_period'
                }
                onClick={() => handleCheckout('pro')}
                className="w-full"
              >
                {billing?.plan === 'pro'
                  ? 'Current plan'
                  : checkoutLoading === 'pro'
                    ? 'Redirecting…'
                    : 'Upgrade'}
              </Button>
            </div>

            {/* Pro Annual */}
            <div
              style={{
                flex: '1 1 220px',
                borderRadius: 'var(--radius-lg)',
                border: `0.5px solid ${billing?.plan === 'pro_annual' ? 'var(--color-text-primary)' : 'var(--color-border-secondary)'}`,
                padding: '18px 20px',
                backgroundColor:
                  billing?.plan === 'pro_annual'
                    ? 'var(--color-bg-secondary)'
                    : 'var(--color-bg-tertiary)',
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
              <div
                style={{
                  fontSize: 15,
                  fontWeight: 600,
                  marginBottom: 4,
                }}
              >
                Pro Annual
              </div>
              <div
                style={{
                  fontSize: 22,
                  fontWeight: 700,
                  margin: '6px 0 8px',
                }}
              >
                $90
                <span
                  style={{ fontSize: 13, fontWeight: 400, color: 'var(--color-text-secondary)' }}
                >
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
                variant={billing?.plan === 'pro_annual' ? 'default' : 'primary'}
                disabled={
                  billing?.plan === 'pro_annual' ||
                  checkoutLoading !== null ||
                  billing?.status === 'on_grace_period'
                }
                onClick={() => handleCheckout('pro_annual')}
                className="w-full"
              >
                {billing?.plan === 'pro_annual'
                  ? 'Current plan'
                  : checkoutLoading === 'pro_annual'
                    ? 'Redirecting…'
                    : 'Upgrade'}
              </Button>
            </div>
          </div>
        </section>

        {/* Actions */}
        {isPaid && (
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
                  onClick={() => setShowCancelConfirm(true)}
                  style={{ marginLeft: 16, flexShrink: 0, color: '#dc2626', borderColor: '#fecaca' }}
                >
                  Cancel subscription
                </Button>
              </div>
            )}

            {isOnGracePeriod && (
              <div className="flex items-center justify-between">
                <p style={{ fontSize: 13, color: 'var(--color-text-secondary)', margin: 0 }}>
                  Your subscription is cancelled but still active until the period ends. Resume to keep access.
                </p>
                <Button
                  variant="primary"
                  onClick={handleResume}
                  disabled={isResumeLoading}
                  style={{ marginLeft: 16, flexShrink: 0 }}
                >
                  {isResumeLoading ? 'Resuming…' : 'Resume subscription'}
                </Button>
              </div>
            )}
          </section>
        )}

        {/* Invoices */}
        <section
          style={{
            backgroundColor: 'var(--color-bg-primary)',
            borderRadius: 'var(--radius-lg)',
            border: '0.5px solid var(--color-border-tertiary)',
            padding: '20px 24px',
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
            Invoices
          </h2>

          {isLoadingInvoices ? (
            <p style={{ fontSize: 14, color: 'var(--color-text-secondary)', margin: 0 }}>
              Loading…
            </p>
          ) : invoices.length === 0 ? (
            <p style={{ fontSize: 14, color: 'var(--color-text-secondary)', margin: 0 }}>
              No invoices yet
            </p>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
              <thead>
                <tr
                  style={{
                    borderBottom: '0.5px solid var(--color-border-tertiary)',
                    color: 'var(--color-text-tertiary)',
                  }}
                >
                  <th style={{ textAlign: 'left', padding: '0 0 10px', fontWeight: 500 }}>Date</th>
                  <th style={{ textAlign: 'left', padding: '0 0 10px', fontWeight: 500 }}>
                    Amount
                  </th>
                  <th style={{ textAlign: 'left', padding: '0 0 10px', fontWeight: 500 }}>
                    Status
                  </th>
                  <th style={{ textAlign: 'right', padding: '0 0 10px', fontWeight: 500 }}>
                    Download
                  </th>
                </tr>
              </thead>
              <tbody>
                {invoices.map((invoice) => (
                  <tr
                    key={invoice.id}
                    style={{ borderBottom: '0.5px solid var(--color-border-tertiary)' }}
                  >
                    <td style={{ padding: '10px 0', color: 'var(--color-text-primary)' }}>
                      {formatDate(invoice.date)}
                    </td>
                    <td style={{ padding: '10px 0', color: 'var(--color-text-primary)' }}>
                      {invoice.amount}
                    </td>
                    <td style={{ padding: '10px 0' }}>
                      <span
                        style={{
                          display: 'inline-block',
                          padding: '1px 7px',
                          borderRadius: 'var(--radius-md)',
                          fontSize: 11,
                          fontWeight: 500,
                          backgroundColor: '#dcfce7',
                          color: '#166534',
                          textTransform: 'capitalize',
                        }}
                      >
                        {invoice.status}
                      </span>
                    </td>
                    <td style={{ padding: '10px 0', textAlign: 'right' }}>
                      <a
                        href={invoice.download_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        style={{
                          fontSize: 12,
                          color: 'var(--color-text-secondary)',
                          textDecoration: 'underline',
                        }}
                      >
                        Download
                      </a>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>
      </div>

      {showCancelConfirm && (
        <ConfirmDialog
          message="Are you sure you want to cancel? You'll keep access until the end of your billing period."
          onConfirm={handleCancel}
          onCancel={() => setShowCancelConfirm(false)}
          isLoading={isCancelLoading}
        />
      )}
    </div>
  );
}
