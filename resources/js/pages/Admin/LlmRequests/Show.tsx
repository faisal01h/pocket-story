import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Cpu, User, Database } from 'lucide-react';

export default function Show({ request }: { request: any }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Requests', href: '/admin/llm-requests' },
        { title: `Request #${request.id}`, href: `/admin/llm-requests/${request.id}` },
    ];

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title={`LLM Request #${request.id}`} />

            <div className="py-6 space-y-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                    <div className="mb-6">
                        <Link href="/admin/llm-requests">
                            <Button variant="ghost" className="gap-2 pl-0 hover:bg-transparent hover:text-indigo-600">
                                <ArrowLeft className="h-4 w-4" /> Back to List
                            </Button>
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Meta Data Column */}
                        <div className="space-y-6">
                            <Card className='py-5'>
                                <CardHeader>
                                    <CardTitle className="text-sm font-bold uppercase tracking-widest text-gray-500">Request Data</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <div className="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">ID</div>
                                        <div className="font-mono text-sm">#{request.id}</div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Timestamp</div>
                                        <div className="text-sm">{new Date(request.created_at).toLocaleString()}</div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Provider / Model</div>
                                        <div className="flex flex-col gap-1">
                                            <Badge variant="outline" className="w-fit">{request.llm_model?.provider?.name || 'Unknown Provider'}</Badge>
                                            <Badge variant="secondary" className="w-fit">{request.llm_model?.name || 'Unknown Model'}</Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className='py-5'>
                                <CardHeader>
                                    <CardTitle className="text-sm font-bold uppercase tracking-widest text-gray-500">Usage Stats</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-100 dark:border-green-900/50">
                                            <div className="text-[10px] text-green-600 dark:text-green-400 font-bold uppercase">Input Tokens</div>
                                            <div className="text-2xl font-bold text-green-700 dark:text-green-300">{request.input_token_count}</div>
                                        </div>
                                        <div className="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-900/50">
                                            <div className="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase">Output Tokens</div>
                                            <div className="text-2xl font-bold text-blue-700 dark:text-blue-300">{request.output_token_count}</div>
                                        </div>
                                    </div>
                                    <div className="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border flex justify-between items-center">
                                        <span className="text-xs font-medium text-gray-500">Total Tokens</span>
                                        <span className="font-mono font-bold">{request.input_token_count + request.output_token_count}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            {request.user && (
                                <Card className='py-5'>
                                    <CardHeader>
                                        <CardTitle className="text-sm font-bold uppercase tracking-widest text-gray-500">User</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center gap-3">
                                            <div className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                                {request.user.name.charAt(0)}
                                            </div>
                                            <div>
                                                <div className="font-medium text-sm">{request.user.name}</div>
                                                <div className="text-xs text-gray-400">{request.user.email}</div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Content Column */}
                        <div className="md:col-span-2 space-y-6">
                            <Card className="h-full flex flex-col">
                                <CardHeader className="bg-gray-50 dark:bg-gray-800/50 border-b py-5 rounded-t-lg">
                                    <CardTitle className="flex items-center gap-2">
                                        <User className="h-4 w-4 text-indigo-500" />
                                        Input Prompt
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-0 flex-1">
                                    <div className="p-4 bg-gray-50/50 dark:bg-gray-900 text-sm font-mono whitespace-pre-wrap overflow-x-auto max-h-[400px] overflow-y-auto">
                                        {request.input_token || <span className="text-gray-400 italic">No input content recorded</span>}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="h-full flex flex-col">
                                <CardHeader className="bg-gray-50 dark:bg-gray-800/50 border-b py-5 rounded-t-lg">
                                    <CardTitle className="flex items-center gap-2">
                                        <Cpu className="h-4 w-4 text-purple-500" />
                                        Model Response
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-0 flex-1">
                                    <div className="p-4 bg-gray-50/50 dark:bg-gray-900 text-sm font-mono whitespace-pre-wrap overflow-x-auto max-h-[600px] overflow-y-auto">
                                        {request.output_token || <span className="text-gray-400 italic">No output content recorded</span>}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
