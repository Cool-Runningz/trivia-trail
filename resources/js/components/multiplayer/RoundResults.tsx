import { useEffect, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle2, XCircle, Trophy, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { type RoundResults as RoundResultsType } from '@/types';
import { cn } from '@/lib/utils';

interface RoundResultsProps {
    results: RoundResultsType;
    autoProgressDelay?: number;
    onAutoProgress?: () => void;
}

export function RoundResults({ 
    results, 
    autoProgressDelay = 5000,
    onAutoProgress 
}: RoundResultsProps) {
    const [countdown, setCountdown] = useState(Math.floor(autoProgressDelay / 1000));
    const [showResults, setShowResults] = useState(false);

    useEffect(() => {
        // Animate results in
        const timer = setTimeout(() => setShowResults(true), 100);
        return () => clearTimeout(timer);
    }, []);

    useEffect(() => {
        if (!onAutoProgress) return;

        const countdownInterval = setInterval(() => {
            setCountdown(prev => {
                if (prev <= 1) {
                    clearInterval(countdownInterval);
                    onAutoProgress();
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(countdownInterval);
    }, [onAutoProgress]);

    const getScoreChangeIcon = (points: number) => {
        if (points > 0) return <TrendingUp className="h-4 w-4 text-green-600" />;
        if (points < 0) return <TrendingDown className="h-4 w-4 text-red-600" />;
        return <Minus className="h-4 w-4 text-muted-foreground" />;
    };

    const getScoreChangeColor = (points: number) => {
        if (points > 0) return 'text-green-600';
        if (points < 0) return 'text-red-600';
        return 'text-muted-foreground';
    };

    return (
        <div className="space-y-6">
            {/* Correct Answer Display */}
            <Card className={cn(
                "border-2 transition-all duration-500",
                showResults ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
            )}>
                <CardHeader className="bg-green-50 dark:bg-green-950/20">
                    <div className="flex items-center gap-2">
                        <CheckCircle2 className="h-5 w-5 text-green-600" />
                        <CardTitle className="text-green-700 dark:text-green-400">
                            Correct Answer
                        </CardTitle>
                    </div>
                    <CardDescription 
                        className="text-base font-medium text-green-900 dark:text-green-300"
                        dangerouslySetInnerHTML={{ __html: results.correct_answer }}
                    />
                </CardHeader>
            </Card>

            {/* Leaderboard */}
            <Card className={cn(
                "transition-all duration-500 delay-200",
                showResults ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
            )}>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Trophy className="h-5 w-5 text-primary" />
                            <CardTitle>Leaderboard</CardTitle>
                        </div>
                        {onAutoProgress && countdown > 0 && (
                            <Badge variant="outline">
                                Next question in {countdown}s
                            </Badge>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="space-y-2">
                        {results.leaderboard.map((entry, index) => {
                            const participantResult = results.participant_results.find(
                                r => r.participant.id === entry.participant.id
                            );
                            const isCorrect = participantResult?.is_correct || false;
                            const pointsEarned = participantResult?.points_earned || 0;

                            return (
                                <div
                                    key={entry.participant.id}
                                    className={cn(
                                        "flex items-center justify-between p-4 rounded-lg border transition-all duration-300",
                                        index === 0 && "bg-yellow-50 dark:bg-yellow-950/20 border-yellow-200 dark:border-yellow-800",
                                        index === 1 && "bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800",
                                        index === 2 && "bg-orange-50 dark:bg-orange-950/20 border-orange-200 dark:border-orange-800",
                                        index > 2 && "bg-background border-border",
                                        showResults ? "opacity-100 translate-x-0" : "opacity-0 -translate-x-4"
                                    )}
                                    style={{ transitionDelay: `${300 + index * 100}ms` }}
                                >
                                    <div className="flex items-center gap-3 flex-1">
                                        {/* Position */}
                                        <div className={cn(
                                            "flex h-8 w-8 items-center justify-center rounded-full font-bold",
                                            index === 0 && "bg-yellow-500 text-white",
                                            index === 1 && "bg-gray-400 text-white",
                                            index === 2 && "bg-orange-500 text-white",
                                            index > 2 && "bg-muted text-muted-foreground"
                                        )}>
                                            {entry.position}
                                        </div>

                                        {/* Player Name */}
                                        <div className="flex-1">
                                            <div className="font-medium">
                                                {entry.participant.user.name}
                                            </div>
                                        </div>

                                        {/* Answer Status */}
                                        <div className="flex items-center gap-2">
                                            {isCorrect ? (
                                                <CheckCircle2 className="h-5 w-5 text-green-600" />
                                            ) : (
                                                <XCircle className="h-5 w-5 text-red-600" />
                                            )}
                                        </div>

                                        {/* Score Change */}
                                        <div className="flex items-center gap-1 min-w-[80px] justify-end">
                                            {getScoreChangeIcon(pointsEarned)}
                                            <span className={cn("font-semibold", getScoreChangeColor(pointsEarned))}>
                                                {pointsEarned > 0 ? '+' : ''}{pointsEarned}
                                            </span>
                                        </div>

                                        {/* Total Score */}
                                        <div className="min-w-[100px] text-right">
                                            <div className="text-2xl font-bold text-primary">
                                                {entry.score}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                total points
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            {/* Participant Answers Detail */}
            <Card className={cn(
                "transition-all duration-500 delay-300",
                showResults ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4"
            )}>
                <CardHeader>
                    <CardTitle>Round Summary</CardTitle>
                    <CardDescription>
                        {results.participant_results.filter(r => r.is_correct).length} of{' '}
                        {results.participant_results.length} players answered correctly
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-2">
                        {results.participant_results.map((result, index) => (
                            <div
                                key={result.participant.id}
                                className={cn(
                                    "flex items-center justify-between p-3 rounded-lg border transition-all duration-300",
                                    result.is_correct 
                                        ? "bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800"
                                        : "bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-800",
                                    showResults ? "opacity-100 translate-x-0" : "opacity-0 -translate-x-4"
                                )}
                                style={{ transitionDelay: `${400 + index * 50}ms` }}
                            >
                                <div className="flex items-center gap-3">
                                    {result.is_correct ? (
                                        <CheckCircle2 className="h-5 w-5 text-green-600" />
                                    ) : (
                                        <XCircle className="h-5 w-5 text-red-600" />
                                    )}
                                    <div>
                                        <div className="font-medium">
                                            {result.participant.user.name}
                                        </div>
                                        {result.selected_answer && (
                                            <div 
                                                className="text-sm text-muted-foreground"
                                                dangerouslySetInnerHTML={{ __html: result.selected_answer }}
                                            />
                                        )}
                                    </div>
                                </div>
                                {result.response_time_ms && (
                                    <Badge variant="outline" className="text-xs">
                                        {(result.response_time_ms / 1000).toFixed(2)}s
                                    </Badge>
                                )}
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
