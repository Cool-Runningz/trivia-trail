import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trophy, Medal, Award, Home, RotateCcw } from 'lucide-react';
import { type LeaderboardEntry } from '@/types';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import lobby from '@/routes/lobby';

interface FinalStandingsProps {
    leaderboard: LeaderboardEntry[];
    totalQuestions: number;
    roomCode?: string;
}

export function FinalStandings({ 
    leaderboard, 
    totalQuestions 
}: FinalStandingsProps) {
    const handleReturnToLobby = () => {
        router.visit(lobby.index().url);
    };

    const handlePlayAgain = () => {
        // This would create a new room with similar settings
        router.visit(lobby.index().url);
    };

    const getPositionIcon = (position: number) => {
        switch (position) {
            case 1:
                return <Trophy className="h-6 w-6 text-yellow-500" />;
            case 2:
                return <Medal className="h-6 w-6 text-gray-400" />;
            case 3:
                return <Award className="h-6 w-6 text-orange-500" />;
            default:
                return null;
        }
    };

    const getPositionBadge = (position: number, score: number) => {
        // Check if this score is tied with others at the same position
        const playersAtPosition = leaderboard.filter(e => e.score === score);
        const isTiedPosition = playersAtPosition.length > 1;
        
        const positionText = isTiedPosition ? `Tied ${position}${getOrdinalSuffix(position)}` : `${position}${getOrdinalSuffix(position)} Place`;
        
        switch (position) {
            case 1:
                return <Badge className="bg-yellow-500 hover:bg-yellow-600">{positionText}</Badge>;
            case 2:
                return <Badge className="bg-gray-400 hover:bg-gray-500">{positionText}</Badge>;
            case 3:
                return <Badge className="bg-orange-500 hover:bg-orange-600">{positionText}</Badge>;
            default:
                return <Badge variant="outline">{positionText}</Badge>;
        }
    };
    
    const getOrdinalSuffix = (num: number) => {
        const j = num % 10;
        const k = num % 100;
        if (j === 1 && k !== 11) return 'st';
        if (j === 2 && k !== 12) return 'nd';
        if (j === 3 && k !== 13) return 'rd';
        return 'th';
    };

    const winner = leaderboard[0];
    const maxScore = totalQuestions * 30; // Assuming hard difficulty max points
    
    // Check for ties at first place
    const topScore = winner?.score || 0;
    const winners = leaderboard.filter(entry => entry.score === topScore);
    const isTie = winners.length > 1;

    return (
        <div className="space-y-6">
            {/* Winner Celebration */}
            {winner && (
                <Card className="border-2 border-yellow-500 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-950/20 dark:to-orange-950/20">
                    <CardHeader className="text-center">
                        <div className="flex justify-center mb-4">
                            <div className="relative">
                                <Trophy className="h-20 w-20 text-yellow-500 animate-bounce" />
                                {!isTie && (
                                    <div className="absolute -top-2 -right-2">
                                        <div className="h-8 w-8 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">
                                            1
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                        {isTie ? (
                            <>
                                <CardTitle className="text-3xl">
                                    🎉 It's a Tie! 🎉
                                </CardTitle>
                                <CardDescription className="text-lg">
                                    {winners.map(w => w.participant.user.name).join(' & ')} tied with {topScore} points!
                                </CardDescription>
                            </>
                        ) : (
                            <>
                                <CardTitle className="text-3xl">
                                    🎉 {winner.participant.user.name} Wins! 🎉
                                </CardTitle>
                                <CardDescription className="text-lg">
                                    Final Score: {winner.score} points
                                </CardDescription>
                            </>
                        )}
                    </CardHeader>
                </Card>
            )}

            {/* Full Leaderboard */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Trophy className="h-5 w-5 text-primary" />
                        Final Standings
                    </CardTitle>
                    <CardDescription>
                        Game completed with {leaderboard.length} players
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        {leaderboard.map((entry) => {
                            const percentage = maxScore > 0 
                                ? Math.round((entry.score / maxScore) * 100) 
                                : 0;

                            return (
                                <div
                                    key={entry.participant.id}
                                    className={cn(
                                        "flex items-center gap-4 p-4 rounded-lg border transition-all",
                                        entry.position === 1 && "bg-yellow-50 dark:bg-yellow-950/20 border-yellow-200 dark:border-yellow-800",
                                        entry.position === 2 && "bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800",
                                        entry.position === 3 && "bg-orange-50 dark:bg-orange-950/20 border-orange-200 dark:border-orange-800",
                                        entry.position > 3 && "bg-background border-border"
                                    )}
                                >
                                    {/* Position Icon/Number */}
                                    <div className="flex items-center justify-center w-12">
                                        {getPositionIcon(entry.position) || (
                                            <div className="text-2xl font-bold text-muted-foreground">
                                                {entry.position}
                                            </div>
                                        )}
                                    </div>

                                    {/* Player Info */}
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 mb-1">
                                            <span className="font-semibold text-lg">
                                                {entry.participant.user.name}
                                            </span>
                                            {getPositionBadge(entry.position, entry.score)}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            {percentage}% accuracy
                                        </div>
                                    </div>

                                    {/* Score */}
                                    <div className="text-right">
                                        <div className="text-3xl font-bold text-primary">
                                            {entry.score}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            points
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            {/* Game Stats */}
            <Card>
                <CardHeader>
                    <CardTitle>Game Statistics</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="text-center p-4 rounded-lg bg-muted">
                            <div className="text-2xl font-bold">{totalQuestions}</div>
                            <div className="text-sm text-muted-foreground">Questions</div>
                        </div>
                        <div className="text-center p-4 rounded-lg bg-muted">
                            <div className="text-2xl font-bold">{leaderboard.length}</div>
                            <div className="text-sm text-muted-foreground">Players</div>
                        </div>
                        <div className="text-center p-4 rounded-lg bg-muted">
                            <div className="text-2xl font-bold">{winner?.score || 0}</div>
                            <div className="text-sm text-muted-foreground">Highest Score</div>
                        </div>
                        <div className="text-center p-4 rounded-lg bg-muted">
                            <div className="text-2xl font-bold">
                                {leaderboard.length > 0 
                                    ? Math.round(leaderboard.reduce((sum, e) => sum + e.score, 0) / leaderboard.length)
                                    : 0
                                }
                            </div>
                            <div className="text-sm text-muted-foreground">Average Score</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Action Buttons */}
            <div className="flex gap-4 justify-center">
                <Button
                    variant="outline"
                    size="lg"
                    onClick={handleReturnToLobby}
                >
                    <Home className="h-5 w-5 mr-2" />
                    Return to Lobby
                </Button>
                <Button
                    size="lg"
                    onClick={handlePlayAgain}
                >
                    <RotateCcw className="h-5 w-5 mr-2" />
                    Play Again
                </Button>
            </div>
        </div>
    );
}
