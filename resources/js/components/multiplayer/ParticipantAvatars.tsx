import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { type ParticipantPreview } from '@/types';

interface ParticipantAvatarsProps {
    participants: ParticipantPreview[];
    total: number;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export function ParticipantAvatars({
    participants,
    total,
}: ParticipantAvatarsProps) {
    const displayParticipants = participants.slice(0, 3);
    const remaining = total - displayParticipants.length;

    return (
        <div className="flex -space-x-2">
            {displayParticipants.map((participant) => (
                <Avatar
                    key={participant.id}
                    className="h-8 w-8 border-2 border-background"
                >
                    <AvatarFallback className="text-xs">
                        {getInitials(participant.name)}
                    </AvatarFallback>
                </Avatar>
            ))}

            {remaining > 0 && (
                <Avatar className="h-8 w-8 border-2 border-background">
                    <AvatarFallback className="bg-muted text-xs">
                        +{remaining}
                    </AvatarFallback>
                </Avatar>
            )}
        </div>
    );
}
