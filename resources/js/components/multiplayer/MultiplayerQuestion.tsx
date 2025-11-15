import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { CheckCircle2, Clock, Users } from 'lucide-react';
import { type Question, type Participant } from '@/types';
import multiplayer from '@/routes/multiplayer';

interface MultiplayerQuestionProps {
    roomCode: string;
    question: Question;
    questionNumber: number;
    totalQuestions: number;
    timeRemaining: number;
    participants: Participant[];
    onAnswerSubmit?: (answer: string) => void;
    disabled?: boolean;
}

export function MultiplayerQuestion({
    roomCode,
    question,
    questionNumber,
    totalQuestions,
    timeRemaining,
    participants,
    onAnswerSubmit,
    disabled = false,
}: MultiplayerQuestionProps) {
    const [selectedAnswer, setSelectedAnswer] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [hasSubmitted, setHasSubmitted] = useState(false);

    // Calculate timer percentage for progress bar
    const timerPercentage = (timeRemaining / 30) * 100;
    
    // Get timer color based on remaining time
    const getTimerColor = () => {
        if (timeRemaining > 20) return 'text-green-600';
        if (timeRemaining > 10) return 'text-yellow-600';
        return 'text-red-600';
    };

    // Count participants who have answered
    const answeredCount = participants.filter(p => p.has_answered_current).length;
    const totalParticipants = participants.length;

    const handleAnswerSelect = (answer: string) => {
        if (disabled || hasSubmitted || isSubmitting) return;
        setSelectedAnswer(answer);
    };

    const handleSubmit = () => {
        if (!selectedAnswer || hasSubmitted || isSubmitting) return;
        
        setIsSubmitting(true);
        setHasSubmitted(true);

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
                    setHasSubmitted(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            }
        );
    };

    // Reset state when question changes
    useEffect(() => {
        // Use a ref to track if this is the initial mount
        const resetState = () => {
            setSelectedAnswer('');
            setHasSubmitted(false);
            setIsSubmitting(false);
        };
        
        resetState();
    }, [questionNumber]);

    const getAnswerButtonClass = (answer: string) => {
        let baseClass = "w-full rounded-lg border p-4 text-left transition-all";
        
        if (disabled || hasSubmitted) {
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
                    <div className="space-y-4">
                        {/* Timer Display */}
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Clock className={`h-5 w-5 ${getTimerColor()}`} />
                                <span className={`text-2xl font-bold ${getTimerColor()}`}>
                                    {timeRemaining}s
                                </span>
                                <span className="text-sm text-muted-foreground">remaining</span>
                            </div>
                            
                            {/* Answer Status */}
                            <div className="flex items-center gap-2">
                                <Users className="h-4 w-4 text-muted-foreground" />
                                <span className="text-sm font-medium">
                                    {answeredCount} / {totalParticipants}
                                </span>
                                <span className="text-sm text-muted-foreground">answered</span>
                            </div>
                        </div>

                        {/* Timer Progress Bar */}
                        <Progress 
                            value={timerPercentage} 
                            className="h-2"
                        />

                        {/* Participant Answer Status */}
                        <div className="flex flex-wrap gap-2">
                            {participants.map((participant) => (
                                <Badge
                                    key={participant.id}
                                    variant={participant.has_answered_current ? "default" : "outline"}
                                    className="gap-1"
                                >
                                    {participant.has_answered_current && (
                                        <CheckCircle2 className="h-3 w-3" />
                                    )}
                                    {participant.user.name}
                                </Badge>
                            ))}
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
                                    disabled={disabled || hasSubmitted || isSubmitting}
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

                        {/* Submit Button */}
                        <div className="flex justify-between items-center pt-4">
                            {hasSubmitted ? (
                                <div className="flex items-center gap-2 text-green-600">
                                    <CheckCircle2 className="h-5 w-5" />
                                    <span className="font-medium">Answer submitted! Waiting for others...</span>
                                </div>
                            ) : (
                                <>
                                    <div className="text-sm text-muted-foreground">
                                        {selectedAnswer ? 'Click submit to lock in your answer' : 'Select an answer'}
                                    </div>
                                    <Button
                                        onClick={handleSubmit}
                                        disabled={!selectedAnswer || hasSubmitted || isSubmitting || disabled}
                                        className="min-w-[120px]"
                                    >
                                        {isSubmitting ? 'Submitting...' : 'Submit Answer'}
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
