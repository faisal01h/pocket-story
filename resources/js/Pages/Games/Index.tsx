import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

export default function Index({ games }) {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">My Games</h2>}
        >
            <Head title="My Games" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="flex justify-end mb-6">
                        <Link href={route('games.create')}>
                            <Button>Create New Game</Button>
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {games.length === 0 ? (
                            <div className="col-span-full text-center text-gray-500 py-12">
                                You haven't created any games yet.
                            </div>
                        ) : (
                            games.map((game) => (
                                <Card key={game.id}>
                                    <CardHeader>
                                        <CardTitle>{game.title}</CardTitle>
                                        <CardDescription>{game.description || 'No description'}</CardDescription>
                                    </CardHeader>
                                    <CardFooter className="flex justify-between">
                                        <Link href={route('games.show', game.id)}>
                                            <Button variant="outline">Edit</Button>
                                        </Link>
                                        <Link href={route('games.play.show', game.id)}>
                                            <Button>Play</Button>
                                        </Link>
                                    </CardFooter>
                                </Card>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
