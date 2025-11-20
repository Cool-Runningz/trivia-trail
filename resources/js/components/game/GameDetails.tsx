import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart3, Clock, Target, Trophy, Users, BookOpen } from 'lucide-react';
import { type DifficultyLevel } from '@/types';

//TODO: Instead of passing down the individual props, just pass in the `game` object and destructure from there.
interface GameDetailsProps {
    difficulty: DifficultyLevel;
    totalQuestions: number;
    duration?: number | null; // in minutes (single-player)
    playerCount?: number; // multiplayer
    category?: string | null; // category name
}

export function GameDetails({ difficulty, totalQuestions, duration, playerCount, category }: GameDetailsProps) {
    const formatDuration = (minutes: number | null) => {
        if (!minutes) return 'N/A';
        if (minutes < 1) return '< 1 minute';
        if (minutes === 1) return '1 minute';
        return `${Math.round(minutes)} minutes`;
    };


    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <BarChart3 className="h-5 w-5" />
                    Game Details
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className={`grid grid-cols-1 gap-4 md:grid-cols-2`}>
                    <div className="flex items-center gap-3">
                        <Target className="h-5 w-5 text-muted-foreground" />
                        <div>
                            <p className="text-sm font-medium">Difficulty</p>
                            <p className="text-sm text-muted-foreground capitalize">{difficulty}</p>
                        </div>
                    </div>
                    
                    {category && (
                        <div className="flex items-center gap-3">
                            <BookOpen className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium">Category</p>
                                <p className="text-sm text-muted-foreground">{category}</p>
                            </div>
                        </div>
                    )}
                    
                    {duration !== undefined ? (
                        <div className="flex items-center gap-3">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium">Duration</p>
                                <p className="text-sm text-muted-foreground">
                                    {formatDuration(duration)}
                                </p>
                            </div>
                        </div>
                    ) : playerCount !== undefined ? (
                        <div className="flex items-center gap-3">
                            <Users className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium">Players</p>
                                <p className="text-sm text-muted-foreground">{playerCount} players</p>
                            </div>
                        </div>
                    ) : null}
                    
                    <div className="flex items-center gap-3">
                        <Trophy className="h-5 w-5 text-muted-foreground" />
                        <div>
                            <p className="text-sm font-medium">Questions</p>
                            <p className="text-sm text-muted-foreground">{totalQuestions} total</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
