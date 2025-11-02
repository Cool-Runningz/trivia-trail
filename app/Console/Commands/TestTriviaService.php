<?php

namespace App\Console\Commands;

use App\Services\OpenTriviaService;
use Illuminate\Console\Command;

class TestTriviaService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trivia:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the OpenTriviaService functionality';

    /**
     * Execute the console command.
     */
    public function handle(OpenTriviaService $triviaService)
    {
        $this->info('Testing OpenTriviaService...');
        
        // Test categories
        $this->info('Fetching categories...');
        $categories = $triviaService->getCategories();
        $this->info('Found ' . count($categories) . ' categories');
        
        if (!empty($categories)) {
            $this->table(['ID', 'Name'], array_slice($categories, 0, 5));
        }
        
        // Test questions
        $this->info('Fetching sample questions...');
        $questions = $triviaService->getQuestions([
            'amount' => 2,
            'difficulty' => 'easy'
        ]);
        
        if (isset($questions['error']) && $questions['error']) {
            $this->error('Error fetching questions: ' . $questions['message']);
            return;
        }
        
        $this->info('Found ' . count($questions) . ' questions');
        
        foreach ($questions as $index => $question) {
            $this->info("Question " . ($index + 1) . ": " . $question['question']);
            $this->info("Correct Answer: " . $question['correct_answer']);
            $this->info("Shuffled Options: " . implode(', ', $question['shuffled_answers']));
            $this->newLine();
        }
        
        $this->info('OpenTriviaService test completed successfully!');
    }
}
