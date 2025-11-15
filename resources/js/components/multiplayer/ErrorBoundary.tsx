import { Component, ReactNode } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, RefreshCw, Home } from 'lucide-react';
import { router } from '@inertiajs/react';
import lobby from '@/routes/lobby';

interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: ReactNode;
    onError?: (error: Error, errorInfo: React.ErrorInfo) => void;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
    errorInfo: React.ErrorInfo | null;
}

export class MultiplayerErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    constructor(props: ErrorBoundaryProps) {
        super(props);
        this.state = {
            hasError: false,
            error: null,
            errorInfo: null,
        };
    }

    static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
        return {
            hasError: true,
            error,
        };
    }

    componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
        console.error('Multiplayer Error Boundary caught an error:', error, errorInfo);
        
        this.setState({
            error,
            errorInfo,
        });

        // Call optional error handler
        this.props.onError?.(error, errorInfo);
    }

    handleReset = () => {
        this.setState({
            hasError: false,
            error: null,
            errorInfo: null,
        });
        
        // Reload the current page
        router.reload();
    };

    handleReturnToLobby = () => {
        router.visit(lobby.index().url);
    };

    render() {
        if (this.state.hasError) {
            // Use custom fallback if provided
            if (this.props.fallback) {
                return this.props.fallback;
            }

            // Default error UI
            return (
                <div className="container mx-auto py-8">
                    <Card className="max-w-2xl mx-auto border-destructive">
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <AlertCircle className="h-6 w-6 text-destructive" />
                                <div>
                                    <CardTitle>Something went wrong</CardTitle>
                                    <CardDescription>
                                        An error occurred in the multiplayer game
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Alert variant="destructive">
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    {this.state.error?.message || 'An unexpected error occurred'}
                                </AlertDescription>
                            </Alert>

                            {process.env.NODE_ENV === 'development' && this.state.errorInfo && (
                                <details className="text-sm">
                                    <summary className="cursor-pointer font-medium mb-2">
                                        Error Details (Development Only)
                                    </summary>
                                    <pre className="p-4 bg-muted rounded-lg overflow-auto text-xs">
                                        {this.state.error?.stack}
                                        {'\n\n'}
                                        {this.state.errorInfo.componentStack}
                                    </pre>
                                </details>
                            )}

                            <div className="flex gap-3">
                                <Button
                                    onClick={this.handleReset}
                                    className="flex-1"
                                >
                                    <RefreshCw className="h-4 w-4 mr-2" />
                                    Try Again
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={this.handleReturnToLobby}
                                    className="flex-1"
                                >
                                    <Home className="h-4 w-4 mr-2" />
                                    Return to Lobby
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            );
        }

        return this.props.children;
    }
}

/**
 * Hook-based error handler for functional components
 */
export function useErrorHandler() {
    const handleError = (error: Error, context?: string) => {
        console.error(`Error in ${context || 'component'}:`, error);
        
        // You could send to error tracking service here
        // e.g., Sentry.captureException(error);
    };

    const handleNetworkError = (error: Error) => {
        console.error('Network error:', error);
        
        // Show user-friendly message
        return {
            title: 'Connection Error',
            message: 'Unable to connect to the server. Please check your internet connection.',
            canRetry: true,
        };
    };

    const handleGameError = (error: Error) => {
        console.error('Game error:', error);
        
        return {
            title: 'Game Error',
            message: 'An error occurred during the game. Please try again.',
            canRetry: true,
        };
    };

    return {
        handleError,
        handleNetworkError,
        handleGameError,
    };
}
