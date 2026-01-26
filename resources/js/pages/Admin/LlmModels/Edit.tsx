import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { BreadcrumbItem } from '@/types';
import { Cpu } from 'lucide-react';
import InputError from '@/components/input-error';
import llmModels from '@/routes/admin/llm-models';

interface Provider {
    id: number;
    name: string;
}

interface LlmModel {
    id: number;
    llm_provider_id: number;
    name: string;
    identifier: string;
    endpoint_url: string | null;
    is_active: boolean;
}

export default function Edit({ model, providers }: { model: LlmModel; providers: Provider[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'LLM Models', href: '/admin/llm-models' },
        { title: 'Edit', href: `/admin/llm-models/${model.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        llm_provider_id: model.llm_provider_id.toString(),
        name: model.name,
        identifier: model.identifier,
        endpoint_url: model.endpoint_url || '',
        is_active: model.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(llmModels.update.url(model.id));
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${model.name}`} />

            <div className="py-6">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Cpu className="h-6 w-6 text-indigo-600" />
                            Edit LLM Model
                        </h1>
                    </div>

                    <Card className="py-5">
                        <CardHeader>
                            <CardTitle>Model Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <Label htmlFor="llm_provider_id">Provider</Label>
                                    <Select
                                        value={data.llm_provider_id}
                                        onValueChange={(value) => setData('llm_provider_id', value)}
                                    >
                                        <SelectTrigger className="mt-1 w-full">
                                            <SelectValue placeholder="Select a provider" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {providers.map((provider) => (
                                                <SelectItem key={provider.id} value={provider.id.toString()}>
                                                    {provider.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.llm_provider_id} className="mt-2" />
                                </div>

                                <div>
                                    <Label htmlFor="name">Model Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                <div>
                                    <Label htmlFor="identifier">Model Identifier</Label>
                                    <Input
                                        id="identifier"
                                        value={data.identifier}
                                        onChange={(e) => setData('identifier', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.identifier} className="mt-2" />
                                </div>

                                <div>
                                    <Label htmlFor="endpoint_url">Endpoint URL (Optional)</Label>
                                    <Input
                                        id="endpoint_url"
                                        value={data.endpoint_url}
                                        onChange={(e) => setData('endpoint_url', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.endpoint_url} className="mt-2" />
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
                                    <Link href={llmModels.index.url()}>
                                        <Button variant="outline">Cancel</Button>
                                    </Link>
                                    <Button type="submit" disabled={processing}>
                                        Update Model
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
