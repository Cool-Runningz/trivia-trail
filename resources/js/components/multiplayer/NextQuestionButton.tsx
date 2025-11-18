import { useState, useEffect, useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronRight } from 'lucide-react';
import multiplayer from '@/routes/multiplayer';

interface NextQuestionButtonProps {
    roomCode: string;
    readySince: string | null;
    allPlayersAnswered: boolean;
}

export function NextQuestionButton({
    roomCode,
    readySince,
    allPlayersAnswered
}: NextQuestionButtonProps) {
    const [countdown, setCountdown] = useState(3);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const hasAdvanced = useRef(false);

    const handleNextQuestion = useCallback(() => {
        if (isSubmitting || hasAdvanced.current) return;
        hasAdvanced.current = true;
        setIsSubmitting(true);

        router.post(
            multiplayer.game.next(roomCode).url,
            {},
            {
                onFinish: () => setIsSubmitting(false),
                onError: (errors) => {
                    console.error('Failed to advance question:', errors);
                    hasAdvanced.current = false;
                }
            }
        );
    }, [isSubmitting, roomCode]);

    // Auto-advance after 2 seconds
    useEffect(() => {
        if (!readySince) return;

        const readyTime = new Date(readySince).getTime();
        
        const updateCountdown = () => {
            const now = Date.now();
            const elapsed = Math.floor((now - readyTime) / 1000);
            const remaining = Math.max(0, 3 - elapsed);

            setCountdown(remaining);

            if (remaining === 0 && !hasAdvanced.current) {
                handleNextQuestion();
            }
        };

        // Set up interval for countdown
        const timer = setInterval(updateCountdown, 100);

        // Initial check
        updateCountdown();

        return () => clearInterval(timer);
    }, [readySince, handleNextQuestion]);

    return (
        <div className="space-y-3 pt-4 border-t">
            <Button
                onClick={handleNextQuestion}
                disabled={isSubmitting}
                size="lg"
                className="w-full"
            >
                <ChevronRight className="h-5 w-5 mr-2" />
                {isSubmitting ? 'Loading...' : 'Next Question'}
            </Button>

            <p className="text-sm text-center text-muted-foreground">
                {countdown > 0 ? (
                    allPlayersAnswered
                        ? `All players answered! Auto-advancing in ${countdown}s...`
                        : `Time's up! Auto-advancing in ${countdown}s...`
                ) : (
                    'Advancing...'
                )}
            </p>
        </div>
    );
}
