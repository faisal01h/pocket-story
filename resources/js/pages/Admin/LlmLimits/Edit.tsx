import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { BreadcrumbItem } from '@/types';
import { ArrowLeft } from 'lucide-react';

export default function Edit({ limit, users, providers }: { limit: any, users: any[], providers: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Limits', href: '/admin/llm-limits' },
        { title: 'Edit', href: `/admin/llm-limits/${limit.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        user_id: (limit.user_id?.toString() || null) as string | null,
        llm_model_id: (limit.llm_model_id?.toString() || null) as string | null,
        period: limit.period,
        max_tokens: limit.max_tokens,
        is_active: !!limit.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/llm-limits/${limit.id}`);
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit LLM Limit" />

            <div className="py-6">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4">
                        <Link href="/admin/llm-limits">
                            <Button variant="ghost" className="pl-0">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to list
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>Edit Token Limit</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="user_id">User (Optional - empty for all users)</Label>
                                    <Select value={data.user_id || 'all'} onValueChange={(val) => setData('user_id', val === 'all' ? null : val)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a user" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Users (Global Limit)</SelectItem>
                                            {users.map((user) => (
                                                <SelectItem key={user.id} value={user.id.toString()}>
                                                    {user.name} ({user.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.user_id && <p className="text-sm text-red-500">{errors.user_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="llm_model_id">Model (Optional - empty for all models)</Label>
                                    <Select value={data.llm_model_id || 'all'} onValueChange={(val) => setData('llm_model_id', val === 'all' ? null : val)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a model" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Models</SelectItem>
                                            {providers.map((provider: any) => (
                                                <div key={provider.id}>
                                                    <div className="px-2 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                                                        {provider.name}
                                                    </div>
                                                    {provider.models?.map((model: any) => (
                                                        <SelectItem key={model.id} value={model.id.toString()}>
                                                            {model.name}
                                                        </SelectItem>
                                                    ))}
                                                </div>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.llm_model_id && <p className="text-sm text-red-500">{errors.llm_model_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="period">Reset Period</Label>
                                    <Select value={data.period} onValueChange={(val) => setData('period', val)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="daily">Daily</SelectItem>
                                            <SelectItem value="monthly">Monthly</SelectItem>
                                            <SelectItem value="total">Total (Overall Limit)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.period && <p className="text-sm text-red-500">{errors.period}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="max_tokens">Max Tokens</Label>
                                    <Input
                                        id="max_tokens"
                                        type="number"
                                        value={data.max_tokens}
                                        onChange={(e) => setData('max_tokens', parseInt(e.target.value))}
                                        placeholder="e.g. 50000"
                                    />
                                    {errors.max_tokens && <p className="text-sm text-red-500">{errors.max_tokens}</p>}
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', !!checked)}
                                    />
                                    <Label htmlFor="is_active">Active</Label>
                                </div>

                                <div className="pt-4">
                                    <Button type="submit" className="w-full" disabled={processing}>
                                        {processing ? 'Updating...' : 'Update Limit'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
