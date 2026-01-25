import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, usePage, useForm, router, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { BreadcrumbItem } from '@/types';
import { ArrowLeft, User as UserIcon, Keyboard, Key, Cpu, History, Zap, ShieldCheck } from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { useState } from 'react';

export default function Show({ user, redeems, llmRequests, availableRoles }: { user: any; redeems: any[]; llmRequests: any[]; availableRoles: any[] }) {
    const { session } = usePage().props as any;
    const aiSummary = session?.ai_summary;
    const aiError = (usePage().props.errors as any)?.ai_error;
    const [isSummarizing, setIsSummarizing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Users', href: '/admin/users' },
        { title: user.name, href: `/admin/users/${user.id}` },
    ];

    const { data, setData, put, processing: rolesProcessing } = useForm({
        roles: user.roles.map((r: any) => r.name) as string[],
    });

    const toggleRole = (name: string) => {
        if (data.roles.includes(name)) {
            setData('roles', data.roles.filter(r => r !== name));
        } else {
            setData('roles', [...data.roles, name]);
        }
    };

    const handleUpdateRoles = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    const handleSummarize = () => {
        setIsSummarizing(true);
        router.post(`/admin/users/${user.id}/summarize`, {}, {
            onFinish: () => setIsSummarizing(false),
        });
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title={`User: ${user.name}`} />

            <div className="py-6 min-h-screen">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href="/admin/users">
                            <Button variant="ghost" className="pl-0 gap-2">
                                <ArrowLeft className="h-4 w-4" /> Back to Users
                            </Button>
                        </Link>
                    </div>

                    <div className="flex flex-col md:flex-row items-start gap-6 mb-8">
                        <div className="h-20 w-20 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-3xl font-black shadow-xl shadow-indigo-200 dark:shadow-none">
                            {user.name.charAt(0)}
                        </div>
                        <div className="flex-1">
                            <h1 className="text-3xl font-black tracking-tight text-gray-900 dark:text-white uppercase">{user.name}</h1>
                            <p className="text-gray-500 font-mono text-sm">{user.email}</p>
                            <div className="flex flex-wrap gap-2 mt-3">
                                {user.roles.map((role: any) => (
                                    <Badge key={role.id} variant="secondary" className="uppercase font-black text-[10px] tracking-widest">{role.name}</Badge>
                                ))}
                            </div>
                        </div>
                    </div>

                    <Tabs defaultValue="overview" className="space-y-6">
                        <TabsList className="bg-white/50 dark:bg-gray-800/50 backdrop-blur border p-1 h-auto flex flex-wrap shadow-sm">
                            <TabsTrigger value="overview" className="gap-2 px-6 py-2.5">
                                <UserIcon className="h-4 w-4" /> Overview
                            </TabsTrigger>
                            <TabsTrigger value="adventures" className="gap-2 px-6 py-2.5">
                                <Keyboard className="h-4 w-4" /> Adventures
                            </TabsTrigger>
                            <TabsTrigger value="history" className="gap-2 px-6 py-2.5">
                                <Cpu className="h-4 w-4" /> LLM Requests
                            </TabsTrigger>
                            <TabsTrigger value="privileges" className="gap-2 px-6 py-2.5">
                                <Key className="h-4 w-4" /> Privileges
                            </TabsTrigger>
                            <TabsTrigger value="insights" className="gap-2 px-6 py-2.5 text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-tighter italic">
                                <Zap className="h-4 w-4 fill-current" /> AI Insights
                            </TabsTrigger>
                        </TabsList>

                        {/* OVERVIEW */}
                        <TabsContent value="overview">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <Card className='py-5 bg-gradient-to-br from-indigo-50 to-transparent dark:from-indigo-900/10'>
                                    <CardHeader>
                                        <CardTitle className="text-xs font-black uppercase tracking-widest text-indigo-500 flex items-center gap-2">
                                            <History className="h-4 w-4" /> Lifetime Stats
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6 pt-2">
                                        <div>
                                            <div className="text-3xl font-black text-gray-900 dark:text-white uppercase">{user.game_sessions?.length || 0}</div>
                                            <div className="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Total Game Sessions</div>
                                        </div>
                                        <div>
                                            <div className="text-3xl font-black text-gray-900 dark:text-white uppercase">{redeems.length}</div>
                                            <div className="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Redeemed Codes</div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card className="md:col-span-2 py-5 border-dashed border-2">
                                    <CardHeader>
                                        <CardTitle className="text-xs font-black uppercase tracking-widest text-gray-400">Financial History</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-xs text-left text-gray-500">
                                                <thead className="uppercase text-[10px] text-gray-400 border-b">
                                                    <tr>
                                                        <th className="py-3">Code</th>
                                                        <th className="py-3">Points/Type</th>
                                                        <th className="py-3">Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {redeems.map((r: any) => (
                                                        <tr key={r.id} className="border-b last:border-0 border-gray-100 dark:border-gray-800">
                                                            <td className="py-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">{r.redeem_code?.code}</td>
                                                            <td className="py-4 font-bold">{r.redeem_code?.type === 'points' ? `+${r.redeem_code?.reward_value} pts` : 'Tier Upgrade'}</td>
                                                            <td className="py-4 opacity-60 font-mono">{new Date(r.created_at).toLocaleDateString()}</td>
                                                        </tr>
                                                    ))}
                                                    {redeems.length === 0 && (
                                                        <tr><td colSpan={3} className="py-10 text-center text-gray-400 italic">No redemption history found.</td></tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        {/* ADVENTURES */}
                        <TabsContent value="adventures">
                            <Card className='py-5'>
                                <CardHeader>
                                    <CardTitle>Recent Story Sessions</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {user.game_sessions?.map((session: any) => (
                                            <Link key={session.id} href={`/games/${session.game.id}/play/${session.id}`} className="block">
                                                <div className="group flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-indigo-500 hover:bg-indigo-50/10 dark:border-gray-800 dark:hover:bg-gray-800/50 transition-all duration-200">
                                                    <div className="flex items-center gap-4">
                                                        <div className="h-10 w-10 flex-shrink-0 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center font-bold text-gray-400 group-hover:text-indigo-600 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/30 transition-colors">
                                                            {session.game.title.charAt(0)}
                                                        </div>
                                                        <div>
                                                            <div className="font-black uppercase tracking-tight text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors">{session.game.title}</div>
                                                            <div className="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Session ID: #{session.id} • Last move: {new Date(session.updated_at).toLocaleString()}</div>
                                                        </div>
                                                    </div>
                                                    <Badge className="uppercase font-bold tracking-tighter h-5">{session.mode}</Badge>
                                                </div>
                                            </Link>
                                        ))}
                                        {user.game_sessions?.length === 0 && (
                                            <div className="py-20 text-center text-gray-400">This user hasn't played any games yet.</div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* LLM Log */}
                        <TabsContent value="history">
                            <Card className='py-5'>
                                <CardHeader>
                                    <CardTitle>Recent LLM Interactions</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-xs text-left text-gray-500">
                                            <thead className="uppercase text-[10px] text-gray-400 border-b">
                                                <tr>
                                                    <th className="py-3 px-4">Game</th>
                                                    <th className="py-3 px-4">Model</th>
                                                    <th className="py-3 px-4 text-center">In / Out</th>
                                                    <th className="py-3 px-4 text-right">Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {llmRequests.map((req: any) => (
                                                    <tr key={req.id} className="border-b last:border-0 border-gray-100 dark:border-gray-800 group hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                                        <td className="py-4 px-4 font-bold text-gray-900 dark:text-white">{req.game_session?.game?.title || '-'}</td>
                                                        <td className="py-4 px-4">
                                                            <div className="flex flex-col">
                                                                <span className="font-bold text-indigo-600 dark:text-indigo-400 italic text-[10px] uppercase">{req.llm_model?.name}</span>
                                                                <span className="text-[8px] opacity-40 uppercase font-black">{req.llm_model?.provider?.name}</span>
                                                            </div>
                                                        </td>
                                                        <td className="py-4 px-4 text-center tabular-nums">
                                                            <span className="text-green-600 font-bold">{req.input_token_count}</span>
                                                            <span className="mx-1 text-gray-300">/</span>
                                                            <span className="text-blue-600 font-bold">{req.output_token_count}</span>
                                                        </td>
                                                        <td className="py-4 px-4 text-right opacity-60 font-mono text-[10px]">
                                                            {new Date(req.created_at).toLocaleTimeString()}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* PRIVILEGES */}
                        <TabsContent value="privileges">
                            <Card className='py-5'>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <ShieldCheck className="h-5 w-5 text-indigo-600" />
                                        Advanced Security & Roles
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleUpdateRoles} className="space-y-6">
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 rounded-2xl border-2 border-dashed border-indigo-100 bg-indigo-50/20 dark:border-indigo-900/20 dark:bg-gray-900/50">
                                            {availableRoles.map((role) => (
                                                <Label
                                                    key={role.id}
                                                    htmlFor={`role-${role.id}`}
                                                    className={`flex items-start gap-4 p-4 rounded-xl border-2 transition-all cursor-pointer ${data.roles.includes(role.name)
                                                        ? 'border-indigo-500 bg-white dark:bg-gray-800 shadow-lg shadow-indigo-100 dark:shadow-none'
                                                        : 'border-transparent opacity-60 hover:opacity-100'
                                                        }`}
                                                >
                                                    <Checkbox
                                                        id={`role-${role.id}`}
                                                        checked={data.roles.includes(role.name)}
                                                        onCheckedChange={() => toggleRole(role.name)}
                                                        className="mt-1"
                                                    />
                                                    <div className="space-y-1">
                                                        <span className="font-black uppercase tracking-tight text-gray-900 dark:text-white leading-none">
                                                            {role.name}
                                                        </span>
                                                        <div className="text-[10px] text-gray-400 uppercase font-bold tracking-widest leading-tight">
                                                            System Role Profile
                                                        </div>
                                                    </div>
                                                </Label>
                                            ))}
                                        </div>
                                        <div className="flex justify-end pt-4">
                                            <Button type="submit" disabled={rolesProcessing} className="px-8 shadow-xl shadow-indigo-200 dark:shadow-none bg-indigo-600 hover:bg-indigo-700">
                                                Update Security Access
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* AI INSIGHTS */}
                        <TabsContent value="insights">
                            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                                <div className="lg:col-span-1 space-y-4">
                                    <div className="p-6 rounded-3xl bg-indigo-600 text-white shadow-2xl shadow-indigo-200 dark:shadow-none flex flex-col items-center text-center">
                                        <Zap className="h-12 w-12 mb-4 fill-yellow-300 text-yellow-300" />
                                        <h3 className="font-black uppercase tracking-tight text-xl mb-2 italic">Player Behavior Analysis</h3>
                                        <p className="text-indigo-100 text-sm mb-6 font-medium leading-relaxed">Let the AI analyze sessions and behavior to generate a personality profile for this user.</p>
                                        <Button onClick={handleSummarize} disabled={isSummarizing} variant="secondary" className="w-full rounded-2xl font-black uppercase tracking-widest h-12 shadow-lg shadow-indigo-900/20 active:scale-95 transition-transform">
                                            {isSummarizing ? 'Analyzing...' : 'Run Core Analysis'}
                                        </Button>
                                    </div>

                                    <div className="p-6 rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-800 text-center">
                                        <div className="text-[10px] uppercase font-black tracking-widest text-gray-400 mb-2">Analysis Scope</div>
                                        <div className="text-xs font-bold text-gray-600 dark:text-gray-400 space-y-1">
                                            <p>Last 5 Adventures</p>
                                            <p>Last 20 LLM Requests</p>
                                            <p>Financial History</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="lg:col-span-3 space-y-6">
                                    {aiError && (
                                        <div className="p-4 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm font-bold flex items-center gap-3">
                                            <ShieldAlert className="h-5 w-5" />
                                            {aiError}
                                        </div>
                                    )}

                                    <Card className="rounded-[40px] shadow-2xl shadow-indigo-100 dark:shadow-none border-none relative overflow-hidden h-full min-h-[500px]">
                                        <div className="absolute inset-0 bg-gradient-to-tr from-indigo-50/50 via-transparent to-purple-50/30 dark:from-indigo-950/20 pointer-events-none" />
                                        {aiSummary ? (
                                            <CardContent className="p-10 relative">
                                                <div className="prose prose-indigo dark:prose-invert max-w-none prose-headings:font-black prose-p:font-medium prose-p:leading-relaxed prose-strong:text-indigo-600 dark:prose-strong:text-indigo-400">
                                                    <ReactMarkdown remarkPlugins={[remarkGfm]}>
                                                        {aiSummary}
                                                    </ReactMarkdown>
                                                </div>
                                            </CardContent>
                                        ) : (
                                            <CardContent className="h-full flex flex-col items-center justify-center p-20 text-center space-y-4 opacity-40 grayscale group hover:grayscale-0 transition-all duration-700">
                                                <div className="relative">
                                                    <Cpu className="h-24 w-24 text-indigo-600 absolute blur-3xl opacity-30" />
                                                    <Cpu className="h-24 w-24 text-gray-300 dark:text-gray-700 relative" />
                                                </div>
                                                <div className="space-y-2">
                                                    <h4 className="font-black text-gray-400 uppercase tracking-widest text-xl">Brain Empty</h4>
                                                    <p className="text-sm font-mono text-gray-400">Click the button on the left to initialize consciousness transfer and analyze behavior.</p>
                                                </div>
                                            </CardContent>
                                        )}
                                    </Card>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ShieldAlert({ className }: { className?: string }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
            <path d="M12 8v4" />
            <path d="M12 16h.01" />
        </svg>
    )
}
