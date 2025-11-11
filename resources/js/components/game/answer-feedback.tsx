import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle, XCircle, Trophy } from 'lucide-react';

interface AnswerFeedbackProps {
    isCorrect: boolean;
    correctAnswer: string;
    selectedAnswer: string;
    pointsEarned: number;
    explanation?: string;
    showAnimation?: boolean;
}

export default function AnswerFeedback({
    isCorrect,
    correctAnswer,
    selectedAnswer,
    pointsEarned,
    explanation,
    showAnimation = true,
}: AnswerFeedbackProps) {
    const feedbackClass = isCorrect 
        ? 'border-green-200 bg-green-50 text-green-800' 
        : 'border-red-200 bg-red-50 text-red-800';

    const iconClass = isCorrect 
        ? 'text-green-600' 
        : 'text-red-600';

    const animationClass = showAnimation 
        ? 'animate-in slide-in-from-top-2 duration-300' 
        : '';

    return (
        <Alert className={`${feedbackClass} ${animationClass}`}>
            <div className="flex items-start gap-3">
                <div className="mt-0.5">
                    {isCorrect ? (
                        <CheckCircle className={`h-5 w-5 ${iconClass}`} />
                    ) : (
                        <XCircle className={`h-5 w-5 ${iconClass}`} />
                    )}
                </div>
                <div className="flex-1">
                    <AlertDescription>
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="font-semibold">
                                    {isCorrect ? 'Correct!' : 'Incorrect'}
                                </span>
                                <div className="flex items-center gap-1">
                                    <Trophy className="h-4 w-4" />
                                    <span className="font-medium">
                                        +{pointsEarned} points
                                    </span>
                                </div>
                            </div>
                            
                            {!isCorrect && (
                                <div className="space-y-1">
                                    <p>
                                        <span className="font-medium">Your answer:</span>{' '}
                                        <span dangerouslySetInnerHTML={{ __html: selectedAnswer }} />
                                    </p>
                                    <p>
                                        <span className="font-medium">Correct answer:</span>{' '}
                                        <span 
                                            className="font-semibold"
                                            dangerouslySetInnerHTML={{ __html: correctAnswer }} 
                                        />
                                    </p>
                                </div>
                            )}
                            
                            {explanation && (
                                <p className="text-sm opacity-90">
                                    <span className="font-medium">Explanation:</span> {explanation}
                                </p>
                            )}
                        </div>
                    </AlertDescription>
                </div>
            </div>
        </Alert>
    );
}