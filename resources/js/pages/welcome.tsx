import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Gamepad2, Users, Trophy, Zap, Play, UserPlus } from 'lucide-react';
import TriviaTrailLogo from '@/components/trivia-trail-logo';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head> 
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>
            <div className="min-h-screen bg-gradient-to-br from-orange-50 via-white to-green-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
                {/* Navigation */}
                <header className="relative z-10 w-full">
                    <nav className="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8">
                        <div className="flex items-center space-x-3">
                            <Gamepad2 className="h-8 w-8 text-orange-600 dark:text-orange-400" />
                            <TriviaTrailLogo size="md" />
                        </div>
                        
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600"
                                >
                                    <Play className="h-4 w-4" />
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        Log in
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                        >
                                            <UserPlus className="h-4 w-4" />
                                            Register
                                        </Link>
                                    )}
                                </>
                            )}
                        </div>
                    </nav>
                </header>

                {/* Hero Section */}
                <main className="relative">
                    <div className="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
                        <div className="mx-auto max-w-2xl text-center">
                            <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl dark:text-white">
                                Test Your Knowledge on the{' '}
                                <span className="inline-flex flex-wrap items-center justify-center gap-1">
                                    <TriviaTrailLogo size="lg" />
                                </span>
                            </h1>
                            <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                                Challenge yourself with thousands of questions across 15+ categories. 
                                Play solo to sharpen your skills or compete with friends in real-time multiplayer battles.
                            </p>
                            <div className="mt-10 flex items-center justify-center gap-x-6">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-purple-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600"
                                    >
                                        <Play className="h-5 w-5" />
                                        Start Playing
                                    </Link>
                                ) : (
                                    <>
                                        {canRegister && (
                                            <Link
                                                href={register()}
                                                className="inline-flex items-center gap-2 rounded-lg bg-purple-600 hover:bg-purple-700 focus-visible:ring-purple-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-600"
                                            >
                                                <UserPlus className="h-5 w-5" />
                                                Play Free
                                            </Link>
                                        )}
                                        <Link
                                            href={login()}
                                            className="text-base font-semibold leading-6 text-gray-900 dark:text-white"
                                        >
                                            Already have an account? <span aria-hidden="true">→</span>
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Features Section */}
                    <div className="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
                        <div className="mx-auto max-w-2xl lg:text-center">
                            <h2 className="text-base font-semibold leading-7 text-orange-600 dark:text-orange-400">
                                Game Features
                            </h2>
                            <p className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                                Everything you need for the ultimate trivia experience
                            </p>
                        </div>
                        <div className="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                            <dl className="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                                <div className="flex flex-col">
                                    <dt className="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                        <Gamepad2 className="h-5 w-5 flex-none text-green-600 dark:text-green-400" />
                                        Single Player Mode
                                    </dt>
                                    <dd className="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600 dark:text-gray-300">
                                        <p className="flex-auto">
                                            Practice solo with customizable difficulty levels and categories. 
                                            Choose from 1-50 questions and track your progress over time.
                                        </p>
                                    </dd>
                                </div>
                                <div className="flex flex-col">
                                    <dt className="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                        <Users className="h-5 w-5 flex-none text-purple-600 dark:text-purple-400" />
                                        Multiplayer Battles
                                    </dt>
                                    <dd className="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600 dark:text-gray-300">
                                        <p className="flex-auto">
                                            Create private rooms with 6-character codes and compete with up to 10 friends. 
                                            Real-time competition with live answer indicators.
                                        </p>
                                    </dd>
                                </div>
                                <div className="flex flex-col">
                                    <dt className="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                        <Trophy className="h-5 w-5 flex-none text-yellow-600 dark:text-yellow-400" />
                                        Comprehensive Results
                                    </dt>
                                    <dd className="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600 dark:text-gray-300">
                                        <p className="flex-auto">
                                            Detailed answer breakdowns, performance analytics, and game history. 
                                            See your accuracy and track improvement over time.
                                        </p>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Stats Section */}
                    <div className="bg-gradient-to-r from-purple-50 via-pink-50 to-orange-50 py-24 sm:py-32 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800">
                        <div className="mx-auto max-w-7xl px-6 lg:px-8">
                            <div className="mx-auto max-w-2xl lg:max-w-none">
                                <div className="text-center">
                                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                                        Powered by thousands of questions
                                    </h2>
                                    <p className="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-300">
                                        Challenge yourself across multiple categories and difficulty levels
                                    </p>
                                </div>
                                <dl className="mt-16 grid grid-cols-1 gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2 lg:grid-cols-4">
                                    <div className="flex flex-col bg-gradient-to-br from-red-500 to-orange-500 p-8 text-white">
                                        <dt className="text-sm font-semibold leading-6 text-red-100">
                                            Total Questions
                                        </dt>
                                        <dd className="order-first text-3xl font-bold tracking-tight text-white">
                                            4,000+
                                        </dd>
                                    </div>
                                    <div className="flex flex-col bg-gradient-to-br from-green-500 to-teal-500 p-8 text-white">
                                        <dt className="text-sm font-semibold leading-6 text-green-100">
                                            Categories
                                        </dt>
                                        <dd className="order-first text-3xl font-bold tracking-tight text-white">
                                            15+
                                        </dd>
                                    </div>
                                    <div className="flex flex-col bg-gradient-to-br from-blue-500 to-purple-500 p-8 text-white">
                                        <dt className="text-sm font-semibold leading-6 text-blue-100">
                                            Difficulty Levels
                                        </dt>
                                        <dd className="order-first text-3xl font-bold tracking-tight text-white">
                                            3
                                        </dd>
                                    </div>
                                    <div className="flex flex-col bg-gradient-to-br from-pink-500 to-rose-500 p-8 text-white">
                                        <dt className="text-sm font-semibold leading-6 text-pink-100">
                                            Max Players
                                        </dt>
                                        <dd className="order-first text-3xl font-bold tracking-tight text-white">
                                            10
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    {/* CTA Section */}
                    <div className="bg-blue-600 dark:bg-blue-700">
                        <div className="px-6 py-24 sm:px-6 sm:py-32 lg:px-8">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                                    Ready to test your knowledge?
                                </h2>
                                <p className="mx-auto mt-6 max-w-xl text-lg leading-8 text-blue-100">
                                    Join thousands of trivia enthusiasts and start your journey today. 
                                    It's free to play and takes less than a minute to get started.
                                </p>
                                <div className="mt-10 flex items-center justify-center gap-x-6">
                                    {auth.user ? (
                                        <Link
                                            href={dashboard()}
                                            className="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-blue-600 shadow-sm transition-colors hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                        >
                                            <Zap className="h-5 w-5" />
                                            Start Playing Now
                                        </Link>
                                    ) : (
                                        <>
                                            {canRegister && (
                                                <Link
                                                    href={register()}
                                                    className="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-blue-600 shadow-sm transition-colors hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                                >
                                                    <Zap className="h-5 w-5" />
                                                    Get Started
                                                </Link>
                                            )}
                                            <Link
                                                href={login()}
                                                className="text-base font-semibold leading-6 text-white"
                                            >
                                                Sign In <span aria-hidden="true">→</span>
                                            </Link>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}