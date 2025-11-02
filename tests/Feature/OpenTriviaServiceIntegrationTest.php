<?php

use App\Services\OpenTriviaService;
use Illuminate\Support\Facades\Http;

test('OpenTriviaService can be resolved from container', function () {
    $service = app(OpenTriviaService::class);
    
    expect($service)->toBeInstanceOf(OpenTriviaService::class);
});

test('OpenTriviaService integration with real API structure', function () {
    // Mock a realistic API response structure
    Http::fake([
        'https://opentdb.com/api_category.php' => Http::response([
            'trivia_categories' => [
                ['id' => 9, 'name' => 'General Knowledge'],
                ['id' => 10, 'name' => 'Entertainment: Books'],
                ['id' => 11, 'name' => 'Entertainment: Film'],
            ]
        ]),
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'category' => 'General Knowledge',
                    'type' => 'multiple',
                    'difficulty' => 'easy',
                    'question' => 'What does &quot;www&quot; stand for in a website browser?',
                    'correct_answer' => 'World Wide Web',
                    'incorrect_answers' => [
                        'Wide World Web',
                        'Web World Wide',
                        'World Web Wide'
                    ]
                ]
            ]
        ])
    ]);

    $service = app(OpenTriviaService::class);
    
    // Test categories
    $categories = $service->getCategories();
    expect($categories)->toBeArray()
        ->and($categories)->toHaveCount(3)
        ->and($categories[0])->toHaveKeys(['id', 'name']);
    
    // Test questions
    $questions = $service->getQuestions(['amount' => 1, 'category' => 9]);
    expect($questions)->toBeArray()
        ->and($questions)->toHaveCount(1)
        ->and($questions[0])->toHaveKeys(['question', 'correct_answer', 'incorrect_answers', 'shuffled_answers'])
        ->and($questions[0]['question'])->toBe('What does "www" stand for in a website browser?')
        ->and($questions[0]['shuffled_answers'])->toHaveCount(4);
});