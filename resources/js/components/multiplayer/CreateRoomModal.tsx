import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Category, CreateRoomFormData, DifficultyLevel } from '@/types';

interface CreateRoomModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    categories: Category[];
}

export function CreateRoomModal({ open, onOpenChange, categories }: CreateRoomModalProps) {
    const { data, setData, post, processing, errors, reset } = useForm<CreateRoomFormData>({
        difficulty: 'medium',
        total_questions: 10,
        max_players: 8,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('multiplayer.room.store'), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen) {
            reset();
        }
        onOpenChange(newOpen);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Create Room</DialogTitle>
                    <DialogDescription>
                        Configure your multiplayer trivia game settings
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="category">Category (Optional)</Label>
                        <Select
                            value={data.category_id?.toString() || 'any'}
                            onValueChange={(value) => 
                                setData('category_id', value === 'any' ? undefined : parseInt(value))
                            }
                        >
                            <SelectTrigger id="category">
                                <SelectValue placeholder="Any Category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="any">Any Category</SelectItem>
                                {categories.map((category) => (
                                    <SelectItem key={category.id} value={category.id.toString()}>
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.category_id && (
                            <p className="text-sm text-destructive">{errors.category_id}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="difficulty">Difficulty</Label>
                        <Select
                            value={data.difficulty}
                            onValueChange={(value) => setData('difficulty', value as DifficultyLevel)}
                        >
                            <SelectTrigger id="difficulty">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="easy">Easy</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="hard">Hard</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.difficulty && (
                            <p className="text-sm text-destructive">{errors.difficulty}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="total_questions">Number of Questions</Label>
                        <Select
                            value={data.total_questions.toString()}
                            onValueChange={(value) => setData('total_questions', parseInt(value))}
                        >
                            <SelectTrigger id="total_questions">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="5">5 Questions</SelectItem>
                                <SelectItem value="10">10 Questions</SelectItem>
                                <SelectItem value="15">15 Questions</SelectItem>
                                <SelectItem value="20">20 Questions</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.total_questions && (
                            <p className="text-sm text-destructive">{errors.total_questions}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="max_players">Max Players</Label>
                        <Select
                            value={data.max_players.toString()}
                            onValueChange={(value) => setData('max_players', parseInt(value))}
                        >
                            <SelectTrigger id="max_players">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="2">2 Players</SelectItem>
                                <SelectItem value="4">4 Players</SelectItem>
                                <SelectItem value="6">6 Players</SelectItem>
                                <SelectItem value="8">8 Players</SelectItem>
                                <SelectItem value="10">10 Players</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.max_players && (
                            <p className="text-sm text-destructive">{errors.max_players}</p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Room'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
