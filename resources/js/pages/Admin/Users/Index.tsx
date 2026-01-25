import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { BreadcrumbItem } from '@/types';
import { Eye, Search, User as UserIcon } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Index({ users, filters }: { users: any; filters: any }) {
    const [search, setSearch] = useState(filters.search || '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Users', href: '/admin/users' },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { search }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <UserIcon className="h-6 w-6 text-indigo-600" />
                            Users
                        </h1>
                        <form onSubmit={handleSearch} className="flex w-full md:w-1/3 gap-2">
                            <div className="relative w-full">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
                                <Input
                                    type="search"
                                    placeholder="Search name or email..."
                                    className="pl-8"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>Registered Users</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">User</th>
                                            <th scope="col" className="px-6 py-3">Roles</th>
                                            <th scope="col" className="px-6 py-3">Joined</th>
                                            <th scope="col" className="px-6 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.data.map((user: any) => (
                                            <tr key={user.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-bold text-gray-900 dark:text-white">{user.name}</span>
                                                        <span className="text-xs text-gray-400">{user.email}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        {user.roles.map((role: any) => (
                                                            <span key={role.id} className="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] rounded-full uppercase font-bold tracking-tighter">
                                                                {role.name}
                                                            </span>
                                                        ))}
                                                        {user.roles.length === 0 && <span className="text-gray-400 italic text-xs">No roles</span>}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-xs">
                                                    {new Date(user.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 flex justify-end gap-2">
                                                    <Link href={`/admin/users/${user.id}`}>
                                                        <Button variant="ghost" size="icon" title="View details">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            <div className="mt-4 flex justify-between items-center">
                                <div className="text-sm text-gray-500">
                                    Showing {users.from} to {users.to} of {users.total} results
                                </div>
                                <div className="flex gap-2">
                                    {users.prev_page_url && (
                                        <Link href={users.prev_page_url}>
                                            <Button variant="outline" size="sm">Previous</Button>
                                        </Link>
                                    )}
                                    {users.next_page_url && (
                                        <Link href={users.next_page_url}>
                                            <Button variant="outline" size="sm">Next</Button>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
