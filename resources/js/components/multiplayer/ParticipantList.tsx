import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Crown } from 'lucide-react';
import { Participant } from '@/types';
import { cn } from '@/lib/utils';

interface ParticipantListProps {
    participants: Participant[];
    hostUserId: number;
}

export function ParticipantList({ participants, hostUserId }: ParticipantListProps) {
    const statusColors = {
        joined: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
        ready: 'bg-green-500/10 text-green-500 border-green-500/20',
        playing: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
        finished: 'bg-gray-500/10 text-gray-500 border-gray-500/20',
        disconnected: 'bg-red-500/10 text-red-500 border-red-500/20',
    };

    const statusLabels = {
        joined: 'Joined',
        ready: 'Ready',
        playing: 'Playing',
        finished: 'Finished',
        disconnected: 'Disconnected',
    };

    return (
        <div className="space-y-2">
            {participants.map((participant) => {
                const isHost = participant.user.id === hostUserId;
                const initials = participant.user.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .toUpperCase()
                    .slice(0, 2);

                return (
                    <Card key={participant.id}>
                        <CardContent className="flex items-center justify-between p-4">
                            <div className="flex items-center gap-3">
                                <Avatar>
                                    <AvatarFallback>{initials}</AvatarFallback>
                                </Avatar>
                                <div className="flex flex-col">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">{participant.user.name}</span>
                                        {isHost && (
                                            <Crown className="h-4 w-4 text-yellow-500" aria-label="Host" />
                                        )}
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        Score: {participant.score}
                                    </span>
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                className={cn(statusColors[participant.status])}
                            >
                                {statusLabels[participant.status]}
                            </Badge>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
