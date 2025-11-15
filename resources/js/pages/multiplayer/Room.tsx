import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Play, LogOut, AlertCircle } from 'lucide-react';
import { type BreadcrumbItem, type RoomLobbyProps } from '@/types';
import { 
    ParticipantList, 
    RoomSettingsPanel, 
    RoomCodeInput,
    ConnectionStatus,
    ConnectionIndicator,
    MultiplayerErrorBoundary 
} from '@/components/multiplayer';
import { useRoomPolling } from '@/hooks/use-room-polling';
import { useState, useEffect } from 'react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import lobby from '@/routes/lobby';
import multiplayer from '@/routes/multiplayer';

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
        title: 'Room Lobby',
        href: '#',
    },
];

export default function RoomLobby({ room, participants, isHost, canStart }: RoomLobbyProps) {
    const [isLeaving, setIsLeaving] = useState(false);
    const [isStarting, setIsStarting] = useState(false);

    // Use polling hook for real-time updates
    // Keep polling enabled so we can detect when game starts
    const { connectionStatus, lastUpdate } = useRoomPolling(
        'lobby',
        true // Always poll while on this page
    );

    // Redirect to game when room becomes active
    useEffect(() => {
        console.log('Room status:', room.status);
        if (room.status === 'active') {
            console.log('Redirecting to game page...');
            router.visit(multiplayer.game.show(room.room_code).url);
        }
    }, [room.status, room.room_code]);

    const handleLeave = () => {
        if (confirm('Are you sure you want to leave this room?')) {
            setIsLeaving(true);
            router.post(
                multiplayer.room.leave(room.room_code).url,
                {},
                {
                    onFinish: () => setIsLeaving(false),
                }
            );
        }
    };

    const handleStart = () => {
        setIsStarting(true);
        router.post(
            multiplayer.room.start(room.room_code).url,
            {},
            {
                onFinish: () => setIsStarting(false),
            }
        );
    };

    const minPlayers = 2;
    const hasEnoughPlayers = participants.length >= minPlayers;
    const isGameStarting = room.status === 'active';

    // Redirect to game page when game is active
    useEffect(() => {
        if (isGameStarting) {
            router.visit(multiplayer.game.show(room.room_code).url);
        }
    }, [isGameStarting, room.room_code]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <MultiplayerErrorBoundary>
                <Head title={`Room ${room.room_code}`} />

                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="space-y-6">
                        {/* Header */}
                        <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-3xl font-bold tracking-tight">Room Lobby</h1>
                        <p className="text-muted-foreground">
                            Waiting for players to join...
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <ConnectionIndicator status={connectionStatus} />
                        <Button
                            variant="outline"
                            onClick={handleLeave}
                            disabled={isLeaving || isStarting}
                        >
                            <LogOut className="h-4 w-4 mr-2" />
                            Leave Room
                        </Button>
                    </div>
                        </div>

                        {/* Connection Status */}
                        <ConnectionStatus
                    status={connectionStatus}
                    lastUpdate={lastUpdate}
                    compact
                        />

                        {/* Room Code Display */}
                        <Card>
                    <CardHeader>
                        <CardTitle>Room Code</CardTitle>
                        <CardDescription>
                            Share this code with friends to invite them
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="max-w-md">
                            <RoomCodeInput
                                value={room.room_code}
                                onChange={() => {}}
                                disabled
                                showCopyButton
                                label=""
                            />
                        </div>
                    </CardContent>
                        </Card>

                        <div className="grid gap-6 lg:grid-cols-3">
                            {/* Left Column - Participants */}
                            <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    Players ({participants.length} / {room.max_players})
                                </CardTitle>
                                <CardDescription>
                                    {isHost ? 'You are the host' : `Hosted by ${room.host?.name}`}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ParticipantList
                                    participants={participants}
                                    hostUserId={room.host_user_id}
                                />
                            </CardContent>
                            </Card>

                            {/* Host Controls */}
                            {isHost && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Host Controls</CardTitle>
                                    <CardDescription>
                                        Start the game when ready
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {!hasEnoughPlayers && (
                                        <Alert>
                                            <AlertCircle className="h-4 w-4" />
                                            <AlertDescription>
                                                Need at least {minPlayers} players to start the game.
                                                Currently have {participants.length}.
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <Button
                                        onClick={handleStart}
                                        disabled={!canStart || !hasEnoughPlayers || isStarting}
                                        className="w-full"
                                        size="lg"
                                    >
                                        <Play className="h-5 w-5 mr-2" />
                                        {isStarting ? 'Starting Game...' : 'Start Game'}
                                    </Button>
                                </CardContent>
                            </Card>
                            )}

                            {/* Non-host waiting message */}
                            {!isHost && (
                            <Alert>
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    Waiting for the host to start the game...
                                </AlertDescription>
                            </Alert>
                            )}
                            </div>

                            {/* Right Column - Settings */}
                            <div className="space-y-4">
                        <RoomSettingsPanel
                            settings={room.settings}
                            maxPlayers={room.max_players}
                            currentPlayers={participants.length}
                            />
                            </div>
                        </div>
                    </div>
                </div>
            </MultiplayerErrorBoundary>
        </AppLayout>
    );
}
