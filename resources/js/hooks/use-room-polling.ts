import { useState } from 'react';
import { usePoll } from '@inertiajs/react';

export type GamePhase = 'lobby' | 'active' | 'results' | 'completed';
export type ConnectionStatus = 'connected' | 'reconnecting' | 'disconnected';

interface UseRoomPollingReturn {
    connectionStatus: ConnectionStatus;
    lastUpdate: Date | null;
}

const POLLING_INTERVALS: Record<GamePhase, number> = {
    lobby: 3000,      // 3 seconds - moderate updates for participant changes
    active: 1000,     // 1 second - frequent updates for timer and status
    results: 2000,    // 2 seconds - moderate updates for results display
    completed: 5000,  // 5 seconds - infrequent updates for final state
};

/**
 * Hook for polling room state with dynamic intervals based on game phase
 * Uses Inertia's built-in usePoll hook for automatic polling
 */
export function useRoomPolling(
    gamePhase: GamePhase,
    enabled: boolean = true
): UseRoomPollingReturn {
    const [connectionStatus, setConnectionStatus] = useState<ConnectionStatus>('connected');
    const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

    // Use Inertia's usePoll hook with dynamic interval based on game phase
    // Pass a very large interval when disabled to effectively stop polling
    usePoll(
        enabled ? POLLING_INTERVALS[gamePhase] : 999999999,
        {
            // Only fetch specific props to minimize data transfer
            only: ['room', 'participants', 'isHost', 'canStart', 'currentQuestion', 'timeRemaining', 'roundResults'],
            
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
