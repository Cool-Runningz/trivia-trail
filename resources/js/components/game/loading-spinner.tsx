import { Spinner } from '@/components/ui/spinner';
import { Card, CardContent } from '@/components/ui/card';

interface LoadingSpinnerProps {
    message?: string;
    size?: 'sm' | 'md' | 'lg';
    showCard?: boolean;
    className?: string;
}

export default function LoadingSpinner({
    message = 'Loading...',
    size = 'md',
    showCard = false,
    className = '',
}: LoadingSpinnerProps) {
    const getSpinnerSize = () => {
        switch (size) {
            case 'sm':
                return 'h-4 w-4';
            case 'lg':
                return 'h-8 w-8';
            default:
                return 'h-6 w-6';
        }
    };

    const getTextSize = () => {
        switch (size) {
            case 'sm':
                return 'text-sm';
            case 'lg':
                return 'text-lg';
            default:
                return 'text-base';
        }
    };

    const spinnerContent = (
        <div className={`flex flex-col items-center justify-center gap-3 ${className}`}>
            <Spinner className={getSpinnerSize()} />
            <p className={`text-muted-foreground ${getTextSize()}`}>
                {message}
            </p>
        </div>
    );

    if (!showCard) {
        return spinnerContent;
    }

    return (
        <Card>
            <CardContent className="py-12">
                {spinnerContent}
            </CardContent>
        </Card>
    );
}

// Specific loading components for common game scenarios
export function LoadingQuestions() {
    return (
        <LoadingSpinner
            message="Loading questions..."
            size="lg"
            showCard
            className="py-8"
        />
    );
}

export function LoadingCategories() {
    return (
        <LoadingSpinner
            message="Loading categories..."
            size="md"
        />
    );
}

export function SubmittingAnswer() {
    return (
        <LoadingSpinner
            message="Submitting answer..."
            size="sm"
        />
    );
}

export function LoadingResults() {
    return (
        <LoadingSpinner
            message="Calculating results..."
            size="lg"
            showCard
            className="py-8"
        />
    );
}