<?php

namespace App\Utilities;

use App\Models\GameRoom;
use Illuminate\Support\Str;

class RoomCodeGenerator
{
    /**
     * Generate a unique 6-character alphanumeric room code.
     */
    public static function generate(): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $code = self::generateCode();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Failed to generate unique room code after ' . $maxAttempts . ' attempts');
            }
        } while (self::exists($code));

        return $code;
    }

    /**
     * Generate a random 6-character alphanumeric code.
     */
    private static function generateCode(): string
    {
        // Use uppercase letters and numbers (excluding similar looking characters: 0, O, I, 1)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    /**
     * Check if a room code already exists in the database.
     */
    public static function exists(string $code): bool
    {
        return GameRoom::where('room_code', self::format($code))->exists();
    }

    /**
     * Validate room code format (6 alphanumeric characters).
     */
    public static function isValidFormat(string $code): bool
    {
        return preg_match('/^[A-Z0-9]{6}$/i', $code) === 1;
    }

    /**
     * Format room code to uppercase for consistency.
     */
    public static function format(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Format room code for display with optional separator.
     */
    public static function formatForDisplay(string $code, string $separator = '-'): string
    {
        $formatted = self::format($code);
        
        if (strlen($formatted) === 6) {
            return substr($formatted, 0, 3) . $separator . substr($formatted, 3, 3);
        }

        return $formatted;
    }
}
