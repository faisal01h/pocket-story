import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { PlusCircle, Play, History as HistoryIcon, Calendar } from 'lucide-react';
import { store, show } from '@/routes/games/play';
import { BreadcrumbItem } from '@/types';

export default function Sessions({ game, sessions }: { game: any; sessions: any[] }) {
    const handleCreateSession = () => {
        router.post(store.url(game.id));
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'My Games', href: '/games' },
        { title: game.title, href: `/games/${game.id}` },
        { title: 'Sessions', href: `/games/${game.id}/play` },
    ];

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title={`${game.title} - Sessions`} />

            <div className="py-12 bg-gradient-to-b from-indigo-200 via-indigo-50 to-white min-h-screen">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-8">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Your Adventures</h2>
                            <p className="text-gray-500 dark:text-gray-400">Continue a previous journey or start a new one.</p>
                        </div>
                        <Button onClick={handleCreateSession} className="gap-2">
                            <PlusCircle className="h-4 w-4" />
                            Start New Adventure
                        </Button>
                    </div>

                    {sessions.length === 0 ? (
                        <Card className="text-center py-12 border-dashed border-2">
                            <CardContent className="space-y-4 pt-6">
                                <div className="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                    <HistoryIcon className="h-6 w-6 text-gray-400" />
                                </div>
                                <div className="space-y-1">
                                    <CardTitle>No adventures yet</CardTitle>
                                    <CardDescription>
                                        You haven't started any sessions for this game. Start a new one to begin your journey!
                                    </CardDescription>
                                </div>
                                <Button onClick={handleCreateSession} className="mt-4">
                                    Begin Adventure
                                </Button>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {sessions.map((session: any) => (
                                <Card key={session.id} className="hover:shadow-md transition-shadow group overflow-hidden">
                                    <div className="h-2 bg-indigo-500"></div>
                                    <CardHeader className="pb-3">
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <CardTitle className="text-lg">Session #{session.id}</CardTitle>
                                                <CardDescription className="flex items-center gap-1 mt-1 text-[10px]">
                                                    <Calendar className="h-3 w-3" />
                                                    Started {new Date(session.created_at).toLocaleDateString()}
                                                </CardDescription>
                                            </div>
                                            <Badge variant={session.mode === 'llm' ? 'secondary' : 'outline'} className="capitalize text-[10px] px-1.5 py-0 h-5">
                                                {session.mode.toUpperCase()}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pb-4">
                                        <div className="text-sm text-gray-600 dark:text-gray-400">
                                            <div className="font-semibold mb-1 text-[11px] flex items-center gap-1.5 uppercase tracking-wider opacity-60">
                                                <Play className="h-2.5 w-2.5" />
                                                Current Location
                                            </div>
                                            <p className="line-clamp-2 italic text-gray-900 dark:text-gray-100">
                                                {session.current_node?.title || 'Dynamic Encounter'}
                                            </p>
                                        </div>
                                    </CardContent>
                                    <CardFooter className="bg-gray-50/50 dark:bg-gray-800/30 border-t py-2">
                                        <div className="flex justify-between w-full">
                                            <Link href={show.url([game.id, session.id])}>
                                                <Button size="sm" className="gap-2 h-8 text-xs">
                                                    <Play className="h-2 w-2" />
                                                    Continue
                                                </Button>
                                            </Link>
                                            <p className="text-[10px] text-gray-400 self-center">
                                                Last active {new Date(session.updated_at).toLocaleString()}
                                            </p>
                                        </div>
                                    </CardFooter>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
