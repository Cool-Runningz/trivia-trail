import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

// Trivia Game Types

export type DifficultyLevel = 'easy' | 'medium' | 'hard';
export type GameStatus = 'active' | 'completed';

export interface Category {
    id: number;
    name: string;
}

export interface Question {
    question: string;
    correct_answer: string;
    incorrect_answers: string[];
    difficulty: DifficultyLevel;
    category: string;
    type: 'multiple' | 'boolean';
    shuffled_answers: string[];
}

export interface Game {
    id: number;
    user_id: number;
    category_id: number | null;
    difficulty: DifficultyLevel;
    total_questions: number;
    current_question_index: number;
    score: number;
    status: GameStatus;
    questions: Question[];
    started_at: string;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PlayerAnswer {
    id: number;
    game_id: number;
    question_index: number;
    question: string;
    selected_answer: string;
    correct_answer: string;
    is_correct: boolean;
    points_earned: number;
    answered_at: string;
    created_at: string;
    updated_at: string;
}

// API Response Types

export interface CategoriesApiResponse {
    trivia_categories: Category[];
}

export interface QuestionsApiResponse {
    response_code: number;
    results: Question[];
}

export interface QuestionsApiError {
    error: boolean;
    message: string;
    questions: Question[];
}

// Laravel API Response Wrappers

export interface ApiSuccessResponse<T> {
    success: true;
    data: T;
}

export interface ApiErrorResponse {
    success: false;
    message: string;
    error?: string;
}

export type ApiResponse<T> = ApiSuccessResponse<T> | ApiErrorResponse;

// Specific API Response Types
export type CategoriesResponse = ApiResponse<Category[]>;
export type QuestionsResponse = ApiResponse<Question[]>;

// Game Controller Response Types

export interface AnswerResponse {
    is_correct: boolean;
    correct_answer: string;
    points_earned: number;
    new_score: number;
    is_game_completed: boolean;
    next_question?: Question;
    progress?: GameProgress;
}

export interface GameProgress {
    current: number;
    total: number;
    percentage: number;
}

// Game Logic Utility Types

export interface GameSetupParams {
    category_id?: number;
    difficulty: DifficultyLevel;
    total_questions: number;
}

export interface AnswerSubmission {
    selected_answer: string;
}

export interface GameResults {
    final_score: number;
    correct_answers: number;
    total_questions: number;
    percentage: number;
    player_answers: PlayerAnswer[];
}

export interface QuestionWithProgress {
    question: Question;
    current_index: number;
    total_questions: number;
    score: number;
}

// Inertia Page Props Types

export interface GameSetupPageProps {
    categories: Category[];
}

export interface GamePlayPageProps {
    game: {
        id: number;
        score: number;
        current_question_index: number;
        total_questions: number;
        difficulty: DifficultyLevel;
        status: GameStatus;
    };
    question: Question;
    progress: GameProgress;
}

export interface GameResultsPageProps {
    game: {
        id: number;
        difficulty: DifficultyLevel;
        total_questions: number;
        started_at: string;
        completed_at: string;
        time_taken_minutes: number | null;
    };
    results: {
        final_score: number;
        correct_answers: number;
        total_questions: number;
        percentage_score: number;
        answer_breakdown: AnswerBreakdown[];
    };
}

export interface AnswerBreakdown {
    question: string;
    selected_answer: string;
    correct_answer: string;
    is_correct: boolean;
    points_earned: number;
}

// Multiplayer Types

export type RoomStatus = 'waiting' | 'active' | 'completed' | 'cancelled';
export type ParticipantStatus = 'joined' | 'ready' | 'playing' | 'finished' | 'disconnected';

export interface RoomSettings {
    time_per_question: number;
    category_id?: number;
    difficulty: DifficultyLevel;
    total_questions: number;
}

export interface Participant {
    id: number;
    user: User;
    status: ParticipantStatus;
    score: number;
    has_answered_current: boolean;
    position?: number;
    joined_at: string;
}

export interface GameRoom {
    id: number;
    room_code: string;
    host_user_id: number;
    host?: User;
    max_players: number;
    current_players: number;
    status: RoomStatus;
    settings: RoomSettings;
    participants: Participant[];
    is_participant?: boolean;
    is_host?: boolean;
    expires_at: string;
    created_at: string;
    updated_at: string;
}

export type MultiplayerGameStatus = 'waiting' | 'active' | 'showing_results' | 'completed';

export interface MultiplayerGameState {
    room: GameRoom;
    game_status?: MultiplayerGameStatus;
    current_question?: Question;
    current_question_index: number;
    time_remaining: number;
    participants: Participant[];
    round_results?: RoundResults;
    all_players_answered?: boolean;
    current_user_has_answered?: boolean;
    is_ready_for_next?: boolean;
    ready_since?: string | null;
}

export interface RoundResults {
    question: Question;
    correct_answer: string;
    participant_results: ParticipantResult[];
    leaderboard: LeaderboardEntry[];
}

export interface ParticipantResult {
    participant: Participant;
    selected_answer?: string;
    is_correct: boolean;
    points_earned: number;
    response_time_ms?: number;
}

export interface LeaderboardEntry {
    participant: Participant;
    score: number;
    position: number;
}

// Multiplayer Page Props

export interface LobbyPageProps {
    rooms: GameRoom[];
    activeGames: GameRoom[];
    categories: Category[];
}

export interface RoomLobbyProps {
    room: GameRoom;
    participants: Participant[];
    isHost: boolean;
    canStart: boolean;
}

export interface CreateRoomFormData {
    category_id?: number;
    difficulty: DifficultyLevel;
    total_questions: number;
}

export interface JoinRoomFormData {
    room_code: string;
}

// Global route helper (provided by Ziggy/Laravel)
declare global {
    function route(name: string, params?: unknown): string;
}
