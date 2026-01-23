import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Eye } from 'lucide-react';

export default function Index({ requests }: { requests: any }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Requests', href: '/admin/llm-requests' },
    ];

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="LLM Requests" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>LLM Request Logs</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">ID</th>
                                            <th scope="col" className="px-6 py-3">User</th>
                                            <th scope="col" className="px-6 py-3">Model</th>
                                            <th scope="col" className="px-6 py-3">Tokens (In/Out)</th>
                                            <th scope="col" className="px-6 py-3">Time</th>
                                            <th scope="col" className="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {requests.data.map((request: any) => (
                                            <tr key={request.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4">{request.id}</td>
                                                <td className="px-6 py-4">{request.user?.name || 'Unknown'}</td>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-medium">{request.model_name}</span>
                                                        <span className="text-xs text-gray-400">{request.provider}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-green-600">{request.input_token_count}</span> / <span className="text-blue-600">{request.output_token_count}</span>
                                                </td>
                                                <td className="px-6 py-4">{new Date(request.created_at).toLocaleString()}</td>
                                                <td className="px-6 py-4">
                                                    <Link href={`/admin/llm-requests/${request.id}`}>
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

                            <div className="mt-4 flex justify-between items-center">
                                <div className="text-sm text-gray-500">
                                    Showing {requests.from} to {requests.to} of {requests.total} results
                                </div>
                                <div className="flex gap-2">
                                    {requests.prev_page_url && (
                                        <Link href={requests.prev_page_url}>
                                            <Button variant="outline" size="sm">Previous</Button>
                                        </Link>
                                    )}
                                    {requests.next_page_url && (
                                        <Link href={requests.next_page_url}>
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
