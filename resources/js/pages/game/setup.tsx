import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type Category, type DifficultyLevel, type GameSetupPageProps } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'New Game',
        href: '#',
    },
];

export default function GameSetup({ categories }: GameSetupPageProps) {
    const [selectedCategory, setSelectedCategory] = useState<string>('all');
    const [selectedDifficulty, setSelectedDifficulty] = useState<DifficultyLevel>('easy');
    const [questionCount, setQuestionCount] = useState<number>(10);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Setup New Game" />
            
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="mx-auto w-full max-w-2xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>Setup Your Trivia Game</CardTitle>
                            <CardDescription>
                                Choose your preferences to create a personalized trivia experience
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action="/game"
                                method="post"
                                className="flex flex-col gap-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        {/* Hidden inputs for complex React components only */}
                                        <input 
                                            type="hidden" 
                                            name="category_id" 
                                            value={selectedCategory === 'all' ? '' : selectedCategory} 
                                        />
                                        <input 
                                            type="hidden" 
                                            name="difficulty" 
                                            value={selectedDifficulty} 
                                        />
                                        
                                        <div className="grid gap-6">
                                            {/* Category Selection */}
                                            <div className="grid gap-2">
                                                <Label htmlFor="category_id">Category</Label>
                                                <Select
                                                    value={selectedCategory}
                                                    onValueChange={setSelectedCategory}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select a category or leave blank for all categories" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all">All Categories</SelectItem>
                                                        {categories.map((category: Category) => (
                                                            <SelectItem 
                                                                key={category.id} 
                                                                value={category.id.toString()}
                                                            >
                                                                {category.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.category_id} />
                                            </div>

                                            {/* Difficulty Selection */}
                                            <div className="grid gap-2">
                                                <Label htmlFor="difficulty">Difficulty Level</Label>
                                                <Select
                                                    value={selectedDifficulty}
                                                    onValueChange={(value) => setSelectedDifficulty(value as DifficultyLevel)}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select difficulty level" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="easy">Easy (10 points)</SelectItem>
                                                        <SelectItem value="medium">Medium (20 points)</SelectItem>
                                                        <SelectItem value="hard">Hard (30 points)</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={errors.difficulty} />
                                            </div>

                                            {/* Question Count */}
                                            <div className="grid gap-2">
                                                <Label htmlFor="total_questions">Number of Questions</Label>
                                                <Input
                                                    id="total_questions"
                                                    name="total_questions"
                                                    type="number"
                                                    min="1"
                                                    max="50"
                                                    value={questionCount}
                                                    onChange={(e) => setQuestionCount(parseInt(e.target.value) || 1)}
                                                    placeholder="Enter number of questions (1-50)"
                                                />
                                                <InputError message={errors.total_questions} />
                                                <p className="text-sm text-muted-foreground">
                                                    Choose between 1 and 50 questions for your game
                                                </p>
                                            </div>
                                        </div>

                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Start Game
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}