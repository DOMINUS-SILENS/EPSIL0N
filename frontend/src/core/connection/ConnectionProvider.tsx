import { useServerSentEvents, useOnlineStatus } from '@/core/realtime/sse';
import { useAuthStore } from '@/core/state/stores/authStore';

/**
 * ConnectionProvider initializes real-time connections when user is authenticated.
 * This component should be mounted near the root of the application.
 */
export function ConnectionProvider({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated } = useAuthStore();
  
  // Initialize SSE connection for real-time updates
  useServerSentEvents(isAuthenticated ? user?.id ?? null : null);
  
  // Initialize online/offline status monitoring
  useOnlineStatus();
  
  return <>{children}</>;
}


