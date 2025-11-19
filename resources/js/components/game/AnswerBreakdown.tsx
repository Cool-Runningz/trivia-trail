import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { CheckCircle, XCircle } from 'lucide-react';

interface AnswerItem {
    question: string;
    selected_answer: string;
    correct_answer: string;
    is_correct: boolean;
    points_earned?: number;
}

interface AnswerBreakdownProps {
    answers: AnswerItem[];
}

export function AnswerBreakdown({ answers }: AnswerBreakdownProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Answer Breakdown</CardTitle>
                <CardDescription>
                    Review your answers and see where you can improve
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Accordion type="multiple" className="w-full space-y-4">
                    {answers.map((answer, index) => (
                        <AccordionItem 
                            key={index} 
                            value={`question-${index}`}
                            className="rounded-lg border"
                        >
                            <AccordionTrigger className="px-4 hover:no-underline">
                                <div className="flex items-start gap-3 w-full pr-4">
                                    <div className={`mt-1 flex h-6 w-6 items-center justify-center rounded-full shrink-0 ${
                                        answer.is_correct 
                                            ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' 
                                            : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                    }`}>
                                        {answer.is_correct ? (
                                            <CheckCircle className="h-4 w-4" />
                                        ) : (
                                            <XCircle className="h-4 w-4" />
                                        )}
                                    </div>
                                    <div className="flex-1 text-left">
                                        <p 
                                            className="font-medium"
                                            dangerouslySetInnerHTML={{ __html: answer.question }}
                                        />
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent className="px-4 pb-4">
                                <div className="pl-9 space-y-2">
                                    <p className="text-sm">
                                        <span className="text-muted-foreground">Your answer:</span>{' '}
                                        <span 
                                            className={answer.is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}
                                            dangerouslySetInnerHTML={{ __html: answer.selected_answer }}
                                        />
                                    </p>
                                    {!answer.is_correct && (
                                        <p className="text-sm">
                                            <span className="text-muted-foreground">Correct answer:</span>{' '}
                                            <span 
                                                className="text-green-600 dark:text-green-400"
                                                dangerouslySetInnerHTML={{ __html: answer.correct_answer }}
                                            />
                                        </p>
                                    )}
                                    {answer.points_earned !== undefined && (
                                        <p className="text-sm">
                                            <span className="text-muted-foreground">Points:</span>{' '}
                                            <span className="font-medium">
                                                {answer.points_earned > 0 ? `+${answer.points_earned}` : '0'} pts
                                            </span>
                                        </p>
                                    )}
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </CardContent>
        </Card>
    );
}
