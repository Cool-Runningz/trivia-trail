import { useState } from 'react';
import { usePoll } from '@inertiajs/react';

export type ConnectionStatus = 'connected' | 'reconnecting' | 'disconnected';

interface UseGamePollingReturn {
    connectionStatus: ConnectionStatus;
    lastUpdate: Date | null;
}

const DEFAULT_INTERVAL = 1000; // 1 second for active games

/**
 * Hook for polling multiplayer game state during active gameplay
 * Uses Inertia's built-in usePoll hook for automatic polling
 */
export function useGamePolling(
    interval: number = DEFAULT_INTERVAL,
    enabled: boolean = true
): UseGamePollingReturn {
    const [connectionStatus, setConnectionStatus] = useState<ConnectionStatus>('connected');
    const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

    // Use Inertia's usePoll hook
    // Pass a very large interval when disabled to effectively stop polling
    usePoll(
        enabled ? interval : 999999999,
        {
            // Only fetch specific props to minimize data transfer
            only: [
                'room',
                'participants', 
                'currentQuestion', 
                'timeRemaining', 
                'roundResults',
                'finalStandings',
                'gameState'
            ],
            
            // Callbacks
            onSuccess: () => {
                if (enabled) {
                    setConnectionStatus('connected');
                    setLastUpdate(new Date());
                }
            },
            
            onError: () => {
                if (enabled) {
                    setConnectionStatus('reconnecting');
                }
            },
        }
    );

    return {
        connectionStatus,
        lastUpdate,
    };
}
