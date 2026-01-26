import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Edit, Plus, Server, Trash2 } from 'lucide-react';
import llmProviders from '@/routes/admin/llm-providers';

interface Provider {
    id: number;
    name: string;
    slug: string;
    base_url: string;
    is_active: boolean;
    models_count: number;
}

export default function Index({ providers }: { providers: Provider[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Providers', href: '/admin/llm-providers' },
    ];

    const deleteProvider = (id: number) => {
        if (confirm('Are you sure you want to delete this provider? This will also delete all associated models.')) {
            router.delete(llmProviders.destroy.url(id));
        }
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="LLM Providers" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Server className="h-6 w-6 text-indigo-600" />
                            LLM Providers
                        </h1>
                        <Link href={llmProviders.create.url()}>
                            <Button className="flex items-center gap-2">
                                <Plus className="h-4 w-4" />
                                Add Provider
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>Configured Providers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">Provider</th>
                                            <th scope="col" className="px-6 py-3">Base URL</th>
                                            <th scope="col" className="px-6 py-3">Models</th>
                                            <th scope="col" className="px-6 py-3">Status</th>
                                            <th scope="col" className="px-6 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {providers.map((provider) => (
                                            <tr key={provider.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col">
                                                        <span className="font-bold text-gray-900 dark:text-white">{provider.name}</span>
                                                        <span className="text-xs text-gray-400">{provider.slug}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-xs">
                                                    {provider.base_url || 'Default'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] rounded-full uppercase font-bold">
                                                        {provider.models_count} Models
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {provider.is_active ? (
                                                        <span className="text-green-600 font-medium">Active</span>
                                                    ) : (
                                                        <span className="text-red-500 font-medium">Inactive</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 flex justify-end gap-2">
                                                    <Link href={llmProviders.edit.url(provider.id)}>
                                                        <Button variant="ghost" size="icon">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        className="text-red-500"
                                                        onClick={() => deleteProvider(provider.id)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                        {providers.length === 0 && (
                                            <tr>
                                                <td colSpan={5} className="px-6 py-4 text-center text-gray-500">
                                                    No providers found.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
