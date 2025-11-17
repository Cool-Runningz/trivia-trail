import { useState, useRef, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Copy, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

interface RoomCodeInputProps {
    value: string;
    onChange: (value: string) => void;
    error?: string;
    label?: string;
    showCopyButton?: boolean;
    disabled?: boolean;
    autoFocus?: boolean;
}

export function RoomCodeInput({
    value,
    onChange,
    error,
    label = 'Room Code',
    showCopyButton = false,
    disabled = false,
    autoFocus = false,
}: RoomCodeInputProps) {
    const [copied, setCopied] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (autoFocus && inputRef.current) {
            inputRef.current.focus();
        }
    }, [autoFocus]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const input = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (input.length <= 6) {
            onChange(input);
        }
    };

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    };

    const isValid = value.length === 6;

    return (
        <div className="space-y-2">
            {label && <Label htmlFor="room-code">{label}</Label>}
            <div className="flex gap-2">
                <Input
                    ref={inputRef}
                    id="room-code"
                    type="text"
                    value={value}
                    onChange={handleChange}
                    disabled={disabled}
                    placeholder="ABC123"
                    maxLength={6}
                    className={cn(
                        'font-mono text-2xl tracking-[0.5em] text-center uppercase',
                        error && 'border-destructive focus-visible:ring-destructive',
                        isValid && !error && 'border-green-500 focus-visible:ring-green-500'
                    )}
                    aria-invalid={!!error}
                    aria-describedby={error ? 'room-code-error' : undefined}
                />
                {showCopyButton && (
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        onClick={handleCopy}
                        className="shrink-0"
                    >
                        {copied ? (
                            <Check className="h-4 w-4 text-green-500" />
                        ) : (
                            <Copy className="h-4 w-4" />
                        )}
                        <span className="sr-only">Copy room code</span>
                    </Button>
                )}
            </div>
            {error && (
                <p id="room-code-error" className="text-sm text-destructive">
                    {error}
                </p>
            )}
            <p className="text-xs text-muted-foreground">
                Enter a 6-character alphanumeric code
            </p>
        </div>
    );
}
