import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { type HistoryEntry as HistoryEntryType } from '@/types';
import { Link } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { ChevronRight } from 'lucide-react';
import { ParticipantAvatars } from './ParticipantAvatars';

interface HistoryEntryProps {
    entry: HistoryEntryType;
}

function getPositionLabel(position: number): string {
    if (position === 1) return '🥇 1st Place';
    if (position === 2) return '🥈 2nd Place';
    if (position === 3) return '🥉 3rd Place';
    return `#${position}`;
}

export function HistoryEntry({ entry }: HistoryEntryProps) {
    const completedDate = formatDistanceToNow(new Date(entry.completed_at), {
        addSuffix: true,
    });

    return (
        <Link
            href={route('multiplayer.game.results', { room: entry.room_code })}
            className="block"
        >
            <Card className="cursor-pointer transition-colors hover:bg-accent">
                <CardContent className="p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex-1 space-y-1">
                            <div className="flex items-center gap-2">
                                <Badge variant="outline">
                                    {getPositionLabel(entry.user_position)}
                                </Badge>
                                <span className="text-sm text-muted-foreground">
                                    {completedDate}
                                </span>
                            </div>

                            <div className="flex items-center gap-4">
                                <div className="text-sm">
                                    <span className="font-medium">
                                        {entry.user_score}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {' '}
                                        / {entry.total_questions * 100} pts
                                    </span>
                                </div>

                                <div className="text-sm text-muted-foreground">
                                    Room code: {entry.room_code}
                                </div>

                                <div className="text-sm text-muted-foreground">
                                    {entry.participant_count} players
                                </div>

                                <Badge variant="secondary" className="text-xs">
                                    {entry.difficulty}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <ParticipantAvatars
                                participants={entry.participants_preview}
                                total={entry.participant_count}
                            />
                            <ChevronRight className="h-4 w-4 text-muted-foreground" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}
