import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Edit, Plus, Cpu, Trash2 } from 'lucide-react';
import llmModels from '@/routes/admin/llm-models';

interface LlmModel {
    id: number;
    name: string;
    identifier: string;
    is_active: boolean;
    provider: {
        id: number;
        name: string;
    };
}

export default function Index({ models }: { models: LlmModel[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Models', href: '/admin/llm-models' },
    ];

    const deleteModel = (id: number) => {
        if (confirm('Are you sure you want to delete this model?')) {
            router.delete(llmModels.destroy.url(id));
        }
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="LLM Models" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Cpu className="h-6 w-6 text-indigo-600" />
                            LLM Models
                        </h1>
                        <Link href={llmModels.create.url()}>
                            <Button className="flex items-center gap-2">
                                <Plus className="h-4 w-4" />
                                Add Model
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>Configured Models</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">Model Name</th>
                                            <th scope="col" className="px-6 py-3">Identifier</th>
                                            <th scope="col" className="px-6 py-3">Provider</th>
                                            <th scope="col" className="px-6 py-3">Status</th>
                                            <th scope="col" className="px-6 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {models.map((model) => (
                                            <tr key={model.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4">
                                                    <span className="font-bold text-gray-900 dark:text-white">{model.name}</span>
                                                </td>
                                                <td className="px-6 py-4 text-xs font-mono">
                                                    {model.identifier}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] rounded-full uppercase font-bold">
                                                        {model.provider.name}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {model.is_active ? (
                                                        <span className="text-green-600 font-medium">Active</span>
                                                    ) : (
                                                        <span className="text-red-500 font-medium">Inactive</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 flex justify-end gap-2">
                                                    <Link href={llmModels.edit.url(model.id)}>
                                                        <Button variant="ghost" size="icon">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        className="text-red-500"
                                                        onClick={() => deleteModel(model.id)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                        {models.length === 0 && (
                                            <tr>
                                                <td colSpan={5} className="px-6 py-4 text-center text-gray-500">
                                                    No models found.
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
