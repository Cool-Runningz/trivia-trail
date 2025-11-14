<?php

namespace App\Http\Requests;

use App\Models\GameRoom;
use App\RoomStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $room = $this->route('room');

        // If room is passed as a string (room_code), find it
        if (is_string($room)) {
            $room = GameRoom::where('room_code', $room)->first();
        }

        if (!$room) {
            return false;
        }

        // Only the host can modify settings
        if ($room->host_user_id !== $this->user()->id) {
            return false;
        }

        // Settings can only be modified in waiting status
        if ($room->status !== RoomStatus::WAITING) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'max_players' => ['nullable', 'integer', 'min:2', 'max:20'],
            'time_per_question' => ['nullable', 'integer', 'min:10', 'max:60'],
            'scoring_mode' => ['nullable', Rule::in(['standard'])],
            'category_id' => ['nullable', 'integer'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'total_questions' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'max_players.min' => 'A room must have at least 2 players.',
            'max_players.max' => 'A room cannot have more than 20 players.',
            'time_per_question.min' => 'Time per question must be at least 10 seconds.',
            'time_per_question.max' => 'Time per question cannot exceed 60 seconds.',
            'total_questions.min' => 'A game must have at least 5 questions.',
            'total_questions.max' => 'A game cannot have more than 50 questions.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'max_players' => 'maximum players',
            'time_per_question' => 'time per question',
            'scoring_mode' => 'scoring mode',
            'category_id' => 'category',
            'total_questions' => 'number of questions',
        ];
    }
}
