import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type GamePlayPageProps } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Playing Game',
        href: '#',
    },
];

export default function PlayGame({ game, question, progress }: GamePlayPageProps) {
    const [selectedAnswer, setSelectedAnswer] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleAnswerSubmit = () => {
        if (!selectedAnswer) return;
        setIsSubmitting(true);
    };

    const getDifficultyColor = (difficulty: string) => {
        switch (difficulty) {
            case 'easy':
                return 'text-green-600';
            case 'medium':
                return 'text-yellow-600';
            case 'hard':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    };

    const getDifficultyPoints = (difficulty: string) => {
        switch (difficulty) {
            case 'easy':
                return 10;
            case 'medium':
                return 20;
            case 'hard':
                return 30;
            default:
                return 0;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Question ${progress.current} of ${progress.total}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="mx-auto w-full max-w-4xl">
                    {/* Progress and Score Header */}
                    <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{progress.current}</div>
                                    <p className="text-sm text-muted-foreground">of {progress.total} questions</p>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className="text-2xl font-bold">{game.score}</div>
                                    <p className="text-sm text-muted-foreground">current score</p>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <div className={`text-2xl font-bold capitalize ${getDifficultyColor(game.difficulty)}`}>
                                        {game.difficulty}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {getDifficultyPoints(game.difficulty)} points
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Progress Bar */}
                    <div className="mb-6">
                        <Progress value={progress.percentage} className="h-2" />
                        <p className="mt-2 text-center text-sm text-muted-foreground">
                            {progress.percentage.toFixed(1)}% complete
                        </p>
                    </div>

                    {/* Question Card */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-lg">Question {progress.current}</CardTitle>
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-muted-foreground">Category:</span>
                                    <span className="text-sm font-medium">{question.category}</span>
                                </div>
                            </div>
                            <CardDescription 
                                className="text-base leading-relaxed"
                                dangerouslySetInnerHTML={{ __html: question.question }}
                            />
                        </CardHeader>
                        <CardContent>
                            <Form
                                action={`/game/${game.id}/answer`}
                                method="post"
                                onSubmit={handleAnswerSubmit}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input type="hidden" name="selected_answer" value={selectedAnswer} />
                                        <input type="hidden" name="question_index" value={game.current_question_index} />
                                        
                                        <div className="grid gap-3">
                                            {question.shuffled_answers.map((answer, index) => (
                                                <button
                                                    key={index}
                                                    type="button"
                                                    onClick={() => setSelectedAnswer(answer)}
                                                    className={`w-full rounded-lg border p-4 text-left transition-all hover:bg-accent hover:text-accent-foreground ${
                                                        selectedAnswer === answer
                                                            ? 'border-primary bg-primary/10 text-primary'
                                                            : 'border-input bg-background'
                                                    }`}
                                                    disabled={processing || isSubmitting}
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <div className={`flex h-6 w-6 items-center justify-center rounded-full border-2 text-sm font-medium ${
                                                            selectedAnswer === answer
                                                                ? 'border-primary bg-primary text-primary-foreground'
                                                                : 'border-muted-foreground/30'
                                                        }`}>
                                                            {String.fromCharCode(65 + index)}
                                                        </div>
                                                        <span 
                                                            className="flex-1"
                                                            dangerouslySetInnerHTML={{ __html: answer }}
                                                        />
                                                    </div>
                                                </button>
                                            ))}
                                        </div>

                                        {errors.selected_answer && (
                                            <p className="text-sm text-destructive">{errors.selected_answer}</p>
                                        )}

                                        <div className="flex justify-between pt-4">
                                            <div className="text-sm text-muted-foreground">
                                                Select an answer to continue
                                            </div>
                                            <Button
                                                type="submit"
                                                disabled={!selectedAnswer || processing || isSubmitting}
                                                className="min-w-[120px]"
                                            >
                                                {(processing || isSubmitting) && <Spinner />}
                                                Submit Answer
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}