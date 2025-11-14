<?php

namespace App\Http\Requests;

use App\Models\GameRoom;
use App\RoomStatus;
use App\Utilities\RoomCodeGenerator;
use Illuminate\Foundation\Http\FormRequest;

class JoinRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated (handled by middleware)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'room_code' => [
                'required',
                'string',
                'size:6',
                'alpha_num',
                function ($attribute, $value, $fail) {
                    $formattedCode = RoomCodeGenerator::format($value);
                    $room = GameRoom::where('room_code', $formattedCode)->first();

                    if (!$room) {
                        $fail('The room code is invalid or the room does not exist.');
                        return;
                    }

                    if ($room->status !== RoomStatus::WAITING) {
                        $fail('This room is no longer accepting new participants.');
                        return;
                    }

                    if ($room->current_players >= $room->max_players) {
                        $fail('This room is full.');
                        return;
                    }

                    if ($room->participants()->where('user_id', $this->user()->id)->exists()) {
                        $fail('You are already in this room.');
                        return;
                    }

                    if ($room->expires_at < now()) {
                        $fail('This room has expired.');
                        return;
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'room_code.required' => 'Please enter a room code.',
            'room_code.size' => 'Room code must be exactly 6 characters.',
            'room_code.alpha_num' => 'Room code must contain only letters and numbers.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('room_code')) {
            $this->merge([
                'room_code' => strtoupper(trim($this->input('room_code'))),
            ]);
        }
    }
}
