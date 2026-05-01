import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { Button } from '../components/ui/button';
import { Topbar } from '../components/layout/topbar';
import { useAuth } from '../features/auth/auth-context';
import { api } from '../lib/api';
import { relativeTime } from '../lib/utils';
import type { ApiNotification } from '../types';

interface NotificationsResponse {
  data: ApiNotification[];
  meta: {
    total: number;
    unread_count: number;
    current_page?: number;
    last_page?: number;
  };
}

export function NotificationsPage() {
  const { token } = useAuth();
  const navigate = useNavigate();

  const [notifications, setNotifications] = useState<ApiNotification[]>([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [isMarkingAll, setIsMarkingAll] = useState(false);

  const fetchNotifications = useCallback(
    async (page: number, append: boolean) => {
      try {
        const response = await api.get<NotificationsResponse>(
          `/notifications?page=${page}`,
          token ?? undefined,
        );
        setNotifications((prev) =>
          append ? [...prev, ...response.data] : response.data,
        );
        const lastPage = response.meta.last_page ?? 1;
        setHasMore(page < lastPage);
        setCurrentPage(page);
      } finally {
        setIsLoading(false);
        setIsLoadingMore(false);
      }
    },
    [token],
  );

  useEffect(() => {
    setIsLoading(true);
    fetchNotifications(1, false);
  }, [fetchNotifications]);

  async function handleMarkRead(notification: ApiNotification) {
    if (!notification.read_at) {
      setNotifications((prev) =>
        prev.map((n) =>
          n.id === notification.id ? { ...n, read_at: new Date().toISOString() } : n,
        ),
      );
      await api.post(`/notifications/${notification.id}/read`, {}, token ?? undefined);
    }
    if (notification.data.url) {
      navigate(notification.data.url);
    }
  }

  async function handleDelete(id: string, e: React.MouseEvent) {
    e.stopPropagation();
    setNotifications((prev) => prev.filter((n) => n.id !== id));
    await api.delete(`/notifications/${id}`, token ?? undefined);
  }

  async function handleMarkAllRead() {
    setIsMarkingAll(true);
    try {
      await api.post('/notifications/read-all', {}, token ?? undefined);
      setNotifications((prev) =>
        prev.map((n) => ({ ...n, read_at: n.read_at ?? new Date().toISOString() })),
      );
    } finally {
      setIsMarkingAll(false);
    }
  }

  async function handleLoadMore() {
    setIsLoadingMore(true);
    await fetchNotifications(currentPage + 1, true);
  }

  const hasUnread = notifications.some((n) => n.read_at === null);

  return (
    <div
      style={{
        maxWidth: 680,
        margin: '0 auto',
        backgroundColor: 'var(--color-bg-tertiary)',
        borderRadius: 'var(--radius-lg)',
        border: '0.5px solid var(--color-border-tertiary)',
        overflow: 'hidden',
      }}
    >
      <Topbar
        left={
          <span
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 18,
              fontWeight: 500,
            }}
          >
            DevHub
          </span>
        }
        right={null}
      />

      <div style={{ padding: '24px 28px' }}>
        <div className="flex items-center justify-between mb-5">
          <h1
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 22,
              fontWeight: 500,
              margin: 0,
            }}
          >
            Notifications
          </h1>
          {hasUnread && (
            <Button
              variant="default"
              onClick={handleMarkAllRead}
              disabled={isMarkingAll}
            >
              {isMarkingAll ? 'Marking…' : 'Mark all read'}
            </Button>
          )}
        </div>

        {isLoading ? (
          <div
            style={{
              textAlign: 'center',
              padding: '48px 0',
              color: 'var(--color-text-tertiary)',
              fontSize: 14,
            }}
          >
            Loading…
          </div>
        ) : notifications.length === 0 ? (
          <div
            style={{
              textAlign: 'center',
              padding: '64px 0',
              color: 'var(--color-text-tertiary)',
            }}
          >
            <div style={{ fontSize: 36, marginBottom: 12 }}>🎉</div>
            <p style={{ margin: 0, fontSize: 15 }}>You're all caught up</p>
          </div>
        ) : (
          <div
            style={{
              backgroundColor: 'var(--color-bg-primary)',
              borderRadius: 'var(--radius-md)',
              border: '0.5px solid var(--color-border-tertiary)',
              overflow: 'hidden',
            }}
          >
            {notifications.map((notification, index) => (
              <NotificationRow
                key={notification.id}
                notification={notification}
                isLast={index === notifications.length - 1}
                onRead={handleMarkRead}
                onDelete={handleDelete}
              />
            ))}
          </div>
        )}

        {hasMore && !isLoading && (
          <div style={{ textAlign: 'center', marginTop: 16 }}>
            <Button
              variant="default"
              onClick={handleLoadMore}
              disabled={isLoadingMore}
            >
              {isLoadingMore ? 'Loading…' : 'Load more'}
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}

interface NotificationRowProps {
  notification: ApiNotification;
  isLast: boolean;
  onRead: (notification: ApiNotification) => void;
  onDelete: (id: string, e: React.MouseEvent) => void;
}

function NotificationRow({ notification, isLast, onRead, onDelete }: NotificationRowProps) {
  const [isHovered, setIsHovered] = useState(false);
  const isUnread = notification.read_at === null;
  const isClickable = Boolean(notification.data.url) || isUnread;

  return (
    <div
      onClick={() => onRead(notification)}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      style={{
        display: 'flex',
        alignItems: 'flex-start',
        gap: 12,
        padding: '14px 16px',
        borderBottom: isLast ? 'none' : '0.5px solid var(--color-border-tertiary)',
        cursor: isClickable ? 'pointer' : 'default',
        backgroundColor: isHovered && isClickable
          ? 'var(--color-bg-secondary)'
          : 'transparent',
        transition: 'background-color 0.1s',
        position: 'relative',
      }}
    >
      <div
        style={{
          width: 8,
          height: 8,
          borderRadius: '50%',
          backgroundColor: isUnread ? '#3b82f6' : 'var(--color-bg-tertiary)',
          border: isUnread ? 'none' : '1.5px solid var(--color-border-secondary)',
          flexShrink: 0,
          marginTop: 6,
        }}
      />

      <div style={{ flex: 1, minWidth: 0 }}>
        <p
          style={{
            margin: '0 0 3px',
            fontSize: 14,
            color: isUnread
              ? 'var(--color-text-primary)'
              : 'var(--color-text-secondary)',
            fontWeight: isUnread ? 500 : 400,
            lineHeight: 1.5,
          }}
        >
          {notification.data.message}
        </p>
        <span
          style={{
            fontSize: 12,
            color: 'var(--color-text-tertiary)',
          }}
        >
          {relativeTime(notification.created_at)}
        </span>
      </div>

      {isHovered && (
        <button
          onClick={(e) => onDelete(notification.id, e)}
          aria-label="Delete notification"
          style={{
            flexShrink: 0,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            width: 24,
            height: 24,
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            borderRadius: 4,
            color: 'var(--color-text-tertiary)',
            fontSize: 16,
            lineHeight: 1,
          }}
        >
          ×
        </button>
      )}
    </div>
  );
}
