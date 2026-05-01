import { FormEvent, useState } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { useAuth } from '../features/auth/auth-context';
import { ApiError } from '../lib/api';

export function TwoFactorPage() {
  const { twoFactorChallenge } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const state = location.state as { challengeToken?: string } | null;

  const [code, setCode] = useState('');
  const [useRecovery, setUseRecovery] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!state?.challengeToken) {
    return <Navigate to="/login" replace />;
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setErrors({});
    setIsSubmitting(true);

    try {
      await twoFactorChallenge(state!.challengeToken!, code);
      navigate('/', { replace: true });
    } catch (err) {
      if (err instanceof ApiError) {
        const fieldErrors: Record<string, string> = {};
        for (const [field, messages] of Object.entries(err.errors)) {
          fieldErrors[field] = messages[0];
        }
        if (Object.keys(fieldErrors).length === 0) {
          fieldErrors._global = err.message;
        }
        setErrors(fieldErrors);
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'var(--color-bg-tertiary)',
      }}
    >
      <div
        style={{
          width: '100%',
          maxWidth: 400,
          backgroundColor: 'var(--color-bg-primary)',
          borderRadius: 'var(--radius-xl)',
          border: '0.5px solid var(--color-border-tertiary)',
          padding: '32px 28px',
        }}
      >
        <h1
          style={{
            fontFamily: 'var(--font-serif)',
            fontSize: 22,
            fontWeight: 500,
            margin: '0 0 4px',
          }}
        >
          Two-factor authentication
        </h1>
        <p
          style={{
            fontSize: 14,
            color: 'var(--color-text-secondary)',
            margin: '0 0 24px',
          }}
        >
          {useRecovery
            ? 'Enter one of your recovery codes'
            : 'Enter the 6-digit code from your authenticator app'}
        </p>

        {(errors._global ?? errors.code ?? errors.challenge_token) && (
          <div
            style={{
              padding: '10px 12px',
              borderRadius: 'var(--radius-md)',
              backgroundColor: '#fef2f2',
              border: '0.5px solid #fecaca',
              color: '#dc2626',
              fontSize: 13,
              marginBottom: 16,
            }}
          >
            {errors._global ?? errors.code ?? errors.challenge_token}
          </div>
        )}

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <Input
            id="code"
            label={useRecovery ? 'Recovery code' : 'Authentication code'}
            type={useRecovery ? 'text' : 'text'}
            value={code}
            onChange={(e) => setCode(e.target.value)}
            placeholder={useRecovery ? 'xxxxxxxx-xxxx-…' : '000000'}
            autoComplete="one-time-code"
            inputMode={useRecovery ? undefined : 'numeric'}
            maxLength={useRecovery ? undefined : 6}
            required
          />

          <Button type="submit" variant="primary" disabled={isSubmitting} className="w-full">
            {isSubmitting ? 'Verifying…' : 'Verify'}
          </Button>
        </form>

        <button
          onClick={() => {
            setUseRecovery((v) => !v);
            setCode('');
            setErrors({});
          }}
          style={{
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            fontSize: 13,
            color: 'var(--color-text-secondary)',
            textDecoration: 'underline',
            marginTop: 16,
            display: 'block',
            width: '100%',
            textAlign: 'center',
          }}
        >
          {useRecovery ? 'Use authenticator app instead' : 'Use a recovery code instead'}
        </button>
      </div>
    </div>
  );
}
