import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useQueryClient } from '@tanstack/react-query';
import { useMe } from '@/api/auth';
import { loginUrl } from '@/features/auth/guards';

/**
 * Validates the persisted session on boot, reacts to a failed token refresh
 * and scrolls to top on navigation.
 */
export function SessionWatcher() {
  useMe();
  const navigate = useNavigate();
  const location = useLocation();
  const qc = useQueryClient();

  useEffect(() => {
    const onExpired = () => {
      qc.removeQueries({ queryKey: ['account'] });
      qc.removeQueries({ queryKey: ['owner'] });
      toast.error('Your session expired. Please sign in again.', { id: 'session-expired' });
      const path = window.location.pathname + window.location.search;
      if (path.startsWith('/account') || path.startsWith('/owner') || path.startsWith('/checkout')) navigate(loginUrl(path));
    };
    window.addEventListener('auth:expired', onExpired);
    return () => window.removeEventListener('auth:expired', onExpired);
  }, [navigate, qc]);

  useEffect(() => {
    window.scrollTo({ top: 0 });
  }, [location.pathname]);

  return null;
}
