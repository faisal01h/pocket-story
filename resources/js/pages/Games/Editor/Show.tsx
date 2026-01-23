import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { store, update, destroy } from '@/routes/story-nodes';
import { PlusCircle, Trash2, Save, FileText, AlignLeft, Network, Shapes } from 'lucide-react';
import StoryTree from './Partials/StoryTree';
import StoryDiagram from './Partials/StoryDiagram';

export default function Show({ game }) {
    const [selectedNode, setSelectedNode] = useState(null);
    const [viewMode, setViewMode] = useState('list'); // 'list' | 'tree' | 'diagram'
    const storyNodes = game.story_nodes || [];

    const createNodeForm = useForm({
        game_id: game.id,
        title: 'New Node',
        content: 'Write your story here...',
        is_start_node: false,
    });

    const updateNodeForm = useForm({
        title: '',
        content: '',
        is_start_node: false,
        choices: [],
    });

    const handleCreateNode = () => {
        createNodeForm.post(store.url(), {
            onSuccess: () => {
                createNodeForm.reset();
                createNodeForm.setData('game_id', game.id);
            },
        });
    };

    const handleSelectNode = (node) => {
        setSelectedNode(node);
        updateNodeForm.setData({
            title: node.title || '',
            content: node.content,
            is_start_node: node.is_start_node,
            choices: (node.choices || []).map(c => ({
                id: c.id,
                label: c.label,
                target_node_id: c.target_node_id,
            })),
        });
    };

    const handleUpdateNode = (e) => {
        e.preventDefault();
        if (!selectedNode) return;
        updateNodeForm.put(update.url(selectedNode.id), {
            preserveScroll: true,
        });
    };

    const handleDeleteNode = () => {
        if (!selectedNode || !confirm('Are you sure you want to delete this node?')) return;
        router.delete(destroy.url(selectedNode.id), {
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
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Story Editor: {game.title}
                    </h2>
                    <div className="text-sm text-gray-500 dark:text-gray-400">
                        {storyNodes.length} {storyNodes.length === 1 ? 'node' : 'nodes'}
                    </div>
                </div>
            }
        >
            <Head title={`Edit ${game.title}`} />

            <div className="py-6 h-[calc(100vh-80px)]">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 h-full flex gap-6">

                    {/* Nodes List */}
                    <Card className="w-1/3 flex flex-col shadow-lg">
                        <CardHeader className="border-b bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950 py-5 rounded-t-lg">
                            <div className="flex justify-between items-center">
                                <div>
                                    <CardTitle className="text-lg">Story Nodes</CardTitle>
                                    <div className="flex bg-white/50 dark:bg-black/20 rounded-md p-0.5 mt-1 self-start">
                                        <button
                                            onClick={() => setViewMode('list')}
                                            className={`p-1 rounded-sm transition-all ${viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'}`}
                                            title="List View"
                                        >
                                            <AlignLeft className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => setViewMode('tree')}
                                            className={`p-1 rounded-sm transition-all ${viewMode === 'tree' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'}`}
                                            title="Tree View"
                                        >
                                            <Network className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => setViewMode('diagram')}
                                            className={`p-1 rounded-sm transition-all ${viewMode === 'diagram' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'}`}
                                            title="Diagram View"
                                        >
                                            <Shapes className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <Button
                                    size="sm"
                                    onClick={handleCreateNode}
                                    disabled={createNodeForm.processing}
                                    className="gap-2"
                                >
                                    <PlusCircle className="h-4 w-4" />
                                    Add
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="flex-1 overflow-hidden p-0">
                            {storyNodes.length === 0 ? (
                                <div className="flex flex-col items-center justify-center h-full p-8 text-center">
                                    <FileText className="h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" />
                                    <h3 className="font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        No Story Nodes Yet
                                    </h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Create your first node to start building your story
                                    </p>
                                    <Button
                                        onClick={handleCreateNode}
                                        disabled={createNodeForm.processing}
                                        className="gap-2"
                                    >
                                        <PlusCircle className="h-4 w-4" />
                                        Create First Node
                                    </Button>
                                </div>
                            ) : (
                                <ScrollArea className="h-full">
                                    {viewMode === 'diagram' && (
                                        <div className="p-4 h-[600px]">
                                            <StoryDiagram
                                                nodes={storyNodes}
                                                onSelectNode={handleSelectNode}
                                            />
                                        </div>
                                    )}

                                    {viewMode === 'tree' && (
                                        <div className="p-4">
                                            <StoryTree
                                                nodes={storyNodes}
                                                selectedNodeId={selectedNode?.id}
                                                onSelectNode={handleSelectNode}
                                            />
                                        </div>
                                    )}

                                    {viewMode === 'list' && (
                                        <div className="space-y-2 p-4">
                                            {storyNodes.map((node) => (
                                                <div
                                                    key={node.id}
                                                    onClick={() => handleSelectNode(node)}
                                                    className={`p-4 rounded-lg border-2 cursor-pointer transition-all duration-200 ${selectedNode?.id === node.id
                                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 shadow-md scale-[1.02]'
                                                        : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                                                        }`}
                                                >
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="flex-1 min-w-0">
                                                            <div className="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                                {node.title || 'Untitled Node'}
                                                            </div>
                                                            <div className="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                                                {node.content || 'No content'}
                                                            </div>
                                                        </div>
                                                        {node.is_start_node && (
                                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                START
                                                            </span>
                                                        )}
                                                    </div>
                                                    {node.choices && node.choices.length > 0 && (
                                                        <div className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                            {node.choices.length} {node.choices.length === 1 ? 'choice' : 'choices'}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </ScrollArea>
                            )}
                        </CardContent>
                    </Card>

                    {/* Editor Panel */}
                    <Card className="flex-1 flex flex-col shadow-lg overflow-hidden">
                        {selectedNode ? (
                            <>
                                <CardHeader className="border-b bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950 py-5 rounded-t-lg">
                                    <CardTitle className="text-lg">Edit Node</CardTitle>
                                    <CardDescription className="text-xs mt-1">
                                        Modify content and manage choices
                                    </CardDescription>
                                </CardHeader>
                                <ScrollArea className="flex-1">
                                    <CardContent className="p-6">
                                        <form onSubmit={handleUpdateNode} className="space-y-6">
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <Label htmlFor="title" className="text-sm font-semibold">Node Title</Label>
                                                    <Input
                                                        id="title"
                                                        value={updateNodeForm.data.title}
                                                        onChange={(e) => updateNodeForm.setData('title', e.target.value)}
                                                        className="mt-1"
                                                        placeholder="Enter node title"
                                                    />
                                                </div>
                                                <div className="flex items-end pb-1">
                                                    <div className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id="is_start_node"
                                                            checked={updateNodeForm.data.is_start_node}
                                                            onChange={(e) => updateNodeForm.setData('is_start_node', e.target.checked)}
                                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-4 w-4"
                                                        />
                                                        <Label htmlFor="is_start_node" className="text-sm cursor-pointer">
                                                            Set as Start Node
                                                        </Label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <Label htmlFor="content" className="text-sm font-semibold">Story Content</Label>
                                                <Textarea
                                                    id="content"
                                                    rows={8}
                                                    value={updateNodeForm.data.content}
                                                    onChange={(e) => updateNodeForm.setData('content', e.target.value)}
                                                    required
                                                    className="mt-1 font-serif"
                                                    placeholder="Write your story content here..."
                                                />
                                            </div>

                                            <Separator />

                                            <div>
                                                <div className="flex justify-between items-center mb-4">
                                                    <div>
                                                        <Label className="text-base font-semibold">Choices</Label>
                                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            Define player options and story branches
                                                        </p>
                                                    </div>
                                                    <Button type="button" size="sm" variant="outline" onClick={addChoice} className="gap-2">
                                                        <PlusCircle className="h-4 w-4" />
                                                        Add Choice
                                                    </Button>
                                                </div>
                                                <div className="space-y-3">
                                                    {updateNodeForm.data.choices.length === 0 ? (
                                                        <div className="text-center py-8 border-2 border-dashed rounded-lg">
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                                No choices yet. Add a choice to create story branches.
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        updateNodeForm.data.choices.map((choice, index) => (
                                                            <div key={index} className="flex gap-3 items-start p-4 border-2 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                                                                <div className="flex-1 space-y-3">
                                                                    <div>
                                                                        <Label className="text-xs text-gray-600 dark:text-gray-400">Choice Text</Label>
                                                                        <Input
                                                                            placeholder="What does the player see?"
                                                                            value={choice.label}
                                                                            onChange={(e) => updateChoice(index, 'label', e.target.value)}
                                                                            required
                                                                            className="mt-1"
                                                                        />
                                                                    </div>
                                                                    <div>
                                                                        <Label className="text-xs text-gray-600 dark:text-gray-400">Target Node</Label>
                                                                        <select
                                                                            className="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                                            value={choice.target_node_id || ''}
                                                                            onChange={(e) => updateChoice(index, 'target_node_id', e.target.value)}
                                                                        >
                                                                            <option value="">-- Select destination --</option>
                                                                            {storyNodes.map(n => (
                                                                                <option key={n.id} value={n.id}>
                                                                                    {n.title || `Node #${n.id}`} {n.id === selectedNode.id ? '(Current)' : ''}
                                                                                </option>
                                                                            ))}
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <Button
                                                                    type="button"
                                                                    variant="destructive"
                                                                    size="icon"
                                                                    onClick={() => removeChoice(index)}
                                                                    className="mt-6"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        ))
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex justify-between pt-4 border-t">
                                                <Button type="button" variant="destructive" onClick={handleDeleteNode} className="gap-2">
                                                    <Trash2 className="h-4 w-4" />
                                                    Delete Node
                                                </Button>
                                                <Button type="submit" disabled={updateNodeForm.processing} className="gap-2">
                                                    <Save className="h-4 w-4" />
                                                    Save Changes
                                                </Button>
                                            </div>
                                        </form>
                                    </CardContent>
                                </ScrollArea>
                            </>
                        ) : (
                            <div className="flex flex-col items-center justify-center h-full p-8 text-center">
                                <div className="rounded-full bg-gray-100 dark:bg-gray-800 p-6 mb-4">
                                    <FileText className="h-12 w-12 text-gray-400 dark:text-gray-500" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    No Node Selected
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                                    {storyNodes.length === 0
                                        ? "Create your first node to get started"
                                        : "Select a node from the list to edit its content and choices"
                                    }
                                </p>
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
