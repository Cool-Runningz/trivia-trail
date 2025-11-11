<?php

namespace App\Http\Requests;

use App\DifficultyLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can create games
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['required', Rule::enum(DifficultyLevel::class)],
            'total_questions' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.integer' => 'The category must be a valid number.',
            'category_id.min' => 'The category ID must be at least 1.',
            'difficulty.required' => 'The difficulty level is required.',
            'total_questions.required' => 'The number of questions is required.',
            'total_questions.integer' => 'The number of questions must be a valid number.',
            'total_questions.min' => 'You must request at least 1 question.',
            'total_questions.max' => 'You can request a maximum of 50 questions.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'difficulty' => 'difficulty level',
            'total_questions' => 'number of questions',
        ];
    }
}