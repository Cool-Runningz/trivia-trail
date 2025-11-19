import { type QuestionReviewItem } from '@/types';
import { AnswerBreakdown } from '@/components/game/AnswerBreakdown';

interface QuestionReviewProps {
    questions: QuestionReviewItem[];
}

export function QuestionReview({ questions }: QuestionReviewProps) {
    // Transform QuestionReviewItem[] to AnswerItem[] format
    const answers = questions.map((item) => ({
        question: item.question_text,
        selected_answer: item.user_answer || 'Not answered',
        correct_answer: item.correct_answer,
        is_correct: item.is_correct,
        points_earned: item.points_earned,
    }));

    return (
        <AnswerBreakdown 
            answers={answers}
        />
    );
}
