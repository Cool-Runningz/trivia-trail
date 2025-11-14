import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Clock, Trophy, Users, Target } from 'lucide-react';
import { RoomSettings } from '@/types';

interface RoomSettingsPanelProps {
    settings: RoomSettings;
    maxPlayers: number;
    currentPlayers: number;
}

export function RoomSettingsPanel({ settings, maxPlayers, currentPlayers }: RoomSettingsPanelProps) {
    const difficultyColors = {
        easy: 'bg-green-500/10 text-green-500 border-green-500/20',
        medium: 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
        hard: 'bg-red-500/10 text-red-500 border-red-500/20',
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Game Settings</CardTitle>
                <CardDescription>Configuration for this room</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-lg bg-muted">
                            <Trophy className="h-4 w-4" />
                        </div>
                        <div>
                            <p className="text-sm font-medium">Questions</p>
                            <p className="text-sm text-muted-foreground">{settings.total_questions}</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-lg bg-muted">
                            <Clock className="h-4 w-4" />
                        </div>
                        <div>
                            <p className="text-sm font-medium">Time Limit</p>
                            <p className="text-sm text-muted-foreground">{settings.time_per_question}s</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-lg bg-muted">
                            <Target className="h-4 w-4" />
                        </div>
                        <div>
                            <p className="text-sm font-medium">Difficulty</p>
                            <Badge variant="outline" className={difficultyColors[settings.difficulty]}>
                                {settings.difficulty}
                            </Badge>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-lg bg-muted">
                            <Users className="h-4 w-4" />
                        </div>
                        <div>
                            <p className="text-sm font-medium">Players</p>
                            <p className="text-sm text-muted-foreground">
                                {currentPlayers} / {maxPlayers}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
