import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';

export default function Show({ game }) {
    const [selectedNode, setSelectedNode] = useState(null);

    const createNodeForm = useForm({
        game_id: game.id,
        title: 'New Node',
        content: '',
        is_start_node: false,
    });

    const updateNodeForm = useForm({
        title: '',
        content: '',
        is_start_node: false,
        choices: [],
    });

    const handleCreateNode = () => {
        createNodeForm.post(route('story-nodes.store'), {
            onSuccess: () => createNodeForm.reset(),
        });
    };

    const handleSelectNode = (node) => {
        setSelectedNode(node);
        updateNodeForm.setData({
            title: node.title || '',
            content: node.content,
            is_start_node: node.is_start_node,
            choices: node.choices.map(c => ({
                id: c.id,
                label: c.label,
                target_node_id: c.target_node_id,
            })),
        });
    };

    const handleUpdateNode = (e) => {
        e.preventDefault();
        if (!selectedNode) return;
        updateNodeForm.put(route('story-nodes.update', selectedNode.id), {
            onSuccess: () => {
                // Ideally refresh selectedNode from updated props
            }
        });
    };

    const handleDeleteNode = () => {
        if (!selectedNode || !confirm('Are you sure?')) return;
        router.delete(route('story-nodes.destroy', selectedNode.id), {
            onSuccess: () => setSelectedNode(null),
        });
    };

    const addChoice = () => {
        const choices = [...updateNodeForm.data.choices, { label: 'New Choice', target_node_id: '' }];
        updateNodeForm.setData('choices', choices);
    };

    const updateChoice = (index, field, value) => {
        const choices = [...updateNodeForm.data.choices];
        choices[index][field] = value;
        updateNodeForm.setData('choices', choices);
    };

    const removeChoice = (index) => {
        const choices = updateNodeForm.data.choices.filter((_, i) => i !== index);
        updateNodeForm.setData('choices', choices);
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Editor: {game.title}</h2>}
        >
            <Head title={`Edit ${game.title}`} />

            <div className="py-12 h-screen max-h-[calc(100vh-64px)] flex flex-col">
                <div className="mx-auto w-full max-w-7xl sm:px-6 lg:px-8 flex-1 flex gap-6 overflow-hidden">

                    {/* Nodes List */}
                    <Card className="w-1/3 flex flex-col">
                        <CardHeader>
                            <CardTitle className="flex justify-between items-center">
                                Story Nodes
                                <Button size="sm" onClick={handleCreateNode} disabled={createNodeForm.processing}>
                                    + Add Node
                                </Button>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 overflow-hidden p-0">
                            <ScrollArea className="h-full px-6 pb-4">
                                <div className="space-y-2">
                                    {game.story_nodes.map((node) => (
                                        <div
                                            key={node.id}
                                            onClick={() => handleSelectNode(node)}
                                            className={`p-3 rounded-lg border cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 ${selectedNode?.id === node.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700'
                                                }`}
                                        >
                                            <div className="font-medium">{node.title || 'Untitled Node'}</div>
                                            <div className="text-xs text-gray-500 truncate">{node.content}</div>
                                            {node.is_start_node && <span className="text-xs text-green-600 font-bold">START</span>}
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </CardContent>
                    </Card>

                    {/* Editor Panel */}
                    <Card className="flex-1 flex flex-col overflow-auto">
                        {selectedNode ? (
                            <>
                                <CardHeader>
                                    <CardTitle>Edit Node</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleUpdateNode} className="space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="title">Node Title</Label>
                                                <Input
                                                    id="title"
                                                    value={updateNodeForm.data.title}
                                                    onChange={(e) => updateNodeForm.setData('title', e.target.value)}
                                                />
                                            </div>
                                            <div className="flex items-end pb-2">
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="checkbox"
                                                        id="is_start_node"
                                                        checked={updateNodeForm.data.is_start_node}
                                                        onChange={(e) => updateNodeForm.setData('is_start_node', e.target.checked)}
                                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                    />
                                                    <Label htmlFor="is_start_node">Set as Start Node</Label>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <Label htmlFor="content">Story Content</Label>
                                            <Textarea
                                                id="content"
                                                rows={6}
                                                value={updateNodeForm.data.content}
                                                onChange={(e) => updateNodeForm.setData('content', e.target.value)}
                                                required
                                            />
                                        </div>

                                        <Separator />

                                        <div>
                                            <div className="flex justify-between items-center mb-4">
                                                <Label className="text-lg">Choices</Label>
                                                <Button type="button" size="sm" variant="outline" onClick={addChoice}>
                                                    Add Choice
                                                </Button>
                                            </div>
                                            <div className="space-y-4">
                                                {updateNodeForm.data.choices.map((choice, index) => (
                                                    <div key={index} className="flex gap-4 items-start p-3 border rounded-md">
                                                        <div className="flex-1 space-y-2">
                                                            <Input
                                                                placeholder="Choice Label"
                                                                value={choice.label}
                                                                onChange={(e) => updateChoice(index, 'label', e.target.value)}
                                                                required
                                                            />
                                                            <select
                                                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                                value={choice.target_node_id || ''}
                                                                onChange={(e) => updateChoice(index, 'target_node_id', e.target.value)}
                                                            >
                                                                <option value="">Select Target Node...</option>
                                                                {game.story_nodes.map(n => (
                                                                    <option key={n.id} value={n.id}>
                                                                        {n.title || `Node #${n.id}`} {n.id === selectedNode.id ? '(Self)' : ''}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="icon"
                                                            onClick={() => removeChoice(index)}
                                                        >
                                                            X
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="flex justify-between pt-4">
                                            <Button type="button" variant="destructive" onClick={handleDeleteNode}>Delete Node</Button>
                                            <Button type="submit" disabled={updateNodeForm.processing}>Save Changes</Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </>
                        ) : (
                            <div className="flex items-center justify-center h-full text-muted-foreground">
                                Select a node to edit
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
