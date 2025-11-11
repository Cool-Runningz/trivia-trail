import { Card, CardContent } from '@/components/ui/card';
import { Trophy, Target } from 'lucide-react';

interface ScoreDisplayProps {
    currentScore: number;
    maxPossibleScore?: number;
    showMaxScore?: boolean;
    size?: 'sm' | 'md' | 'lg';
    variant?: 'default' | 'compact';
}

export default function ScoreDisplay({
    currentScore,
    maxPossibleScore,
    showMaxScore = false,
    size = 'md',
    variant = 'default',
}: ScoreDisplayProps) {
    const getSizeClasses = () => {
        switch (size) {
            case 'sm':
                return {
                    score: 'text-lg font-bold',
                    label: 'text-xs',
                    padding: 'pt-3',
                };
            case 'lg':
                return {
                    score: 'text-4xl font-bold',
                    label: 'text-base',
                    padding: 'pt-8',
                };
            default:
                return {
                    score: 'text-2xl font-bold',
                    label: 'text-sm',
                    padding: 'pt-6',
                };
        }
    };

    const classes = getSizeClasses();

    if (variant === 'compact') {
        return (
            <div className="flex items-center gap-2">
                <Trophy className="h-4 w-4 text-primary" />
                <span className="font-semibold">{currentScore}</span>
                {showMaxScore && maxPossibleScore && (
                    <span className="text-muted-foreground">/ {maxPossibleScore}</span>
                )}
                <span className="text-sm text-muted-foreground">points</span>
            </div>
        );
    }

    return (
        <Card>
            <CardContent className={classes.padding}>
                <div className="text-center">
                    <div className="mb-2 flex justify-center">
                        <Trophy className="h-6 w-6 text-primary" />
                    </div>
                    <div className={`${classes.score} text-primary`}>
                        {currentScore}
                        {showMaxScore && maxPossibleScore && (
                            <span className="text-muted-foreground">/{maxPossibleScore}</span>
                        )}
                    </div>
                    <p className={`${classes.label} text-muted-foreground`}>
                        {showMaxScore ? 'Score' : 'Current Score'}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}