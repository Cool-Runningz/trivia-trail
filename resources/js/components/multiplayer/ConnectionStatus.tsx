import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { 
    WifiOff, 
    RefreshCw,
    CheckCircle2 
} from 'lucide-react';
import { type ConnectionStatus as ConnectionStatusType } from '@/hooks/use-room-polling';
import { cn } from '@/lib/utils';

interface ConnectionStatusProps {
    status: ConnectionStatusType;
    lastUpdate?: Date | null;
    compact?: boolean;
}

export function ConnectionStatus({
    status,
    lastUpdate,
    compact = false,
}: ConnectionStatusProps) {
    const getStatusConfig = () => {
        switch (status) {
            case 'connected':
                return {
                    icon: CheckCircle2,
                    color: 'text-green-600',
                    bgColor: 'bg-green-50 dark:bg-green-950/20',
                    borderColor: 'border-green-200 dark:border-green-800',
                    label: 'Connected',
                    description: 'Real-time updates active',
                    showRetry: false,
                };
            case 'reconnecting':
                return {
                    icon: RefreshCw,
                    color: 'text-yellow-600',
                    bgColor: 'bg-yellow-50 dark:bg-yellow-950/20',
                    borderColor: 'border-yellow-200 dark:border-yellow-800',
                    label: 'Reconnecting',
                    description: 'Attempting to reconnect...',
                    showRetry: false,
                };
            case 'disconnected':
                return {
                    icon: WifiOff,
                    color: 'text-red-600',
                    bgColor: 'bg-red-50 dark:bg-red-950/20',
                    borderColor: 'border-red-200 dark:border-red-800',
                    label: 'Disconnected',
                    description: 'Connection lost. Please check your internet connection and refresh the page.',
                    showRetry: false,
                };
        }
    };

    const config = getStatusConfig();
    const Icon = config.icon;

    // Don't show anything if connected and compact mode
    if (status === 'connected' && compact) {
        return null;
    }

    // Compact badge version
    if (compact) {
        return (
            <Badge 
                variant="outline" 
                className={cn(
                    "gap-1.5",
                    config.bgColor,
                    config.borderColor
                )}
            >
                <Icon className={cn("h-3 w-3", config.color, status === 'reconnecting' && "animate-spin")} />
                <span className={config.color}>{config.label}</span>
            </Badge>
        );
    }

    // Full alert version
    return (
        <Alert className={cn(config.bgColor, config.borderColor)}>
            <div className="flex items-start gap-3">
                <Icon className={cn("h-5 w-5 mt-0.5", config.color, status === 'reconnecting' && "animate-spin")} />
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                        <span className={cn("font-semibold", config.color)}>
                            {config.label}
                        </span>
                        {lastUpdate && status === 'connected' && (
                            <span className="text-xs text-muted-foreground">
                                Last update: {lastUpdate.toLocaleTimeString()}
                            </span>
                        )}
                    </div>
                    <AlertDescription className="text-sm">
                        {config.description}
                    </AlertDescription>
                </div>
            </div>
        </Alert>
    );
}

interface ConnectionIndicatorProps {
    status: ConnectionStatusType;
    className?: string;
}

/**
 * Minimal connection indicator for headers/navbars
 */
export function ConnectionIndicator({ status, className }: ConnectionIndicatorProps) {
    const getIndicatorConfig = () => {
        switch (status) {
            case 'connected':
                return {
                    color: 'bg-green-500',
                    pulse: false,
                    tooltip: 'Connected',
                };
            case 'reconnecting':
                return {
                    color: 'bg-yellow-500',
                    pulse: true,
                    tooltip: 'Reconnecting...',
                };
            case 'disconnected':
                return {
                    color: 'bg-red-500',
                    pulse: true,
                    tooltip: 'Disconnected',
                };
        }
    };

    const config = getIndicatorConfig();

    return (
        <div className={cn("flex items-center gap-2", className)} title={config.tooltip}>
            <div className="relative">
                <div className={cn("h-2 w-2 rounded-full", config.color)} />
                {config.pulse && (
                    <div className={cn(
                        "absolute inset-0 h-2 w-2 rounded-full animate-ping",
                        config.color,
                        "opacity-75"
                    )} />
                )}
            </div>
            <span className="text-xs text-muted-foreground">
                {config.tooltip}
            </span>
        </div>
    );
}
