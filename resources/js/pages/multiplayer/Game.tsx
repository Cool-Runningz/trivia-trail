import { Head } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import { type BreadcrumbItem, type MultiplayerGameState } from '@/types';
import { 
    MultiplayerQuestion, 
    RoundResults, 
    FinalStandings,
    ConnectionStatus,
    ConnectionIndicator,
    MultiplayerErrorBoundary 
} from '@/components/multiplayer';
import { useGamePolling } from '@/hooks/use-game-polling';
import { type GamePhase } from '@/hooks/use-room-polling';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import lobby from '@/routes/lobby';

interface MultiplayerGameProps {
    gameState: MultiplayerGameState;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Multiplayer Lobby',
        href: lobby.index().url,
    },
    {
        title: 'Playing Game',
        href: '#',
    },
];

export default function MultiplayerGame({ gameState: initialGameState }: MultiplayerGameProps) {
    // Determine current game phase
    const getGamePhase = (): GamePhase => {
        const { room, round_results } = initialGameState;
        
        if (room.status === 'completed') return 'completed';
        if (round_results) return 'results';
        if (initialGameState.current_question) return 'active';
        return 'lobby';
    };

    const gamePhase = getGamePhase();

    // Determine polling interval based on game phase
    const pollingInterval = gamePhase === 'active' ? 1000 : gamePhase === 'results' ? 2000 : 3000;

    // Use polling hook with dynamic intervals
    const { connectionStatus, lastUpdate } = useGamePolling(
        pollingInterval,
        gamePhase !== 'completed'
    );

    const { room, current_question, current_question_index, time_remaining, participants, round_results } = initialGameState;

    // Show final standings if game is completed
    if (room.status === 'completed' && round_results?.leaderboard) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <MultiplayerErrorBoundary>
                    <Head title="Game Complete" />
                    
                    <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                        <div className="max-w-4xl mx-auto space-y-6 w-full">
                        <div className="flex items-center justify-between">
                            <h1 className="text-3xl font-bold">Game Complete!</h1>
                            <ConnectionIndicator status={connectionStatus} />
                        </div>

                        <FinalStandings
                            leaderboard={round_results.leaderboard}
                            totalQuestions={room.settings.total_questions}
                            roomCode={room.room_code}
                        />
                        </div>
                    </div>
                </MultiplayerErrorBoundary>
            </AppLayout>
        );
    }

    // Show round results if available
    if (round_results) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <MultiplayerErrorBoundary>
                    <Head title={`Round ${current_question_index} Results`} />
                    
                    <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                        <div className="max-w-4xl mx-auto space-y-6 w-full">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold">Round Results</h1>
                                <p className="text-muted-foreground">
                                    Question {current_question_index} of {room.settings.total_questions}
                                </p>
                            </div>
                            <ConnectionIndicator status={connectionStatus} />
                        </div>

                        <ConnectionStatus
                            status={connectionStatus}
                            lastUpdate={lastUpdate}
                            compact
                        />

                        <RoundResults
                            results={round_results}
                            autoProgressDelay={5000}
                        />
                        </div>
                    </div>
                </MultiplayerErrorBoundary>
            </AppLayout>
        );
    }

    // Show active question
    if (current_question) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <MultiplayerErrorBoundary>
                    <Head title={`Question ${current_question_index + 1}`} />
                    
                    <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                        <div className="max-w-4xl mx-auto space-y-6 w-full">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold">Room {room.room_code}</h1>
                                <p className="text-muted-foreground">
                                    Question {current_question_index + 1} of {room.settings.total_questions}
                                </p>
                            </div>
                            <ConnectionIndicator status={connectionStatus} />
                        </div>

                        <ConnectionStatus
                            status={connectionStatus}
                            lastUpdate={lastUpdate}
                            compact
                        />

                        <MultiplayerQuestion
                            roomCode={room.room_code}
                            question={current_question}
                            questionNumber={current_question_index + 1}
                            totalQuestions={room.settings.total_questions}
                            timeRemaining={time_remaining}
                            participants={participants}
                        />
                        </div>
                    </div>
                </MultiplayerErrorBoundary>
            </AppLayout>
        );
    }

    // Fallback: waiting state
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <MultiplayerErrorBoundary>
                <Head title="Waiting..." />
                
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="max-w-4xl mx-auto space-y-6 w-full">
                    <div className="flex items-center justify-between">
                        <h1 className="text-3xl font-bold">Room {room.room_code}</h1>
                        <ConnectionIndicator status={connectionStatus} />
                    </div>

                    <ConnectionStatus
                        status={connectionStatus}
                        lastUpdate={lastUpdate}
                    />

                    <Card>
                        <CardContent className="pt-6">
                            <Alert>
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    Waiting for game to start...
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>
                    </div>
                </div>
            </MultiplayerErrorBoundary>
        </AppLayout>
    );
}
