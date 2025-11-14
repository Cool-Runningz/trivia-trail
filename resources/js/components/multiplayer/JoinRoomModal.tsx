import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { RoomCodeInput } from './RoomCodeInput';

interface JoinRoomModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function JoinRoomModal({ open, onOpenChange }: JoinRoomModalProps) {
    const [roomCode, setRoomCode] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        setProcessing(true);
        setError('');
        
        router.post(
            route('multiplayer.room.join'),
            { room_code: roomCode },
            {
                onSuccess: () => {
                    setRoomCode('');
                    onOpenChange(false);
                },
                onError: (errors) => {
                    setError(errors.room_code || 'Failed to join room');
                },
                onFinish: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen) {
            setRoomCode('');
            setError('');
        }
        onOpenChange(newOpen);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Join Room</DialogTitle>
                    <DialogDescription>
                        Enter the 6-character room code to join a multiplayer game
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <RoomCodeInput
                        value={roomCode}
                        onChange={setRoomCode}
                        error={error}
                        autoFocus
                    />
                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || roomCode.length !== 6}
                        >
                            {processing ? 'Joining...' : 'Join Room'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
