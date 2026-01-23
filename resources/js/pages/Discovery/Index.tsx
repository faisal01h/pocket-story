import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { BreadcrumbItem } from '@/types';
import { Search, PlayCircle, User as UserIcon } from 'lucide-react';
import { useState } from 'react';

export default function Index({ games, filters }: { games: any, filters: any }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Discover', href: '/discover' },
    ];

    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/discover', { search }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Discover Games" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Discover Adventures</h1>
                            <p className="text-gray-500 dark:text-gray-400">Explore public games created by the community.</p>
                        </div>

                        <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-96">
                            <div className="relative flex-1">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
                                <Input
                                    placeholder="Search by title or code..."
                                    className="pl-9"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {games.data.map((game: any) => (
                            <Card key={game.id} className="overflow-hidden flex flex-col h-full hover:shadow-lg transition-shadow py-5">
                                <CardHeader className="pb-2">
                                    <div className="flex justify-between items-start">
                                        <CardTitle className="text-lg font-bold line-clamp-1">{game.title}</CardTitle>
                                    </div>
                                    <div className="flex items-center text-xs text-gray-500 gap-1.5 pt-1">
                                        <UserIcon className="h-3 w-3" />
                                        <span>{game.user.name}</span>
                                    </div>
                                </CardHeader>
                                <CardContent className="flex-1 flex flex-col">
                                    <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 mb-4 flex-1">
                                        {game.description || 'No description provided.'}
                                    </p>

                                    <div className="mt-auto space-y-3">
                                        <div className="flex items-center justify-between text-[10px] text-gray-400 uppercase tracking-wider font-semibold">
                                            <span>CODE: {game.slug || game.id}</span>
                                        </div>
                                        <Link href={`/games/${game.id}/play`}>
                                            <Button className="w-full" variant="outline">
                                                <PlayCircle className="mr-2 h-4 w-4" />
                                                Play Now
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    {games.data.length === 0 && (
                        <div className="text-center py-20 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-dashed border-gray-300 dark:border-gray-700">
                            <p className="text-gray-500">No public games found.</p>
                        </div>
                    )}

                    <div className="mt-8 flex justify-center gap-2">
                        {games.links.map((link: any, i: number) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-3 py-1 rounded border text-sm ${link.active
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
