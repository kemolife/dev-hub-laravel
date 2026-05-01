import { createBrowserRouter } from 'react-router';
import { EditorPage } from './pages/editor-page';
import { HomePage } from './pages/home-page';
import { PostDetailPage } from './pages/post-detail-page';

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
    element: <EditorPage />,
  },
]);
