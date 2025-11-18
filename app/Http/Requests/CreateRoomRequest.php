<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRoomRequest extends FormRequest
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
            // max_players is fixed at 8 and not user-configurable
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
            'time_per_question.min' => 'Time per question must be at least 10 seconds.',
            'time_per_question.max' => 'Time per question cannot exceed 60 seconds.',
            'total_questions.min' => 'A game must have at least 5 questions.',
            'total_questions.max' => 'A game cannot have more than 50 questions.',
        ];
    }
}
