import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type GameResultsPageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { AnswerBreakdown, GameDetails } from '@/components/game';

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
                    <div className="mb-8">
                        <GameDetails 
                            difficulty={game.difficulty}
                            totalQuestions={game.total_questions}
                            duration={game.time_taken_minutes}
                        />
                    </div>

                    {/* Answer Breakdown */}
                    <AnswerBreakdown 
                        answers={results.answer_breakdown}
                    />

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