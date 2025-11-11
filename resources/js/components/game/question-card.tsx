import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type Question } from '@/types';

interface QuestionCardProps {
    question: Question;
    questionNumber: number;
    selectedAnswer?: string;
    onAnswerSelect?: (answer: string) => void;
    disabled?: boolean;
    showFeedback?: boolean;
    isCorrect?: boolean;
}

export default function QuestionCard({
    question,
    questionNumber,
    selectedAnswer,
    onAnswerSelect,
    disabled = false,
    showFeedback = false,
    isCorrect,
}: QuestionCardProps) {
    const getAnswerButtonClass = (answer: string) => {
        let baseClass = "w-full rounded-lg border p-4 text-left transition-all";
        
        if (disabled) {
            baseClass += " cursor-not-allowed opacity-50";
        } else {
            baseClass += " hover:bg-accent hover:text-accent-foreground cursor-pointer";
        }

        if (showFeedback) {
            if (answer === question.correct_answer) {
                baseClass += " border-green-500 bg-green-50 text-green-700";
            } else if (answer === selectedAnswer && !isCorrect) {
                baseClass += " border-red-500 bg-red-50 text-red-700";
            } else {
                baseClass += " border-input bg-background";
            }
        } else if (selectedAnswer === answer) {
            baseClass += " border-primary bg-primary/10 text-primary";
        } else {
            baseClass += " border-input bg-background";
        }

        return baseClass;
    };

    const getAnswerIndicatorClass = (answer: string) => {
        let baseClass = "flex h-6 w-6 items-center justify-center rounded-full border-2 text-sm font-medium";
        
        if (showFeedback) {
            if (answer === question.correct_answer) {
                baseClass += " border-green-500 bg-green-500 text-white";
            } else if (answer === selectedAnswer && !isCorrect) {
                baseClass += " border-red-500 bg-red-500 text-white";
            } else {
                baseClass += " border-muted-foreground/30";
            }
        } else if (selectedAnswer === answer) {
            baseClass += " border-primary bg-primary text-primary-foreground";
        } else {
            baseClass += " border-muted-foreground/30";
        }

        return baseClass;
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Question {questionNumber}</CardTitle>
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
                <div className="grid gap-3">
                    {question.shuffled_answers.map((answer, index) => (
                        <button
                            key={index}
                            type="button"
                            onClick={() => onAnswerSelect?.(answer)}
                            className={getAnswerButtonClass(answer)}
                            disabled={disabled}
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
            </CardContent>
        </Card>
    );
}