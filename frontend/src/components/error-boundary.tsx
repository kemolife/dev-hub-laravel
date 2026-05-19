import { Component, ErrorInfo, ReactNode } from 'react';
import { Link } from 'react-router';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error?: Error;
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false };

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('[ErrorBoundary]', error, info.componentStack);
  }

  render(): ReactNode {
    if (this.state.hasError) {
      if (this.props.fallback) return this.props.fallback;

      return (
        <div
          style={{
            maxWidth: 480,
            margin: '80px auto',
            padding: '32px 24px',
            textAlign: 'center',
            fontFamily: 'var(--font-sans)',
          }}
        >
          <p
            style={{
              fontSize: 15,
              color: 'var(--color-text-primary)',
              marginBottom: 8,
            }}
          >
            Something went wrong.
          </p>
          <p
            style={{
              fontSize: 13,
              color: 'var(--color-text-tertiary)',
              marginBottom: 24,
            }}
          >
            {this.state.error?.message}
          </p>
          <Link
            to="/"
            style={{ fontSize: 13, color: 'var(--color-text-primary)' }}
            onClick={() => this.setState({ hasError: false })}
          >
            ← Back to home
          </Link>
        </div>
      );
    }

    return this.props.children;
  }
}
