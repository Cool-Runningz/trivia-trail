import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import { type BreadcrumbItem, type MultiplayerGameState, type SharedData } from '@/types';
import { 
    MultiplayerQuestion, 
    RoundResults, 
    FinalStandings,
    ConnectionStatus,
    ConnectionIndicator,
    MultiplayerErrorBoundary,
    CancelGameButton
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
    const { auth } = usePage<SharedData>().props;
    const isHost = initialGameState.room.host_user_id === auth.user.id;

    // Determine current game phase
    const getGamePhase = (): GamePhase => {
        const { room, round_results, game_status } = initialGameState;
        
        // Check both room status and game status for completion
        if (room.status === 'completed' || game_status === 'completed') return 'completed';
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

    const { 
        room, 
        current_question, 
        current_question_index, 
        time_remaining, 
        round_results, 
        game_status,
        all_players_answered = false,
        current_user_has_answered = false,
        is_ready_for_next = false,
        ready_since = null
    } = initialGameState;

    // Show loading state when game is completed but waiting for redirect
    if ((room.status === 'completed' || game_status === 'completed') && !round_results?.leaderboard) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <MultiplayerErrorBoundary>
                    <Head title="Loading Results..." />
                    
                    <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                        <div className="max-w-4xl mx-auto space-y-6 w-full">
                            <div className="flex items-center justify-between">
                                <h1 className="text-3xl font-bold">Game Complete!</h1>
                                <ConnectionIndicator status={connectionStatus} />
                            </div>

                            <Card>
                                <CardContent className="pt-6">
                                    <div className="flex flex-col items-center justify-center space-y-4 py-8">
                                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                                        <p className="text-muted-foreground">Loading final results...</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </MultiplayerErrorBoundary>
            </AppLayout>
        );
    }

    // Show final standings if game is completed
    if ((room.status === 'completed' || game_status === 'completed') && round_results?.leaderboard) {
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
                            difficulty={room.settings.difficulty}
                            roomCode={room.room_code}
                            questionsReview={round_results.questions_review}
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
                            autoProgressDelay={4000}
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
                            <div className="flex items-center gap-4">
                                <ConnectionIndicator status={connectionStatus} />
                                {isHost && (
                                    <CancelGameButton roomCode={room.room_code} />
                                )}
                            </div>
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
                            allPlayersAnswered={all_players_answered}
                            currentUserHasAnswered={current_user_has_answered}
                            isReadyForNext={is_ready_for_next}
                            readySince={ready_since}
                            isHost={isHost}
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
