import { Button } from '../../components/ui/button';

interface CancelDialogProps {
  isLoading: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

export function CancelDialog({ isLoading, onConfirm, onCancel }: CancelDialogProps) {
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
        <p style={{ fontSize: 15, margin: '0 0 20px', color: 'var(--color-text-primary)' }}>
          Are you sure you want to cancel? You'll keep access until the end of your billing period.
        </p>
        <div className="flex gap-3 justify-end">
          <Button variant="default" onClick={onCancel} disabled={isLoading}>
            Keep subscription
          </Button>
          <Button
            variant="primary"
            onClick={onConfirm}
            disabled={isLoading}
            style={{ backgroundColor: '#dc2626', borderColor: '#dc2626' }}
          >
            {isLoading ? 'Cancelling…' : 'Yes, cancel'}
          </Button>
        </div>
      </div>
    </div>
  );
}
