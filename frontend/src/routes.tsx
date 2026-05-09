import { Navigate, useLocation } from 'react-router';
import { createBrowserRouter } from 'react-router';
import { useAuth } from './features/auth/auth-context';
import { BillingPage } from './pages/billing-page';
import { BookmarksPage } from './pages/bookmarks-page';
import { DraftsPage } from './pages/drafts-page';
import { EditorPage } from './pages/editor-page';
import { FeedPage } from './pages/feed-page';
import { HomePage } from './pages/home-page';
import { LoginPage } from './pages/login-page';
import { NotificationPreferencesPage } from './pages/notification-preferences-page';
import { NotificationsPage } from './pages/notifications-page';
import { PostDetailPage } from './pages/post-detail-page';
import { ProfilePage } from './pages/profile-page';
import { RegisterPage } from './pages/register-page';
import { SearchPage } from './pages/search-page';
import { TagDetailPage } from './pages/tag-detail-page';
import { TagsPage } from './pages/tags-page';
import { TwoFactorPage } from './pages/two-factor-page';

function RequireAuth({ children }: { children: React.ReactNode }) {
  const { token, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) return null;
  if (!token) return <Navigate to="/login" state={{ from: location }} replace />;

  return <>{children}</>;
}

export const router = createBrowserRouter([
  {
    path: '/',
    element: <HomePage />,
  },
  {
    path: '/posts/:slug',
    element: <PostDetailPage />,
  },
  {
    path: '/editor',
    element: (
      <RequireAuth>
        <EditorPage />
      </RequireAuth>
    ),
  },
  {
    path: '/drafts',
    element: (
      <RequireAuth>
        <DraftsPage />
      </RequireAuth>
    ),
  },
  {
    path: '/notifications',
    element: (
      <RequireAuth>
        <NotificationsPage />
      </RequireAuth>
    ),
  },
  {
    path: '/settings/notifications',
    element: (
      <RequireAuth>
        <NotificationPreferencesPage />
      </RequireAuth>
    ),
  },
  {
    path: '/u/:username',
    element: <ProfilePage />,
  },
  {
    path: '/feed',
    element: (
      <RequireAuth>
        <FeedPage />
      </RequireAuth>
    ),
  },
  {
    path: '/login',
    element: <LoginPage />,
  },
  {
    path: '/register',
    element: <RegisterPage />,
  },
  {
    path: '/two-factor',
    element: <TwoFactorPage />,
  },
  {
    path: '/search',
    element: <SearchPage />,
  },
  {
    path: '/tags',
    element: <TagsPage />,
  },
  {
    path: '/tags/:slug',
    element: <TagDetailPage />,
  },
  {
    path: '/bookmarks',
    element: (
      <RequireAuth>
        <BookmarksPage />
      </RequireAuth>
    ),
  },
  {
    path: '/settings',
    element: <Navigate to="/settings/billing" replace />,
  },
  {
    path: '/settings/billing',
    element: (
      <RequireAuth>
        <BillingPage />
      </RequireAuth>
    ),
  },
]);
