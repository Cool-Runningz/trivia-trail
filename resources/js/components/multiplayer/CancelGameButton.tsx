import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { XCircle } from 'lucide-react';
import multiplayer from '@/routes/multiplayer';

interface CancelGameButtonProps {
    roomCode: string;
}

export function CancelGameButton({ roomCode }: CancelGameButtonProps) {
    const [isDeleting, setIsDeleting] = useState(false);

    const handleCancel = () => {
        if (confirm('Cancel this game? This will end the game for all players and delete the room. This action cannot be undone.')) {
            setIsDeleting(true);
            router.delete(multiplayer.room.destroy(roomCode).url, {
                onFinish: () => setIsDeleting(false),
            });
        }
    };

    return (
        <Button 
            variant="destructive" 
            size="sm" 
            onClick={handleCancel}
            disabled={isDeleting}
        >
            <XCircle className="h-4 w-4 mr-2" />
            {isDeleting ? 'Cancelling...' : 'Cancel Game'}
        </Button>
    );
}
