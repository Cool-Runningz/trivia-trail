import { type GameHistoryProps } from '@/types';
import { HistoryEmptyState } from './HistoryEmptyState';
import { HistoryEntry } from './HistoryEntry';

export function GameHistory({ gameHistory }: GameHistoryProps) {
    if (!gameHistory || gameHistory.length === 0) {
        return <HistoryEmptyState />;
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold">Recent Games</h2>
                <span className="text-sm text-muted-foreground">
                    Last 7 days
                </span>
            </div>

            <div className="space-y-2">
                {gameHistory.map((entry) => (
                    <HistoryEntry key={entry.id} entry={entry} />
                ))}
            </div>
        </div>
    );
}
