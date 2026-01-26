import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useState, useEffect, useRef } from 'react';
import { action, restart, switchMode, regenerate } from '@/routes/games/play';
import { RefreshCw, Send, History, Cpu, User as UserIcon, MessageSquare, ChevronDown, ChevronUp } from 'lucide-react';
import { BreadcrumbItem } from '@/types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

export default function Play({ game, session, currentNode, dynamicState, availableModels, errors }: { game: any; session: any; currentNode: any; dynamicState: any; availableModels: any[]; errors?: any }) {
    const [inputText, setInputText] = useState('');
    const [selectedModel, setSelectedModel] = useState(availableModels[0]?.identifier || 'gemini-2.5-flash');
    const [showHistory, setShowHistory] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);

    const history = session.state_histories || [];
    const displayContent = dynamicState?.content || currentNode?.content || "The story continues...";
    const displayChoices = dynamicState?.choices || currentNode?.choices || [];

    useEffect(() => {
        const start = router.on('start', () => setIsProcessing(true));
        const finish = router.on('finish', () => setIsProcessing(false));
        return () => {
            start();
            finish();
        };
    }, []);

    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [displayContent, showHistory]);

    const handleChoiceClick = (choice: any) => {
        if (isProcessing) return;

        router.post(action.url([game.id, session.id]), {
            session_id: session.id,
            action_type: choice.target_node_id ? 'choice' : 'text',
            choice_id: choice.id || null,
            target_node_id: choice.target_node_id || null,
            input_text: choice.target_node_id ? null : choice.label,
            model: selectedModel,
        });
    };

    const handleTextSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isProcessing || !inputText.trim()) return;

        router.post(action.url([game.id, session.id]), {
            session_id: session.id,
            action_type: 'text',
            input_text: inputText,
            model: selectedModel,
        }, {
            onSuccess: () => setInputText('')
        });
    };

    const handleRestart = () => {
        if (confirm('Are you sure you want to restart your adventure? All progress will be lost.')) {
            router.post(restart.url([game.id, session.id]));
        }
    };

    const handleModeSwitch = (newMode: 'standard' | 'llm') => {
        router.post(switchMode.url([game.id, session.id]), { mode: newMode });
    };

    const handleRegenerate = () => {
        if (isProcessing) return;
        router.post(regenerate.url([game.id, session.id]), {
            model: selectedModel,
        });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'My Games', href: '/games' },
        { title: game.title, href: `/games/${game.id}/play` },
        { title: `Session #${session.id}`, href: `/games/${game.id}/play/${session.id}` },
    ];

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title={`Play ${game.title}`} />

            <div className="py-6 h-full overflow-hidden">
                <div className="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8 h-full flex flex-col gap-6">

                    {/* Header Controls (Replacement for the old header prop) */}
                    <div className="flex justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border">
                        <div className="flex items-center gap-4">
                            <h2 className="text-sm font-bold leading-tight text-gray-800 dark:text-gray-200 uppercase tracking-widest">{game.title}</h2>
                            <div className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 ${session.mode === 'llm'
                                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                }`}>
                                {session.mode === 'llm' ? <Cpu className="h-3 w-3" /> : <UserIcon className="h-3 w-3" />}
                                {session.mode} Mode
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {game.settings?.llm_enabled && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleModeSwitch(session.mode === 'llm' ? 'standard' : 'llm')}
                                    className="h-8 gap-2 text-[11px]"
                                >
                                    <RefreshCw className={`h-3 w-3 ${isProcessing ? 'animate-spin' : ''}`} />
                                    Switch to {session.mode === 'llm' ? 'Standard' : 'LLM'}
                                </Button>
                            )}
                            <Button variant="ghost" size="sm" onClick={handleRestart} className="h-8 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 gap-2 text-[11px] font-bold uppercase tracking-tighter">
                                <RefreshCw className="h-3 w-3" />
                                Restart
                            </Button>
                        </div>
                    </div>

                    {
                        errors?.error ? <Alert className='bg-red-50 dark:bg-red-900/20 border-red-500 dark:border-red-900/20 text-red-600 dark:text-red-400'>
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription className='font-mono text-xs'>{errors.error}</AlertDescription>
                        </Alert> : null
                    }

                    {/* Main Content Area */}
                    <Card className="flex-1 flex flex-col border-none shadow-xl bg-white dark:bg-gray-900 overflow-hidden relative" style={{ minHeight: '60vh' }}>
                        <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                        <CardHeader className="flex flex-row items-center justify-between border-b bg-gray-50/50 dark:bg-gray-800/30 py-3 px-6">
                            <CardTitle className="text-[11px] font-bold text-gray-500 dark:text-gray-400 flex items-center gap-2 uppercase tracking-widest text-indigo-600">
                                <MessageSquare className="h-3 w-3" />
                                Current Narrative
                            </CardTitle>
                            {session.mode === 'llm' && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowHistory(!showHistory)}
                                    className="h-7 text-[10px] gap-1.5 uppercase font-bold tracking-tighter"
                                >
                                    <History className="h-3 w-3" />
                                    {showHistory ? 'Hide Logs' : 'View Logs'}
                                    {showHistory ? <ChevronUp className="h-2.5 w-2.5" /> : <ChevronDown className="h-2.5 w-2.5" />}
                                </Button>
                            )}
                        </CardHeader>

                        <ScrollArea className="flex-1 h-48" viewportRef={scrollRef}>
                            <CardContent className="p-0 h-48">
                                {showHistory && history.length > 0 && (
                                    <div className="bg-gray-50 dark:bg-gray-800/50 p-6 border-b space-y-4">
                                        <div className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 border-b pb-2">Adventure Log</div>
                                        {history.map((msg: any, i: number) => (
                                            <div key={i} className={`flex gap-3 text-sm ${msg.role === 'user' ? 'justify-end' : ''}`}>
                                                <div className={`max-w-[80%] p-3 rounded-2xl ${msg.role === 'user'
                                                    ? 'bg-indigo-600 text-white rounded-tr-none'
                                                    : 'bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-tl-none shadow-sm'
                                                    }`}>
                                                    <div className="text-[9px] font-bold opacity-50 mb-1 uppercase tracking-tighter">
                                                        {msg.role === 'user' ? 'You' : 'Narrator'}
                                                    </div>
                                                    <div className="prose prose-sm dark:prose-invert max-w-none text-xs leading-relaxed">
                                                        <ReactMarkdown remarkPlugins={[remarkGfm]}>{msg.content}</ReactMarkdown>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="p-8 md:p-12 min-h-48">
                                    <div className={`prose prose-lg dark:prose-invert max-w-none font-serif leading-relaxed text-gray-800 dark:text-gray-200 ${isProcessing ? 'opacity-50 blur-[1px]' : ''} transition-all duration-300`}>
                                        <ReactMarkdown remarkPlugins={[remarkGfm]}>{displayContent}</ReactMarkdown>
                                    </div>

                                    {game.settings?.allow_llm_regeneration && session.mode === 'llm' && !isProcessing && (
                                        <div className="mt-4 flex justify-end">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={handleRegenerate}
                                                className="h-8 gap-2 text-[11px] border-indigo-200 hover:bg-indigo-50 dark:border-indigo-900/50 dark:hover:bg-indigo-900/20"
                                            >
                                                <RefreshCw className="h-3 w-3" />
                                                Regenerate Response
                                            </Button>
                                        </div>
                                    )}

                                    {isProcessing && (
                                        <div className="mt-8 flex items-center gap-3 text-indigo-500 font-medium italic animate-pulse">
                                            <RefreshCw className="h-4 w-4 animate-spin" />
                                            <span className="text-sm font-bold uppercase tracking-widest">The story is unfolding...</span>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </ScrollArea>
                    </Card>

                    {/* Controls Area */}
                    <Card className="border-t-4 border-t-indigo-500 shadow-2xl">
                        <CardHeader className="py-3 border-b bg-gray-50/30">
                            <CardTitle className="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 tracking-[0.2em] uppercase">
                                Choose Your Path
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6">
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {displayChoices.map((choice: any, idx: number) => (
                                        <Button
                                            key={choice.id || idx}
                                            variant="secondary"
                                            disabled={isProcessing}
                                            className="h-auto py-3 px-5 text-left justify-start relative group overflow-hidden transition-all hover:ring-1 hover:ring-indigo-500 bg-gray-50 hover:bg-white dark:bg-gray-800 dark:hover:bg-gray-700 border border-transparent hover:border-indigo-100"
                                            onClick={() => handleChoiceClick(choice)}
                                        >
                                            <div className="absolute left-0 top-0 h-full w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            <span className="shrink-0 w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-[10px] font-bold text-indigo-600 dark:text-indigo-400 mr-3">
                                                {idx + 1}
                                            </span>
                                            <span className="font-medium text-xs text-gray-700 dark:text-gray-200 text-wrap">{choice.label}</span>
                                        </Button>
                                    ))}
                                </div>

                                {game.settings?.llm_enabled && (
                                    <>
                                        <div className="relative flex items-center py-2">
                                            <div className="flex-grow border-t border-gray-100 dark:border-gray-800"></div>
                                            <span className="flex-shrink-0 mx-4 text-[9px] font-bold text-gray-300 uppercase tracking-[0.4em]">or carve your own way</span>
                                            <div className="flex-grow border-t border-gray-100 dark:border-gray-800"></div>
                                        </div>

                                        <form onSubmit={handleTextSubmit} className="flex gap-2">
                                            <div className="relative flex-1 group">
                                                <Input
                                                    placeholder={session.mode === 'llm' ? "Speak to the world..." : "Go to LLM mode to use text actions"}
                                                    value={inputText}
                                                    onChange={(e) => setInputText(e.target.value)}
                                                    disabled={isProcessing || session.mode !== 'llm'}
                                                    className="pl-10 h-10 bg-white dark:bg-gray-800 border focus-visible:ring-indigo-500 rounded-lg text-sm"
                                                />
                                                <MessageSquare className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 group-focus-within:text-indigo-500" />
                                            </div>
                                            <Button
                                                type="submit"
                                                disabled={isProcessing || !inputText.trim() || session.mode !== 'llm'}
                                                className="h-10 px-6 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg transition-all active:scale-95 gap-2 text-xs font-bold uppercase tracking-widest"
                                            >
                                                {isProcessing ? <RefreshCw className="h-3 w-3 animate-spin" /> : <Send className="h-3 w-3" />}
                                                Confirm
                                            </Button>
                                        </form>
                                        <div className="flex justify-end mt-2">
                                            <Select value={selectedModel} onValueChange={setSelectedModel} disabled={isProcessing || session.mode !== 'llm'}>
                                                <SelectTrigger className="md:w-1/4 h-7 text-[10px] uppercase font-bold tracking-wider bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                                    <SelectValue placeholder="Select Model" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {availableModels.map((m) => (
                                                        <SelectItem key={m.id} value={m.identifier}>
                                                            {m.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        {session.mode !== 'llm' && (
                                            <p className="text-[9px] text-center text-gray-400 italic">
                                                Switch to LLM Mode in the adventure menu to use free-text actions.
                                            </p>
                                        )}
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout >
    );
}
