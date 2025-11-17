import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2, Clock } from 'lucide-react';
import { type Question } from '@/types';
import multiplayer from '@/routes/multiplayer';
import { NextQuestionButton } from './NextQuestionButton';

interface MultiplayerQuestionProps {
    roomCode: string;
    question: Question;
    questionNumber: number;
    totalQuestions: number;
    timeRemaining: number;
    allPlayersAnswered: boolean;
    currentUserHasAnswered: boolean;
    isReadyForNext: boolean;
    readySince: string | null;
    isHost: boolean;
    onAnswerSubmit?: (answer: string) => void;
    disabled?: boolean;
}

export function MultiplayerQuestion({
    roomCode,
    question,
    questionNumber,
    totalQuestions,
    timeRemaining,
    allPlayersAnswered,
    currentUserHasAnswered,
    isReadyForNext,
    readySince,
    isHost,
    onAnswerSubmit,
    disabled = false,
}: MultiplayerQuestionProps) {
    const [selectedAnswer, setSelectedAnswer] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const timeExpired = timeRemaining <= 0;
    
    // Get timer color based on remaining time
    const getTimerColor = () => {
        if (timeRemaining > 20) return 'text-green-600';
        if (timeRemaining > 10) return 'text-yellow-600';
        return 'text-red-600';
    };

    const handleAnswerSelect = (answer: string) => {
        if (disabled || currentUserHasAnswered || isSubmitting || timeExpired) return;
        setSelectedAnswer(answer);
    };

    const handleSubmit = () => {
        if (!selectedAnswer || currentUserHasAnswered || isSubmitting || timeExpired) return;
        
        setIsSubmitting(true);

        router.post(
            multiplayer.game.answer(roomCode).url,
            {
                selected_answer: selectedAnswer,
                question_index: questionNumber - 1,
            },
            {
                onSuccess: () => {
                    onAnswerSubmit?.(selectedAnswer);
                },
                onError: () => {
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            }
        );
    };

    const getAnswerButtonClass = (answer: string) => {
        let baseClass = "w-full rounded-lg border p-4 text-left transition-all";
        
        if (disabled || currentUserHasAnswered || timeExpired) {
            baseClass += " cursor-not-allowed opacity-50";
        } else {
            baseClass += " hover:bg-accent hover:text-accent-foreground cursor-pointer";
        }

        if (selectedAnswer === answer) {
            baseClass += " border-primary bg-primary/10 text-primary";
        } else {
            baseClass += " border-input bg-background";
        }

        return baseClass;
    };

    const getAnswerIndicatorClass = (answer: string) => {
        let baseClass = "flex h-6 w-6 items-center justify-center rounded-full border-2 text-sm font-medium";
        
        if (selectedAnswer === answer) {
            baseClass += " border-primary bg-primary text-primary-foreground";
        } else {
            baseClass += " border-muted-foreground/30";
        }

        return baseClass;
    };

    return (
        <div className="space-y-4">
            {/* Timer and Status Bar */}
            <Card>
                <CardContent className="pt-6">
                    <div className="flex items-center justify-center">
                        <div className="flex items-center gap-2">
                            <Clock className={`h-5 w-5 ${getTimerColor()}`} />
                            <span className={`text-2xl font-bold ${getTimerColor()}`}>
                                {timeRemaining}s
                            </span>
                            <span className="text-sm text-muted-foreground">remaining</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Question Card */}
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-lg">
                            Question {questionNumber} of {totalQuestions}
                        </CardTitle>
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
                    <div className="space-y-4">
                        {/* Answer Options */}
                        <div className="grid gap-3">
                            {question.shuffled_answers.map((answer, index) => (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() => handleAnswerSelect(answer)}
                                    className={getAnswerButtonClass(answer)}
                                    disabled={disabled || currentUserHasAnswered || isSubmitting || timeExpired}
                                >
                                    <div className="flex items-center gap-3">
                                        <div className={getAnswerIndicatorClass(answer)}>
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

                        {/* Submit Button and Status Messages */}
                        <div className="pt-4">
                            {currentUserHasAnswered && !isReadyForNext && (
                                <div className="flex items-center gap-2 text-green-600 justify-center">
                                    <CheckCircle2 className="h-5 w-5" />
                                    <span className="font-medium">Answer submitted! Waiting for others...</span>
                                </div>
                            )}

                            {allPlayersAnswered && !isHost && (
                                <div className="flex items-center gap-2 text-blue-600 justify-center">
                                    <CheckCircle2 className="h-5 w-5" />
                                    <span className="font-medium">All players answered! Waiting for host...</span>
                                </div>
                            )}

                            {timeExpired && !allPlayersAnswered && !isHost && (
                                <div className="flex items-center gap-2 text-orange-600 justify-center">
                                    <Clock className="h-5 w-5" />
                                    <span className="font-medium">Time's up! Waiting for host...</span>
                                </div>
                            )}

                            {isReadyForNext && isHost && (
                                <NextQuestionButton 
                                    roomCode={roomCode}
                                    readySince={readySince}
                                    allPlayersAnswered={allPlayersAnswered}
                                />
                            )}

                            {!currentUserHasAnswered && !timeExpired && (
                                <div className="flex justify-between items-center">
                                    <div className="text-sm text-muted-foreground">
                                        {selectedAnswer ? 'Click submit to lock in your answer' : 'Select an answer'}
                                    </div>
                                    <Button
                                        onClick={handleSubmit}
                                        disabled={!selectedAnswer || currentUserHasAnswered || isSubmitting || disabled || timeExpired}
                                        className="min-w-[120px]"
                                    >
                                        {isSubmitting ? 'Submitting...' : 'Submit Answer'}
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
