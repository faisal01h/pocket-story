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

export default function Create({ providers }: { providers: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Redeem Codes', href: '/admin/redeem-codes' },
        { title: 'Create', href: '/admin/redeem-codes/create' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        code: '',
        llm_model_id: '' as string | null,
        period: 'daily',
        max_tokens: 50000,
        usage_limit: 1,
        expires_at: '',
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/redeem-codes');
    };

    const generateCode = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 12; i++) {
            if (i > 0 && i % 4 === 0) result += '-';
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        setData('code', result);
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Redeem Code" />

            <div className="py-6">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4">
                        <Link href="/admin/redeem-codes">
                            <Button variant="ghost" className="pl-0">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to list
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle>Create Redeem Code</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="code">Code</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="code"
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                            placeholder="XXXX-XXXX-XXXX"
                                            className="font-mono"
                                            required
                                        />
                                        <Button type="button" variant="outline" onClick={generateCode}>
                                            Generate
                                        </Button>
                                    </div>
                                    {errors.code && <p className="text-sm text-red-500">{errors.code}</p>}
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

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="period">Reset Period</Label>
                                        <Select value={data.period} onValueChange={(val) => setData('period', val as any)}>
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
                                        <Label htmlFor="max_tokens">Tokens to Add</Label>
                                        <Input
                                            id="max_tokens"
                                            type="number"
                                            value={data.max_tokens}
                                            onChange={(e) => setData('max_tokens', parseInt(e.target.value))}
                                            placeholder="e.g. 100000"
                                        />
                                        {errors.max_tokens && <p className="text-sm text-red-500">{errors.max_tokens}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="usage_limit">Global Usage Limit (Number of Users)</Label>
                                        <Input
                                            id="usage_limit"
                                            type="number"
                                            value={data.usage_limit}
                                            onChange={(e) => setData('usage_limit', parseInt(e.target.value))}
                                        />
                                        {errors.usage_limit && <p className="text-sm text-red-500">{errors.usage_limit}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="expires_at">Expiration Date (Optional)</Label>
                                        <Input
                                            id="expires_at"
                                            type="datetime-local"
                                            value={data.expires_at}
                                            onChange={(e) => setData('expires_at', e.target.value)}
                                        />
                                        {errors.expires_at && <p className="text-sm text-red-500">{errors.expires_at}</p>}
                                    </div>
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
                                        {processing ? 'Creating...' : 'Create Redeem Code'}
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
