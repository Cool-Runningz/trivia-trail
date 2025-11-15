import { Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Users, Clock, Trophy } from 'lucide-react';
import { GameRoom } from '@/types';
import { cn } from '@/lib/utils';
import multiplayer from '@/routes/multiplayer';

interface RoomBrowserProps {
    rooms: GameRoom[];
}

export function RoomBrowser({ rooms }: RoomBrowserProps) {
    if (rooms.length === 0) {
        return (
            <Card>
                <CardContent className="flex flex-col items-center justify-center py-12">
                    <Users className="h-12 w-12 text-muted-foreground mb-4" />
                    <p className="text-lg font-medium text-muted-foreground">No active rooms</p>
                    <p className="text-sm text-muted-foreground">Create a room to get started</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {rooms.map((room) => (
                <RoomCard key={room.id} room={room} />
            ))}
        </div>
    );
}

function RoomCard({ room }: { room: GameRoom }) {
    const difficultyColors = {
        easy: 'bg-green-500/10 text-green-500 border-green-500/20',
        medium: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
        hard: 'bg-red-500/10 text-red-500 border-red-500/20',
    };

    const statusColors = {
        waiting: 'bg-blue-500/10 text-blue-500 border-blue-500/20',
        starting: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
        active: 'bg-green-500/10 text-green-500 border-green-500/20',
        completed: 'bg-gray-500/10 text-gray-500 border-gray-500/20',
        cancelled: 'bg-red-500/10 text-red-500 border-red-500/20',
    };

    const isJoinable = room.status === 'waiting' && room.current_players < room.max_players;

    return (
        <Card className={cn(!isJoinable && 'opacity-60')}>
            <CardHeader>
                <div className="flex items-start justify-between">
                    <div className="space-y-1">
                        <CardTitle className="text-lg font-mono">{room.room_code}</CardTitle>
                        <CardDescription>
                            Hosted by {room.host?.name || 'Unknown'}
                        </CardDescription>
                    </div>
                    <Badge variant="outline" className={statusColors[room.status]}>
                        {room.status}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-2">
                    <div className="flex items-center gap-2 text-sm">
                        <Users className="h-4 w-4 text-muted-foreground" />
                        <span>
                            {room.current_players} / {room.max_players} players
                        </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm">
                        <Trophy className="h-4 w-4 text-muted-foreground" />
                        <span>{room.settings.total_questions} questions</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm">
                        <Clock className="h-4 w-4 text-muted-foreground" />
                        <span>{room.settings.time_per_question}s per question</span>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Badge variant="outline" className={difficultyColors[room.settings.difficulty]}>
                        {room.settings.difficulty}
                    </Badge>
                </div>

                {isJoinable ? (
                    <Button asChild className="w-full">
                        <Link href={multiplayer.room.show(room.room_code).url}>
                            Join Room
                        </Link>
                    </Button>
                ) : (
                    <Button disabled className="w-full">
                        {room.status === 'active' ? 'Game in Progress' : 'Room Full'}
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}
