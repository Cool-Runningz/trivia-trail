<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:50'],
            'category' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard', 'mixed'])],
            'type' => ['nullable', Rule::in(['multiple', 'boolean'])]
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
            'amount.required' => 'The number of questions is required.',
            'amount.integer' => 'The number of questions must be a valid number.',
            'amount.min' => 'You must request at least 1 question.',
            'amount.max' => 'You can request a maximum of 50 questions.',
            'category.integer' => 'The category must be a valid number.',
            'category.min' => 'The category ID must be at least 1.',
            'difficulty.in' => 'The difficulty must be one of: easy, medium, hard, or mixed.',
            'type.in' => 'The question type must be either multiple choice or true/false.'
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
            'amount' => 'number of questions',
            'category' => 'category',
            'difficulty' => 'difficulty level',
            'type' => 'question type'
        ];
    }
}