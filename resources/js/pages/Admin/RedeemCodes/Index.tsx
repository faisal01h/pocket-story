import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Edit, Trash2, PlusCircle, Ticket } from 'lucide-react';

export default function Index({ codes }: { codes: any }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Redeem Codes', href: '/admin/redeem-codes' },
    ];

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this redeem code?')) {
            router.delete(`/admin/redeem-codes/${id}`);
        }
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Redeem Codes" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 flex justify-end">
                        <Link href="/admin/redeem-codes/create">
                            <Button>
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Create Code
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Ticket className="h-5 w-5" />
                                LLM Redeem Codes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">Code</th>
                                            <th scope="col" className="px-6 py-3">Model</th>
                                            <th scope="col" className="px-6 py-3">Limit Extra</th>
                                            <th scope="col" className="px-6 py-3">Usages</th>
                                            <th scope="col" className="px-6 py-3">Expires</th>
                                            <th scope="col" className="px-6 py-3">Status</th>
                                            <th scope="col" className="px-6 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {codes.data.map((code: any) => (
                                            <tr key={code.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">{code.code}</td>
                                                <td className="px-6 py-4">
                                                    {code.llm_model ? (
                                                        <span>
                                                            {code.llm_model.name}
                                                            <span className="ml-2 text-[10px] text-gray-400 uppercase">({code.llm_model.provider?.name})</span>
                                                        </span>
                                                    ) : (
                                                        'All Models'
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="font-mono">{code.max_tokens.toLocaleString()}</div>
                                                    <div className="text-[10px] text-gray-400 uppercase">{code.period}</div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {code.usages_count} / {code.usage_limit}
                                                </td>
                                                <td className="px-6 py-4 text-xs">
                                                    {code.expires_at ? new Date(code.expires_at).toLocaleDateString() : 'Never'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-1 rounded text-xs ${code.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                        {code.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 flex gap-2">
                                                    <Link href={`/admin/redeem-codes/${code.id}/edit`}>
                                                        <Button variant="ghost" size="icon">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button variant="ghost" size="icon" className="text-red-500" onClick={() => handleDelete(code.id)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                        {codes.data.length === 0 && (
                                            <tr>
                                                <td colSpan={7} className="px-6 py-10 text-center text-gray-500">
                                                    No redeem codes created yet.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="mt-4 flex justify-between items-center">
                                <div className="text-sm text-gray-500">
                                    Showing {codes.from || 0} to {codes.to || 0} of {codes.total} results
                                </div>
                                <div className="flex gap-2">
                                    {codes.prev_page_url && (
                                        <Link href={codes.prev_page_url}>
                                            <Button variant="outline" size="sm">Previous</Button>
                                        </Link>
                                    )}
                                    {codes.next_page_url && (
                                        <Link href={codes.next_page_url}>
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
