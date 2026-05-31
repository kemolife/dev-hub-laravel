import type { Invoice } from '../../types';

function formatDate(iso: string | null): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

interface InvoiceListProps {
  invoices: Invoice[];
  isLoading: boolean;
  onDownload: (downloadUrl: string, invoiceId: string) => void;
}

export function InvoiceList({ invoices, isLoading, onDownload }: InvoiceListProps) {
  return (
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

      {isLoading ? (
        <p style={{ fontSize: 14, color: 'var(--color-text-secondary)', margin: 0 }}>Loading…</p>
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
              <th style={{ textAlign: 'left', padding: '0 0 10px', fontWeight: 500 }}>Amount</th>
              <th style={{ textAlign: 'left', padding: '0 0 10px', fontWeight: 500 }}>Status</th>
              <th style={{ textAlign: 'right', padding: '0 0 10px', fontWeight: 500 }}>Download</th>
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
                  <button
                    onClick={() => onDownload(invoice.download_url, invoice.id)}
                    style={{
                      background: 'none',
                      border: 'none',
                      cursor: 'pointer',
                      fontSize: 12,
                      color: 'var(--color-text-secondary)',
                      textDecoration: 'underline',
                      padding: 0,
                    }}
                  >
                    Download
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </section>
  );
}
