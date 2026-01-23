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

export default function Create({ users }: { users: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Limits', href: '/admin/llm-limits' },
        { title: 'Create', href: '/admin/llm-limits/create' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        user_id: '' as string | null,
        model_name: '' as string | null,
        period: 'daily',
        max_tokens: 10000,
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/llm-limits');
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Create LLM Limit" />

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
                            <CardTitle>Create Token Limit</CardTitle>
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
                                    <Label htmlFor="model_name">Model (Optional - empty for all models)</Label>
                                    <Select value={data.model_name || 'all'} onValueChange={(val) => setData('model_name', val === 'all' ? null : val)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a model" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Models</SelectItem>
                                            <SelectItem value="gemini-2.0-flash">Gemini 2.0 Flash</SelectItem>
                                            <SelectItem value="gemini-2.0-pro">Gemini 2.0 Pro</SelectItem>
                                            <SelectItem value="gemini-1.5-flash">Gemini 1.5 Flash</SelectItem>
                                            <SelectItem value="gemini-1.5-pro">Gemini 1.5 Pro</SelectItem>
                                            <SelectItem value="gemini-exp-1206">Gemini experimental</SelectItem>
                                            <SelectItem value="gemini-3-pro-preview">Gemini 3 Pro (Preview)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.model_name && <p className="text-sm text-red-500">{errors.model_name}</p>}
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
                                        {processing ? 'Creating...' : 'Create Limit'}
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
