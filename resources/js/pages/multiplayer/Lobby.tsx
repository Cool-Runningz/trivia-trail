import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Plus, LogIn, Users } from 'lucide-react';
import { LobbyPageProps } from '@/types';
import { RoomBrowser } from '@/components/multiplayer/RoomBrowser';
import { CreateRoomModal } from '@/components/multiplayer/CreateRoomModal';
import { JoinRoomModal } from '@/components/multiplayer/JoinRoomModal';

export default function Lobby({ rooms, categories }: LobbyPageProps) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [joinModalOpen, setJoinModalOpen] = useState(false);

    return (
        <>
            <Head title="Multiplayer Lobby" />

            <div className="container mx-auto py-8 space-y-8">
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

                <Separator />

                {/* Available Rooms */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-semibold tracking-tight">Available Rooms</h2>
                            <p className="text-sm text-muted-foreground">
                                {rooms.length} {rooms.length === 1 ? 'room' : 'rooms'} waiting for players
                            </p>
                        </div>
                     
                    </div>

                    <RoomBrowser rooms={rooms} />
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
        </>
    );
}
