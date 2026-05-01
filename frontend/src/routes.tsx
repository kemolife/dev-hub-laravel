import { Navigate, useLocation } from 'react-router';
import { createBrowserRouter } from 'react-router';
import { useAuth } from './features/auth/auth-context';
import { EditorPage } from './pages/editor-page';
import { FeedPage } from './pages/feed-page';
import { HomePage } from './pages/home-page';
import { LoginPage } from './pages/login-page';
import { PostDetailPage } from './pages/post-detail-page';
import { ProfilePage } from './pages/profile-page';
import { RegisterPage } from './pages/register-page';
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
    path: '/posts/:id',
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
]);
