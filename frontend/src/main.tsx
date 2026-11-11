import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { ApiError } from '@/api/errors';
import { ConfirmProvider } from '@/components/ui';
import { router } from '@/app/router';
import './index.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      refetchOnWindowFocus: false,
      // Don't retry client errors (404, 403, 422…).
      retry: (count, error) => !(error instanceof ApiError && error.status >= 400 && error.status < 500) && count < 2,
    },
  },
});

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <ConfirmProvider>
        <RouterProvider router={router} />
      </ConfirmProvider>
      <Toaster
        position="bottom-right"
        gutter={10}
        toastOptions={{
          duration: 3500,
          className: '!rounded-xl !bg-ink !px-4 !py-3 !text-sm !text-white !shadow-pop',
          success: { iconTheme: { primary: 'oklch(0.7 0.15 160)', secondary: '#fff' } },
          error: { iconTheme: { primary: 'oklch(0.68 0.2 25)', secondary: '#fff' } },
        }}
      />
    </QueryClientProvider>
  </StrictMode>,
);
