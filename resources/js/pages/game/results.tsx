import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type GameResultsPageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Trophy, XCircle, Clock, Target, BarChart3 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Game Results',
        href: '#',
    },
];

export default function GameResults({ game, results }: GameResultsPageProps) {

    const formatDuration = (minutes: number | null) => {
        if (!minutes) return 'N/A';
        if (minutes < 1) return '< 1 minute';
        if (minutes === 1) return '1 minute';
        return `${Math.round(minutes)} minutes`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Game Results" />
            
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="mx-auto w-full max-w-4xl">
                    {/* Header with Trophy */}
                    <div className="mb-8 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                            <Trophy className="h-8 w-8 text-primary" />
                        </div>
                        <h1 className="text-3xl font-bold">Game Complete!</h1>
                        <p className="text-muted-foreground">Here's how you performed</p>
                    </div>

                    {/* Main Results Cards */}
                    <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-primary">{results.final_score}</div>
                                    <p className="text-sm text-muted-foreground">Final Score</p>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold">{results.correct_answers}</div>
                                    <p className="text-sm text-muted-foreground">of {results.total_questions} correct</p>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold">{results.percentage_score.toFixed(1)}%</div>
                                    <p className="text-sm text-muted-foreground">Accuracy</p>
                                </div>
                            </CardContent>
                        </Card>
                        

                    </div>

                    {/* Game Details */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BarChart3 className="h-5 w-5" />
                                Game Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="flex items-center gap-3">
                                    <Target className="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm font-medium">Difficulty</p>
                                        <p className="text-sm text-muted-foreground capitalize">{game.difficulty}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Clock className="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm font-medium">Duration</p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDuration(game.time_taken_minutes)}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Trophy className="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm font-medium">Questions</p>
                                        <p className="text-sm text-muted-foreground">{game.total_questions} total</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Answer Breakdown */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle>Answer Breakdown</CardTitle>
                            <CardDescription>
                                Review your answers and see where you can improve
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {results.answer_breakdown.map((answer, index) => (
                                    <div key={index} className="rounded-lg border p-4">
                                        <div className="flex items-start gap-3">
                                            <div className={`mt-1 flex h-6 w-6 items-center justify-center rounded-full ${
                                                answer.is_correct 
                                                    ? 'bg-green-100 text-green-600' 
                                                    : 'bg-red-100 text-red-600'
                                            }`}>
                                                {answer.is_correct ? (
                                                    <CheckCircle className="h-4 w-4" />
                                                ) : (
                                                    <XCircle className="h-4 w-4" />
                                                )}
                                            </div>
                                            <div className="flex-1">
                                                <p 
                                                    className="font-medium"
                                                    dangerouslySetInnerHTML={{ __html: answer.question }}
                                                />
                                                <div className="mt-2 space-y-1">
                                                    <p className="text-sm">
                                                        <span className="text-muted-foreground">Your answer:</span>{' '}
                                                        <span 
                                                            className={answer.is_correct ? 'text-green-600' : 'text-red-600'}
                                                            dangerouslySetInnerHTML={{ __html: answer.selected_answer }}
                                                        />
                                                    </p>
                                                    {!answer.is_correct && (
                                                        <p className="text-sm">
                                                            <span className="text-muted-foreground">Correct answer:</span>{' '}
                                                            <span 
                                                                className="text-green-600"
                                                                dangerouslySetInnerHTML={{ __html: answer.correct_answer }}
                                                            />
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-medium">
                                                    {answer.points_earned > 0 ? `+${answer.points_earned}` : '0'} pts
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Action Buttons */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:justify-center">
                        <Button asChild size="lg">
                            <Link href="/game/setup">
                                Play Again
                            </Link>
                        </Button>
                        <Button variant="outline" size="lg" asChild>
                            <Link href={dashboard().url}>
                                Back to Dashboard
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}