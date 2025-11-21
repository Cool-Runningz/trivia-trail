import { cn } from '@/lib/utils';

interface TriviaTrailLogoProps {
    size?: 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
}

const sizeConfig = {
    sm: {
        tile: 'h-6 w-5 text-xs',
        separator: 'text-sm',
    },
    md: {
        tile: 'h-8 w-6 text-sm',
        separator: 'text-lg',
    },
    lg: {
        tile: 'h-12 w-10 text-lg sm:h-16 sm:w-12 sm:text-2xl',
        separator: 'text-2xl sm:text-4xl',
    },
    xl: {
        tile: 'h-16 w-12 text-2xl sm:h-20 sm:w-16 sm:text-3xl',
        separator: 'text-3xl sm:text-5xl',
    },
};

export default function TriviaTrailLogo({ 
    size = 'md', 
    className 
}: TriviaTrailLogoProps) {
    const config = sizeConfig[size];
    
    return (
        <div className={cn('flex items-center space-x-1', className)}>
            <span className={cn('inline-flex items-center justify-center rounded bg-red-500 font-bold text-white shadow-sm', config.tile)}>T</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-orange-500 font-bold text-white shadow-sm', config.tile)}>R</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-yellow-500 font-bold text-white shadow-sm', config.tile)}>I</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-green-500 font-bold text-white shadow-sm', config.tile)}>V</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-blue-500 font-bold text-white shadow-sm', config.tile)}>I</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-purple-500 font-bold text-white shadow-sm', config.tile)}>A</span>
            <span className={cn('mx-2 font-bold text-gray-900 dark:text-white', config.separator)}>•</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-pink-500 font-bold text-white shadow-sm', config.tile)}>T</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-indigo-500 font-bold text-white shadow-sm', config.tile)}>R</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-teal-500 font-bold text-white shadow-sm', config.tile)}>A</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-cyan-500 font-bold text-white shadow-sm', config.tile)}>I</span>
            <span className={cn('inline-flex items-center justify-center rounded bg-emerald-500 font-bold text-white shadow-sm', config.tile)}>L</span>
        </div>
    );
}