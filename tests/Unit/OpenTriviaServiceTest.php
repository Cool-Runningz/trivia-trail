<?php

uses(Tests\TestCase::class);

use App\Services\OpenTriviaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new OpenTriviaService();
    Cache::flush();
});

test('getCategories returns formatted categories from API', function () {
    // Mock successful API response
    Http::fake([
        'https://opentdb.com/api_category.php' => Http::response([
            'trivia_categories' => [
                ['id' => 9, 'name' => 'General Knowledge'],
                ['id' => 10, 'name' => 'Entertainment: Books'],
            ]
        ])
    ]);

    $categories = $this->service->getCategories();

    expect($categories)->toHaveCount(2)
        ->and($categories[0])->toHaveKeys(['id', 'name'])
        ->and($categories[0]['id'])->toBe(9)
        ->and($categories[0]['name'])->toBe('General Knowledge');
});

test('getCategories caches results for one hour', function () {
    Http::fake([
        'https://opentdb.com/api_category.php' => Http::response([
            'trivia_categories' => [
                ['id' => 9, 'name' => 'General Knowledge'],
            ]
        ])
    ]);

    // First call
    $this->service->getCategories();
    
    // Second call should use cache
    $this->service->getCategories();

    // Should only make one HTTP request
    Http::assertSentCount(1);
});

test('getCategories returns fallback categories on API failure', function () {
    Http::fake([
        'https://opentdb.com/api_category.php' => Http::response([], 500)
    ]);

    $categories = $this->service->getCategories();

    expect($categories)->toBeArray()
        ->and($categories)->not->toBeEmpty()
        ->and($categories[0])->toHaveKeys(['id', 'name']);
});

test('getQuestions returns processed questions from API', function () {
    Http::fake([
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'What is 2 + 2?',
                    'correct_answer' => '4',
                    'incorrect_answers' => ['3', '5', '6'],
                    'difficulty' => 'easy',
                    'category' => 'Mathematics'
                ]
            ]
        ])
    ]);

    $questions = $this->service->getQuestions(['amount' => 1]);

    expect($questions)->toHaveCount(1)
        ->and($questions[0])->toHaveKeys(['question', 'correct_answer', 'incorrect_answers', 'shuffled_answers'])
        ->and($questions[0]['shuffled_answers'])->toHaveCount(4);
});

test('getQuestions validates parameters correctly', function () {
    Http::fake([
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => []
        ])
    ]);

    $this->service->getQuestions([
        'amount' => 100, // Should be capped at 50
        'category' => 9,
        'difficulty' => 'easy'
    ]);

    Http::assertSent(function ($request) {
        $url = $request->url();
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return isset($params['amount']) && $params['amount'] == 50;
    });
});

test('getQuestions decodes HTML entities', function () {
    Http::fake([
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'What&#039;s the capital of France?',
                    'correct_answer' => 'Paris &amp; Love',
                    'incorrect_answers' => ['London &lt;city&gt;', 'Berlin', 'Madrid'],
                    'difficulty' => 'easy',
                    'category' => 'Geography'
                ]
            ]
        ])
    ]);

    $questions = $this->service->getQuestions(['amount' => 1]);

    expect($questions[0]['question'])->toBe("What's the capital of France?")
        ->and($questions[0]['correct_answer'])->toBe('Paris & Love')
        ->and($questions[0]['incorrect_answers'][0])->toBe('London <city>');
});

test('getQuestions shuffles answers', function () {
    Http::fake([
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 0,
            'results' => [
                [
                    'question' => 'Test question?',
                    'correct_answer' => 'Correct',
                    'incorrect_answers' => ['Wrong1', 'Wrong2', 'Wrong3'],
                    'difficulty' => 'easy',
                    'category' => 'Test'
                ]
            ]
        ])
    ]);

    $questions = $this->service->getQuestions(['amount' => 1]);

    expect($questions[0]['shuffled_answers'])->toHaveCount(4)
        ->and($questions[0]['shuffled_answers'])->toContain('Correct')
        ->and($questions[0]['shuffled_answers'])->toContain('Wrong1')
        ->and($questions[0]['shuffled_answers'])->toContain('Wrong2')
        ->and($questions[0]['shuffled_answers'])->toContain('Wrong3');
});

test('getQuestions handles API errors gracefully', function () {
    Http::fake([
        'https://opentdb.com/api.php*' => Http::response([], 500)
    ]);

    $result = $this->service->getQuestions(['amount' => 1]);

    expect($result)->toHaveKeys(['error', 'message', 'questions'])
        ->and($result['error'])->toBeTrue()
        ->and($result['questions'])->toBeArray();
});

test('getQuestions handles API response codes', function () {
    Http::fake([
        'https://opentdb.com/api.php*' => Http::response([
            'response_code' => 1, // No results
            'results' => []
        ])
    ]);

    $result = $this->service->getQuestions(['amount' => 1]);

    expect($result)->toHaveKeys(['error', 'message', 'questions'])
        ->and($result['error'])->toBeTrue();
});