import { home } from '@/routes';
import { Link } from '@inertiajs/react';
import { Gamepad2 } from 'lucide-react';
import { type PropsWithChildren } from 'react';
import TriviaTrailLogo from '@/components/trivia-trail-logo';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-to-br from-orange-50 via-white to-green-50 p-6 md:p-10 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-6">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-3 font-medium"
                        >
                            <div className="flex items-center space-x-3">
                                <Gamepad2 className="h-8 w-8 text-orange-600 dark:text-orange-400" />
                                <TriviaTrailLogo size="md" />
                            </div>
                            <span className="sr-only">Trivia Trail</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{title}</h1>
                            <p className="text-center text-sm text-gray-600 dark:text-gray-300">
                                {description}
                            </p>
                        </div>
                    </div>
                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
