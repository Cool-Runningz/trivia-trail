<?php

namespace App\Http\Requests;

use App\GameStatus;
use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can submit answers
        if (!auth()->check()) {
            return false;
        }

        // Get the game from the route parameter
        $game = $this->route('game');
        
        if (!$game instanceof Game) {
            return false;
        }

        // User must own the game
        return $game->user_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selected_answer' => ['required', 'string', 'max:500'],
            'question_index' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $game = $this->route('game');
            
            if (!$game instanceof Game) {
                $validator->errors()->add('game', 'Invalid game.');
                return;
            }

            // Validate game is active
            if ($game->status !== GameStatus::Active) {
                $validator->errors()->add('game', 'This game is no longer active.');
                return;
            }

            $questionIndex = $this->input('question_index');
            
            // Validate question exists in the game
            if (!$game->questions || !isset($game->questions[$questionIndex])) {
                $validator->errors()->add('question_index', 'Invalid question index.');
                return;
            }

            // Validate question hasn't been answered already
            $existingAnswer = $game->playerAnswers()
                ->where('question_index', $questionIndex)
                ->exists();
                
            if ($existingAnswer) {
                $validator->errors()->add('question_index', 'This question has already been answered.');
                return;
            }

            // Validate the selected answer is one of the valid options for this question
            $question = $game->questions[$questionIndex];
            $validAnswers = array_merge(
                [$question['correct_answer']],
                $question['incorrect_answers'] ?? []
            );

            if (!in_array($this->input('selected_answer'), $validAnswers, true)) {
                $validator->errors()->add('selected_answer', 'Invalid answer selection.');
            }
        });
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selected_answer.required' => 'You must select an answer.',
            'selected_answer.string' => 'The selected answer must be text.',
            'selected_answer.max' => 'The selected answer is too long.',
            'question_index.required' => 'Question index is required.',
            'question_index.integer' => 'Question index must be a valid number.',
            'question_index.min' => 'Question index must be at least 0.',
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
            'selected_answer' => 'answer',
            'question_index' => 'question',
        ];
    }
}