import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as gamesIndex } from '@/routes/games';
import { show as playShow } from '@/routes/games/play';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Gamepad2, History, Layers, PlayCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardProps {
    stats: {
        sessions_count: number;
        unique_games_played: number;
        total_games_created: number;
    };
    recentSessions: Array<{
        id: number;
        game_id: number;
        current_node_id: number | null;
        mode: string;
        updated_at: string;
        game: {
            id: number;
            title: string;
        };
        current_node: {
            id: number;
            title: string;
        } | null;
    }>;
}

export default function Dashboard({ stats, recentSessions }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Stats Grid */}
                <div className="grid gap-6 md:grid-cols-3">
                    <Card className='py-5'>
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sessions</CardTitle>
                            <History className="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.sessions_count}</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Across all games</p>
                        </CardContent>
                    </Card>
                    <Card className='py-5'>
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">Games Played</CardTitle>
                            <Gamepad2 className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.unique_games_played}</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Unique story worlds explored</p>
                        </CardContent>
                    </Card>
                    <Card className='py-5'>
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">Games Created</CardTitle>
                            <Layers className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_games_created}</div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Your own contributions</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Join Game by Code */}
                <Card className="py-5">
                    <CardHeader>
                        <CardTitle>Find Game by Code</CardTitle>
                        <CardDescription>Enter a unique game code (slug) to start a new adventure.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.currentTarget);
                            router.post('/discover/join', { code: formData.get('code') });
                        }} className="flex gap-2 max-w-sm">
                            <Input name="code" placeholder="Enter code (e.g. dragon-nest)" required />
                            <Button type="submit">Join</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Recent Sessions */}
                <Card className="flex-1 py-5">
                    <CardHeader>
                        <CardTitle>Recent Game Sessions</CardTitle>
                        <CardDescription>Pick up where you left off in your adventures.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recentSessions.length > 0 ? (
                            <div className="space-y-4">
                                {recentSessions.map((session) => (
                                    <div key={session.id} className="flex items-center justify-between p-4 rounded-lg border border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <div className="flex flex-col gap-1">
                                            <div className="font-semibold">{session.game.title}</div>
                                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                                <span>{session.current_node?.title || 'Unknown Location'}</span>
                                                <span>•</span>
                                                <Badge variant="outline" className="text-[10px] uppercase">
                                                    {session.mode}
                                                </Badge>
                                                <span>•</span>
                                                <span>{new Date(session.updated_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                        <Link
                                            href={playShow.url({ game: session.game_id, play: session.id })}
                                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors"
                                        >
                                            <PlayCircle className="h-4 w-4" />
                                            Continue
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-gray-500">
                                <Gamepad2 className="h-12 w-12 mb-4 opacity-20" />
                                <p>No sessions found. Start a game to see it here!</p>
                                <Link href={gamesIndex.url()} className="mt-4 text-indigo-600 hover:underline">Browse Games</Link>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
