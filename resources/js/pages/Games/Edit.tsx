import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { update } from '@/routes/games';

export default function Edit({ game }) {
    const { data, setData, put, processing, errors } = useForm({
        title: game.title,
        description: game.description || '',
        settings: {
            default_mode: game.settings?.default_mode || 'standard',
            llm_enabled: game.settings?.llm_enabled || false,
            allow_llm_regeneration: game.settings?.allow_llm_regeneration || false,
        },
        llm_guidelines: game.llm_guidelines || '',
        is_public: !!game.is_public,
        slug: game.slug || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(update.url(game.id));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Edit Game</h2>}
        >
            <Head title={`Edit ${game.title}`} />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <Card className="py-5">
                        <CardHeader>
                            <CardTitle>Edit Game Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <Label htmlFor="title">Title</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        required
                                        className="mt-1 block w-full"
                                    />
                                    {errors.title && <div className="text-red-500 text-sm mt-1">{errors.title}</div>}
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="slug">Game Code (URL Slug)</Label>
                                        <Input
                                            id="slug"
                                            value={data.slug}
                                            onChange={(e) => setData('slug', e.target.value)}
                                            placeholder="my-awesome-game"
                                            className="mt-1 block w-full"
                                        />
                                        <p className="text-[10px] text-gray-500 mt-1">Leave empty to auto-generate from title.</p>
                                        {errors.slug && <div className="text-red-500 text-sm mt-1">{errors.slug}</div>}
                                    </div>

                                    <div className="flex flex-col justify-center">
                                        <div className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                id="is_public"
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                checked={data.is_public}
                                                onChange={(e) => setData('is_public', e.target.checked)}
                                            />
                                            <Label htmlFor="is_public">Make Game Discoverable (Public)</Label>
                                        </div>
                                        {errors.is_public && <div className="text-red-500 text-sm mt-1">{errors.is_public}</div>}
                                    </div>
                                </div>

                                <div>
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    {errors.description && <div className="text-red-500 text-sm mt-1">{errors.description}</div>}
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="llm_enabled"
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            checked={data.settings.llm_enabled}
                                            onChange={(e) => setData('settings', { ...data.settings, llm_enabled: e.target.checked })}
                                        />
                                        <Label htmlFor="llm_enabled">Enable LLM Features (Dynamic Storytelling)</Label>
                                    </div>

                                    {data.settings.llm_enabled && (
                                        <div className="flex items-center space-x-2 pl-6">
                                            <input
                                                type="checkbox"
                                                id="allow_llm_regeneration"
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                checked={data.settings.allow_llm_regeneration}
                                                onChange={(e) => setData('settings', { ...data.settings, allow_llm_regeneration: e.target.checked })}
                                            />
                                            <Label htmlFor="allow_llm_regeneration">Allow LLM Response Regeneration</Label>
                                        </div>
                                    )}

                                    {data.settings.llm_enabled && (
                                        <div className="pl-6 border-l-2 border-indigo-100 dark:border-indigo-900 ml-1">
                                            <Label htmlFor="llm_guidelines">LLM Guidelines / System Prompt</Label>
                                            <p className="text-xs text-gray-500 mb-2">
                                                Provide specific instructions for the AI on how to narrate this adventure (e.g., tone, rules, prohibited content).
                                            </p>
                                            <Textarea
                                                id="llm_guidelines"
                                                value={data.llm_guidelines}
                                                onChange={(e) => setData('llm_guidelines', e.target.value)}
                                                className="mt-1 block w-full font-mono text-sm"
                                                rows={5}
                                                placeholder="You are a narrator for a dark fantasy RPG..."
                                            />
                                            {errors.llm_guidelines && <div className="text-red-500 text-sm mt-1">{errors.llm_guidelines}</div>}
                                        </div>
                                    )}
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        Save Changes
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
