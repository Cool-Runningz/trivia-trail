<?php

namespace App\Utilities;

use App\DifficultyLevel;

class GameUtilities
{
    /**
     * Calculate points earned for a correct answer based on difficulty
     *
     * @param DifficultyLevel $difficulty
     * @param bool $isCorrect
     * @return int
     */
    public static function calculatePoints(DifficultyLevel $difficulty, bool $isCorrect): int
    {
        if (!$isCorrect) {
            return 0;
        }

        return match ($difficulty) {
            DifficultyLevel::Easy => 10,
            DifficultyLevel::Medium => 20,
            DifficultyLevel::Hard => 30,
        };
    }

    /**
     * Calculate accuracy percentage
     *
     * @param int $correctAnswers
     * @param int $totalQuestions
     * @return float
     */
    public static function calculateAccuracy(int $correctAnswers, int $totalQuestions): float
    {
        if ($totalQuestions === 0) {
            return 0.0;
        }

        return round(($correctAnswers / $totalQuestions) * 100, 1);
    }

    /**
     * Shuffle answers for a question (if not already shuffled)
     *
     * @param array $question
     * @return array
     */
    public static function ensureShuffledAnswers(array $question): array
    {
        // If already shuffled, return as is
        if (isset($question['shuffled_answers'])) {
            return $question;
        }

        // If no correct/incorrect answers, return as is
        if (!isset($question['correct_answer']) || !isset($question['incorrect_answers'])) {
            return $question;
        }

        // Combine all answers
        $allAnswers = array_merge(
            [$question['correct_answer']],
            $question['incorrect_answers']
        );

        // Shuffle the combined answers
        shuffle($allAnswers);

        // Add shuffled answers to question
        $question['shuffled_answers'] = $allAnswers;

        return $question;
    }

    /**
     * Validate if an answer is correct
     *
     * @param string $selectedAnswer
     * @param string $correctAnswer
     * @return bool
     */
    public static function isAnswerCorrect(string $selectedAnswer, string $correctAnswer): bool
    {
        // Trim and compare case-sensitively (as trivia answers are case-sensitive)
        return trim($selectedAnswer) === trim($correctAnswer);
    }

    /**
     * Format time duration in minutes and seconds
     *
     * @param int $seconds
     * @return string
     */
    public static function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%d min %d sec', $minutes, $remainingSeconds);
        }

        return sprintf('%d sec', $remainingSeconds);
    }

    /**
     * Calculate response time bonus (for future scoring enhancements)
     *
     * @param int $responseTimeMs
     * @param int $maxTimeMs
     * @return float Multiplier between 0 and 1
     */
    public static function calculateSpeedBonus(int $responseTimeMs, int $maxTimeMs): float
    {
        if ($responseTimeMs >= $maxTimeMs) {
            return 0.0;
        }

        // Linear bonus: faster = higher multiplier
        return round(1.0 - ($responseTimeMs / $maxTimeMs), 2);
    }
}
