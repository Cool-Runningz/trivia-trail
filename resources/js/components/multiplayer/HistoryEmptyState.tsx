import { Card, CardContent } from '@/components/ui/card';
import { History } from 'lucide-react';

export function HistoryEmptyState() {
    return (
        <Card className="border-dashed">
            <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                <History className="mb-4 h-12 w-12 text-muted-foreground" />
                <h3 className="mb-2 font-semibold">No Recent Games</h3>
                <p className="max-w-sm text-sm text-muted-foreground">
                    Your completed multiplayer games from the last 7 days will
                    appear here. Create or join a room to start playing!
                </p>
            </CardContent>
        </Card>
    );
}
