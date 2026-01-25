import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Edit, Trash2, PlusCircle } from 'lucide-react';

export default function Index({ limits }: { limits: any }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Limits', href: '/admin/llm-limits' },
    ];

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this limit?')) {
            router.delete(`/admin/llm-limits/${id}`);
        }
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="LLM Limits" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 flex justify-end">
                        <Link href="/admin/llm-limits/create">
                            <Button>
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Add Limit
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>LLM Token Limits</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">ID</th>
                                            <th scope="col" className="px-6 py-3">User</th>
                                            <th scope="col" className="px-6 py-3">Model</th>
                                            <th scope="col" className="px-6 py-3">Period</th>
                                            <th scope="col" className="px-6 py-3">Max Tokens</th>
                                            <th scope="col" className="px-6 py-3">Status</th>
                                            <th scope="col" className="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {limits.data.map((limit: any) => (
                                            <tr key={limit.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4">{limit.id}</td>
                                                <td className="px-6 py-4">{limit.user?.name || 'All Users'}</td>
                                                <td className="px-6 py-4 font-medium">
                                                    {limit.llm_model ? (
                                                        <span>
                                                            {limit.llm_model.name}
                                                            <span className="ml-2 text-[10px] text-gray-400 uppercase">({limit.llm_model.provider?.name})</span>
                                                        </span>
                                                    ) : (
                                                        'All Models'
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 capitalize">{limit.period}</td>
                                                <td className="px-6 py-4 font-mono">{limit.max_tokens.toLocaleString()}</td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-1 rounded text-xs ${limit.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                        {limit.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 flex gap-2">
                                                    <Link href={`/admin/llm-limits/${limit.id}/edit`}>
                                                        <Button variant="ghost" size="icon">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button variant="ghost" size="icon" className="text-red-500" onClick={() => handleDelete(limit.id)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                        {limits.data.length === 0 && (
                                            <tr>
                                                <td colSpan={7} className="px-6 py-10 text-center text-gray-500">
                                                    No limits defined yet.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-4 flex justify-between items-center">
                                <div className="text-sm text-gray-500">
                                    Showing {limits.from || 0} to {limits.to || 0} of {limits.total} results
                                </div>
                                <div className="flex gap-2">
                                    {limits.prev_page_url && (
                                        <Link href={limits.prev_page_url}>
                                            <Button variant="outline" size="sm">Previous</Button>
                                        </Link>
                                    )}
                                    {limits.next_page_url && (
                                        <Link href={limits.next_page_url}>
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
