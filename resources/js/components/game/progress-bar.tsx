import { Progress } from '@/components/ui/progress';
import { Card, CardContent } from '@/components/ui/card';
import { type GameProgress } from '@/types';

interface ProgressBarProps {
    progress: GameProgress;
    showCard?: boolean;
    showPercentage?: boolean;
    showQuestionCount?: boolean;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}

export default function ProgressBar({
    progress,
    showCard = true,
    showPercentage = true,
    showQuestionCount = true,
    size = 'md',
    className = '',
}: ProgressBarProps) {
    const getProgressHeight = () => {
        switch (size) {
            case 'sm':
                return 'h-1';
            case 'lg':
                return 'h-3';
            default:
                return 'h-2';
        }
    };

    const getTextSize = () => {
        switch (size) {
            case 'sm':
                return 'text-xs';
            case 'lg':
                return 'text-base';
            default:
                return 'text-sm';
        }
    };

    const progressContent = (
        <div className={className}>
            <div className="flex items-center justify-between mb-2">
                {showQuestionCount && (
                    <span className={`font-medium ${getTextSize()}`}>
                        Question {progress.current} of {progress.total}
                    </span>
                )}
                {showPercentage && (
                    <span className={`text-muted-foreground ${getTextSize()}`}>
                        {progress.percentage.toFixed(1)}%
                    </span>
                )}
            </div>
            <Progress 
                value={progress.percentage} 
                className={getProgressHeight()} 
            />
        </div>
    );

    if (!showCard) {
        return progressContent;
    }

    return (
        <Card>
            <CardContent className="pt-6">
                {progressContent}
            </CardContent>
        </Card>
    );
}