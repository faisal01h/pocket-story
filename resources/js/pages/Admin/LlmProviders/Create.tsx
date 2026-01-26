import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { BreadcrumbItem } from '@/types';
import { Server } from 'lucide-react';
import InputError from '@/components/input-error';
import llmProviders from '@/routes/admin/llm-providers';

export default function Create() {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Providers', href: '/admin/llm-providers' },
        { title: 'Create', href: '/admin/llm-providers/create' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        base_url: '',
        api_key: '',
        secret_key: '',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(llmProviders.store.url());
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Create LLM Provider" />

            <div className="py-6">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Server className="h-6 w-6 text-indigo-600" />
                            Create LLM Provider
                        </h1>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Provider Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <Label htmlFor="name">Provider Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="e.g. OpenAI, Anthropic, Google"
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                <div>
                                    <Label htmlFor="base_url">Base URL (Optional)</Label>
                                    <Input
                                        id="base_url"
                                        value={data.base_url}
                                        onChange={(e) => setData('base_url', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="https://api.openai.com/v1"
                                    />
                                    <InputError message={errors.base_url} className="mt-2" />
                                </div>

                                <div>
                                    <Label htmlFor="api_key">API Key (Optional)</Label>
                                    <Input
                                        id="api_key"
                                        type="password"
                                        value={data.api_key}
                                        onChange={(e) => setData('api_key', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.api_key} className="mt-2" />
                                </div>

                                <div>
                                    <Label htmlFor="secret_key">Secret Key (Optional)</Label>
                                    <Input
                                        id="secret_key"
                                        type="password"
                                        value={data.secret_key}
                                        onChange={(e) => setData('secret_key', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.secret_key} className="mt-2" />
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', !!checked)}
                                    />
                                    <Label htmlFor="is_active" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Is Active
                                    </Label>
                                    <InputError message={errors.is_active} className="mt-2" />
                                </div>

                                <div className="flex items-center justify-end gap-4">
                                    <Link href={llmProviders.index.url()}>
                                        <Button variant="outline">Cancel</Button>
                                    </Link>
                                    <Button type="submit" disabled={processing}>
                                        Create Provider
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
