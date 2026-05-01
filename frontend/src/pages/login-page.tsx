import { FormEvent, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { useAuth } from '../features/auth/auth-context';
import { ApiError } from '../lib/api';

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: Location })?.from?.pathname ?? '/';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setErrors({});
    setIsSubmitting(true);

    try {
      const result = await login(email, password);

      if (result.twoFactor) {
        navigate('/two-factor', { state: { challengeToken: result.challengeToken } });
      } else {
        navigate(from, { replace: true });
      }
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
          Sign in
        </h1>
        <p
          style={{
            fontSize: 14,
            color: 'var(--color-text-secondary)',
            margin: '0 0 24px',
          }}
        >
          Welcome back to DevHub
        </p>

        {errors._global && (
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
            {errors._global}
          </div>
        )}

        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          <Input
            id="email"
            label="Email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            error={errors.email}
            autoComplete="email"
            required
          />
          <Input
            id="password"
            label="Password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            error={errors.password}
            autoComplete="current-password"
            required
          />

          <Button type="submit" variant="primary" disabled={isSubmitting} className="w-full mt-1">
            {isSubmitting ? 'Signing in…' : 'Sign in'}
          </Button>
        </form>

        <p
          style={{
            fontSize: 13,
            color: 'var(--color-text-secondary)',
            textAlign: 'center',
            marginTop: 20,
          }}
        >
          No account?{' '}
          <Link
            to="/register"
            style={{ color: 'var(--color-text-primary)', textDecoration: 'underline' }}
          >
            Create one
          </Link>
        </p>
      </div>
    </div>
  );
}
