import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { useAuth } from '../auth/auth-context';
import { api } from '../../lib/api';

interface NotificationsMeta {
  total: number;
  unread_count: number;
}

const POLL_INTERVAL_MS = 60_000;

export function NotificationBell() {
  const { token, user } = useAuth();
  const navigate = useNavigate();
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    if (!token || !user) return;

    let cancelled = false;

    async function fetchUnreadCount() {
      try {
        const response = await api.get<{ data: unknown[]; meta: NotificationsMeta }>(
          '/notifications?page=1',
          token ?? undefined,
        );
        if (!cancelled) {
          setUnreadCount(response.meta.unread_count);
        }
      } catch {
        // silently ignore polling errors
      }
    }

    fetchUnreadCount();
    const interval = setInterval(fetchUnreadCount, POLL_INTERVAL_MS);

    return () => {
      cancelled = true;
      clearInterval(interval);
    };
  }, [token, user]);

  if (!user) return null;

  return (
    <button
      onClick={() => navigate('/notifications')}
      aria-label={unreadCount > 0 ? `${unreadCount} unread notifications` : 'Notifications'}
      style={{
        position: 'relative',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: 32,
        height: 32,
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        borderRadius: 'var(--radius-md)',
        color: 'var(--color-text-secondary)',
        transition: 'background-color 0.15s',
      }}
      onMouseEnter={(e) => {
        (e.currentTarget as HTMLButtonElement).style.backgroundColor =
          'var(--color-bg-secondary)';
      }}
      onMouseLeave={(e) => {
        (e.currentTarget as HTMLButtonElement).style.backgroundColor = 'transparent';
      }}
    >
      <svg
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.75"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
      >
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
      </svg>

      {unreadCount > 0 && (
        <span
          style={{
            position: 'absolute',
            top: 3,
            right: 3,
            minWidth: 16,
            height: 16,
            borderRadius: 8,
            backgroundColor: '#e53e3e',
            color: '#ffffff',
            fontSize: 10,
            fontWeight: 600,
            lineHeight: '16px',
            textAlign: 'center',
            padding: '0 3px',
          }}
        >
          {unreadCount > 99 ? '99+' : unreadCount}
        </span>
      )}
    </button>
  );
}
