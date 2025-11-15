import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import game from '@/routes/game';
import lobby from '@/routes/lobby';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Gamepad2, Users, Trophy, Clock, Zap, ArrowRight } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4 md:p-8">
                {/* Hero Section */}
                <div className="text-center space-y-4">
                    <div className="flex items-center justify-center gap-3">
                        <Trophy className="h-12 w-12 text-primary" />
                        <h1 className="text-4xl font-bold tracking-tight">Trivia Trail</h1>
                    </div>
                    <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
                        Test your knowledge solo or challenge friends in real-time multiplayer trivia battles
                    </p>
                </div>

                {/* Game Mode Selection */}
                <div className="grid gap-6 md:grid-cols-2 max-w-5xl mx-auto w-full">
                    {/* Single Player Card */}
                    <Card className="relative overflow-hidden border-2 hover:border-primary transition-all hover:shadow-lg group">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform" />
                        <CardHeader>
                            <div className="flex items-center gap-3 mb-2">
                                <div className="p-3 rounded-lg bg-primary/10">
                                    <Gamepad2 className="h-8 w-8 text-primary" />
                                </div>
                                <div>
                                    <CardTitle className="text-2xl">Single Player</CardTitle>
                                    <CardDescription>Play at your own pace</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Challenge yourself with trivia questions across multiple categories and difficulty levels. 
                                Track your progress and improve your knowledge.
                            </p>
                            
                            <div className="space-y-2">
                                <div className="flex items-center gap-2 text-sm">
                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                    <span>No time pressure</span>
                                </div>
                                <div className="flex items-center gap-2 text-sm">
                                    <Trophy className="h-4 w-4 text-muted-foreground" />
                                    <span>Track your personal best</span>
                                </div>
                                <div className="flex items-center gap-2 text-sm">
                                    <Zap className="h-4 w-4 text-muted-foreground" />
                                    <span>Multiple difficulty levels</span>
                                </div>
                            </div>

                            <Button asChild className="w-full" size="lg">
                                <Link href={game.setup().url}>
                                    Start Solo Game
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Multiplayer Card */}
                    <Card className="relative overflow-hidden border-2 hover:border-primary transition-all hover:shadow-lg group">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform" />
                        <CardHeader>
                            <div className="flex items-center gap-3 mb-2">
                                <div className="p-3 rounded-lg bg-primary/10">
                                    <Users className="h-8 w-8 text-primary" />
                                </div>
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <CardTitle className="text-2xl">Multiplayer</CardTitle>
                                        <Badge variant="secondary" className="text-xs">New!</Badge>
                                    </div>
                                    <CardDescription>Compete with friends</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Create or join a room to play trivia with friends in real-time. 
                                Race against the clock and climb the leaderboard!
                            </p>
                            
                            <div className="space-y-2">
                                <div className="flex items-center gap-2 text-sm">
                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                    <span>30-second timer per question</span>
                                </div>
                                <div className="flex items-center gap-2 text-sm">
                                    <Trophy className="h-4 w-4 text-muted-foreground" />
                                    <span>Live leaderboard rankings</span>
                                </div>
                                <div className="flex items-center gap-2 text-sm">
                                    <Zap className="h-4 w-4 text-muted-foreground" />
                                    <span>Real-time competition</span>
                                </div>
                            </div>

                            <Button asChild className="w-full" size="lg">
                                <Link href={lobby.index().url}>
                                    Enter Multiplayer Lobby
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Quick Stats or Info Section */}
                <div className="grid gap-4 md:grid-cols-3 max-w-5xl mx-auto w-full">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center space-y-2">
                                <div className="text-3xl font-bold text-primary">1000+</div>
                                <p className="text-sm text-muted-foreground">Trivia Questions</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center space-y-2">
                                <div className="text-3xl font-bold text-primary">20+</div>
                                <p className="text-sm text-muted-foreground">Categories</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center space-y-2">
                                <div className="text-3xl font-bold text-primary">3</div>
                                <p className="text-sm text-muted-foreground">Difficulty Levels</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
