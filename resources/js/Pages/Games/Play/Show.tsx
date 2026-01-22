import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useState, useEffect } from 'react';

export default function Play({ game, session, currentNode, flash }) {
    const [history, setHistory] = useState(session.state_history || []);
    const [dynamicState, setDynamicState] = useState(flash?.dynamicState || null);
    const [inputText, setInputText] = useState('');
    const [showHistory, setShowHistory] = useState(false);

    useEffect(() => {
        setHistory(session.state_history || []);
        if (flash?.dynamicState) {
            setDynamicState(flash.dynamicState);
        } else {
            setDynamicState(null);
        }
    }, [session, flash]);

    const handleChoiceClick = (choice) => {
        if (choice.target_node_id) {
            router.post(route('games.play.action', game.id), {
                session_id: session.id,
                action_type: 'choice',
                choice_id: choice.id || null, // Assuming standard choices have IDs
            });
        } else {
            // Dynamic/LLM choice without ID, treat as text action
            router.post(route('games.play.action', game.id), {
                session_id: session.id,
                action_type: 'text',
                input_text: choice.label,
            });
        }
    };

    const handleTextSubmit = (e) => {
        e.preventDefault();
        router.post(route('games.play.action', game.id), {
            session_id: session.id,
            action_type: 'text',
            input_text: inputText,
        }, {
            onSuccess: () => setInputText('')
        });
    };

    const displayContent = dynamicState?.content || currentNode?.content || "The story continues...";
    const displayChoices = dynamicState?.choices || currentNode?.choices || [];

    return (
        <AuthenticatedLayout
            header={<div className="flex justify-between items-center">
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Playing: {game.title}</h2>
                <div className="text-sm px-3 py-1 bg-gray-200 rounded-full dark:text-gray-900 capitalize">Mode: {session.mode}</div>
            </div>}
        >
            <Head title={`Play ${game.title}`} />

            <div className="py-12 h-[calc(100vh-64px)] flex flex-col">
                <div className="mx-auto w-full max-w-4xl sm:px-6 lg:px-8 flex-1 flex flex-col">

                    <Card className="flex-1 flex flex-col mb-6 overflow-hidden">
                        <CardContent className="flex-1 p-6 overflow-auto bg-gray-50 dark:bg-gray-900/50 font-serif text-lg leading-relaxed whitespace-pre-wrap">
                            {session.mode === 'llm' && (
                                <div className="mb-4">
                                    <Button variant="ghost" size="sm" onClick={() => setShowHistory(!showHistory)}>
                                        {showHistory ? 'Hide History' : 'Show History'}
                                    </Button>
                                    {showHistory && (
                                        <div className="mt-2 text-sm text-gray-500 space-y-2 border-l-2 pl-4">
                                            {history.map((msg, i) => (
                                                <div key={i} className={msg.role === 'user' ? 'font-bold' : ''}>
                                                    {msg.role}: {msg.content}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}

                            <div>{displayContent}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">What do you do?</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {displayChoices.map((choice, idx) => (
                                        <Button
                                            key={choice.id || idx}
                                            variant="outline"
                                            className="h-auto py-4 text-left justify-start whitespace-normal"
                                            onClick={() => handleChoiceClick(choice)}
                                        >
                                            <span className="font-bold mr-2">{idx + 1}.</span> {choice.label}
                                        </Button>
                                    ))}
                                </div>

                                {session.mode === 'llm' && (
                                    <>
                                        <div className="relative flex items-center py-2">
                                            <div className="flex-grow border-t border-gray-300"></div>
                                            <span className="flex-shrink-0 mx-4 text-gray-400">OR</span>
                                            <div className="flex-grow border-t border-gray-300"></div>
                                        </div>

                                        <form onSubmit={handleTextSubmit} className="flex gap-4">
                                            <Input
                                                placeholder="Describe your action..."
                                                value={inputText}
                                                onChange={(e) => setInputText(e.target.value)}
                                                className="flex-1"
                                            />
                                            <Button type="submit" disabled={!inputText}>
                                                Send
                                            </Button>
                                        </form>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
