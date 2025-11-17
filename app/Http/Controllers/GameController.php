<?php

namespace App\Http\Controllers;

use App\GameStatus;
use App\Http\Requests\AnswerRequest;
use App\Http\Requests\GameStoreRequest;
use App\Models\Game;
use App\Models\PlayerAnswer;
use App\Services\OpenTriviaService;
use App\Utilities\GameUtilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __construct(
        private OpenTriviaService $triviaService
    ) {}

    /**
     * Show the game setup page
     *
     * @return Response
     */
    public function setup(): Response
    {
        $categories = $this->triviaService->getCategories();
        
        return Inertia::render('game/setup', [
            'categories' => $categories
        ]);
    }

    /**
     * Create a new game with questions from OpenTriviaService
     *
     * @param GameStoreRequest $request
     * @return RedirectResponse
     */
    public function store(GameStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Prepare parameters for OpenTriviaService
        $questionParams = [
            'amount' => $validated['total_questions'],
            'difficulty' => $validated['difficulty']
        ];
        
        // Add category if specified
        if (!empty($validated['category_id'])) {
            $questionParams['category'] = $validated['category_id'];
        }
        
        // Fetch questions from OpenTriviaService
        $questionsResponse = $this->triviaService->getQuestions($questionParams);
        
        // Check if there was an error fetching questions
        if (isset($questionsResponse['error']) && $questionsResponse['error']) {
            return back()->withErrors([
                'questions' => $questionsResponse['message'] ?? 'Failed to fetch questions. Please try again.'
            ]);
        }
        
        // Ensure we have questions
        if (empty($questionsResponse) || count($questionsResponse) === 0) {
            return back()->withErrors([
                'questions' => 'No questions available for the selected parameters. Please try different settings.'
            ]);
        }
        
        // Create the game record with questions stored in JSON field
        $game = Game::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'difficulty' => $validated['difficulty'],
            'total_questions' => count($questionsResponse), // Use actual count from API
            'current_question_index' => 0,
            'score' => 0,
            'status' => GameStatus::Active,
            'questions' => $questionsResponse, // Store questions temporarily in JSON field
            'started_at' => now(),
        ]);
        
        // Redirect to the game play page
        return redirect()->route('game.show', $game);
    }

    /**
     * Show the current question for an active game
     *
     * @param Game $game
     * @return Response|RedirectResponse
     */
    public function show(Game $game): Response|RedirectResponse
    {
        // Ensure the game belongs to the authenticated user
        if ($game->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to game.');
        }
        
        // If game is completed, redirect to results
        if ($game->isCompleted()) {
            return redirect()->route('game.results', $game);
        }
        
        // Get current question
        $currentQuestion = $game->currentQuestion();
        
        // If no current question available, something went wrong
        if (!$currentQuestion) {
            return redirect()->route('game.setup')->withErrors([
                'game' => 'No questions available for this game. Please start a new game.'
            ]);
        }
        
        // Calculate progress information
        $progress = [
            'current' => $game->current_question_index + 1,
            'total' => $game->total_questions,
            'percentage' => round((($game->current_question_index + 1) / $game->total_questions) * 100, 1)
        ];
        
        return Inertia::render('game/play', [
            'game' => [
                'id' => $game->id,
                'score' => $game->score,
                'current_question_index' => $game->current_question_index,
                'total_questions' => $game->total_questions,
                'difficulty' => $game->difficulty->value,
                'status' => $game->status->value,
            ],
            'question' => $currentQuestion,
            'progress' => $progress,
        ]);
    }

    /**
     * Process player answer submission
     *
     * @param Game $game
     * @param AnswerRequest $request
     * @return RedirectResponse
     */
    public function answer(Game $game, AnswerRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $questionIndex = $validated['question_index'];
        $selectedAnswer = $validated['selected_answer'];
        
        // Get the question from the game's questions array
        $question = $game->questions[$questionIndex];
        $correctAnswer = $question['correct_answer'];
        $isCorrect = GameUtilities::isAnswerCorrect($selectedAnswer, $correctAnswer);
        
        // Calculate points based on difficulty using shared utility
        $pointsEarned = GameUtilities::calculatePoints($game->difficulty, $isCorrect);
        
        // Create PlayerAnswer record
        PlayerAnswer::create([
            'game_id' => $game->id,
            'question_index' => $questionIndex,
            'question' => $question['question'],
            'selected_answer' => $selectedAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'answered_at' => now(),
        ]);
        
        // Update game score and progress
        $game->increment('score', $pointsEarned);
        $game->increment('current_question_index');
        
        // Check if game is completed
        $isGameCompleted = $game->current_question_index >= $game->total_questions;
        if ($isGameCompleted) {
            $game->update([
                'status' => GameStatus::Completed,
                'completed_at' => now(),
            ]);
        }
        
        // Redirect based on game completion status
        if ($isGameCompleted) {
            // Game is complete, redirect to results
            return redirect()->route('game.results', $game);
        } else {
            // Continue to next question
            return redirect()->route('game.show', $game);
        }
    }

    /**
     * Show game results and final statistics
     *
     * @param Game $game
     * @return Response|RedirectResponse
     */
    public function results(Game $game): Response|RedirectResponse
    {
        // Ensure the game belongs to the authenticated user
        if ($game->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to game.');
        }
        
        // Only allow access to completed games
        if (!$game->isCompleted()) {
            return redirect()->route('game.show', $game)->withErrors([
                'game' => 'This game is not yet completed.'
            ]);
        }
        
        // Calculate final statistics
        $finalScore = $game->calculateFinalScore();
        $correctAnswers = $game->getCorrectAnswersCount();
        $percentageScore = $game->getPercentageScore();
        
        // Get detailed answer breakdown
        $answerBreakdown = $game->playerAnswers()
            ->orderBy('question_index')
            ->get()
            ->map(function ($answer) {
                return [
                    'question' => $answer->question,
                    'selected_answer' => $answer->selected_answer,
                    'correct_answer' => $answer->correct_answer,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                ];
            });
        
        // Calculate time taken
        $timeTaken = null;
        if ($game->started_at && $game->completed_at) {
            $timeTaken = $game->started_at->diffInMinutes($game->completed_at);
        }
        
        return Inertia::render('game/results', [
            'game' => [
                'id' => $game->id,
                'difficulty' => $game->difficulty->value,
                'total_questions' => $game->total_questions,
                'started_at' => $game->started_at?->format('Y-m-d H:i:s'),
                'completed_at' => $game->completed_at?->format('Y-m-d H:i:s'),
                'time_taken_minutes' => $timeTaken,
            ],
            'results' => [
                'final_score' => $finalScore,
                'correct_answers' => $correctAnswers,
                'total_questions' => $game->total_questions,
                'percentage_score' => $percentageScore,
                'answer_breakdown' => $answerBreakdown,
            ],
        ]);
    }

    /**
     * Show the game play page (legacy method - redirects to setup)
     *
     * @return RedirectResponse
     */
    public function play(): RedirectResponse
    {
        return redirect()->route('game.setup');
    }
}