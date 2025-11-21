import { useState, useEffect, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Plus, LogIn, Users } from 'lucide-react';
import { type BreadcrumbItem, type LobbyPageProps } from '@/types';
import { RoomBrowser } from '@/components/multiplayer/RoomBrowser';
import { CreateRoomModal } from '@/components/multiplayer/CreateRoomModal';
import { JoinRoomModal } from '@/components/multiplayer/JoinRoomModal';
import { GameHistory } from '@/components/multiplayer/GameHistory';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import lobby from '@/routes/lobby';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Multiplayer Lobby',
        href: lobby.index().url,
    },
];

export default function Lobby({ activeGames, categories, gameHistory }: LobbyPageProps) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [joinModalOpen, setJoinModalOpen] = useState(false);
    const shownFlashMessages = useRef<Set<string>>(new Set());
    const { flash } = usePage<any>().props;

    // Show toast notifications for flash messages (prevent duplicates during polling)
    useEffect(() => {
        if (flash?.success && !shownFlashMessages.current.has(flash.success)) {
            toast.success(flash.success);
            shownFlashMessages.current.add(flash.success);
        }
        if (flash?.info && !shownFlashMessages.current.has(flash.info)) {
            toast.info(flash.info);
            shownFlashMessages.current.add(flash.info);
        }
        if (flash?.error && !shownFlashMessages.current.has(flash.error)) {
            toast.error(flash.error);
            shownFlashMessages.current.add(flash.error);
        }
    }, [flash]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Multiplayer Lobby" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="space-y-8">
                {/* Header */}
                <div className="space-y-2">
                    <div className="flex items-center gap-3">
                        <Users className="h-8 w-8" />
                        <h1 className="text-3xl font-bold tracking-tight">Multiplayer Lobby</h1>
                    </div>
                    <p className="text-muted-foreground">
                        Join an existing room or create your own to play trivia with friends
                    </p>
                </div>

                {/* Action Cards */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card className="cursor-pointer hover:border-primary transition-colors" onClick={() => setCreateModalOpen(true)}>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-primary/10">
                                    <Plus className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle>Create Room</CardTitle>
                                    <CardDescription>Start a new multiplayer game</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Configure game settings and invite friends to join your room
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer hover:border-primary transition-colors" onClick={() => setJoinModalOpen(true)}>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-primary/10">
                                    <LogIn className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle>Join Room</CardTitle>
                                    <CardDescription>Enter a room code to join</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Have a room code? Join an existing game with your friends
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Your Active Games */}
                {activeGames && activeGames.length > 0 && (
                    <>
                        <Separator />
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-2xl font-semibold tracking-tight">Your Active Games</h2>
                                    <p className="text-sm text-muted-foreground">
                                        {activeGames.length} {activeGames.length === 1 ? 'game' : 'games'} in progress
                                    </p>
                                </div>
                            </div>

                            <RoomBrowser rooms={activeGames} />
                        </div>
                    </>
                )}

                {/* Game History */}
                <Separator />
                <div className="space-y-4">
                    <GameHistory gameHistory={gameHistory} />
                </div>

              
                </div>
            </div>

            {/* Modals */}
            <CreateRoomModal
                open={createModalOpen}
                onOpenChange={setCreateModalOpen}
                categories={categories}
            />
            <JoinRoomModal
                open={joinModalOpen}
                onOpenChange={setJoinModalOpen}
            />
        </AppLayout>
    );
}
