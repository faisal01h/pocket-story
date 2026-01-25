import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardDescription, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { BreadcrumbItem } from '@/types';
import { Ticket, CheckCircle2 } from 'lucide-react';

export default function Redeem() {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Redeem Code', href: '/redeem' },
    ];

    const { data, setData, post, processing, errors, reset, wasSuccessful } = useForm({
        code: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/redeem', {
            onSuccess: () => reset('code'),
        });
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Redeem LLM Code" />

            <div className="py-12">
                <div className="mx-auto max-w-md px-4 sm:px-6 lg:px-8">
                    <Card className="border-indigo-100 dark:border-indigo-900/50 shadow-xl overflow-hidden">
                        <div className="h-2 bg-indigo-600 w-full" />
                        <CardHeader className="text-center pb-2">
                            <div className="mx-auto bg-indigo-100 dark:bg-indigo-900/30 p-3 rounded-full w-fit mb-4">
                                <Ticket className="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <CardTitle className="text-2xl font-bold">Redeem Token Code</CardTitle>
                            <CardDescription>
                                Enter your promotional code to increase your AI token limit.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {wasSuccessful ? (
                                <div className="text-center py-6 space-y-4">
                                    <div className="flex justify-center">
                                        <CheckCircle2 className="h-16 w-16 text-green-500 animate-bounce" />
                                    </div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Redemption Successful!</h3>
                                    <p className="text-sm text-gray-500">Your token limit has been updated. You can now continue your adventures.</p>
                                    <Button variant="outline" onClick={() => (window.location.href = '/dashboard')} className="mt-4">
                                        Back to Dashboard
                                    </Button>
                                </div>
                            ) : (
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="code" className="text-sm font-medium">Promo Code</Label>
                                        <Input
                                            id="code"
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                            placeholder="XXXX-XXXX-XXXX"
                                            className="uppercase font-mono text-center text-lg tracking-widest h-12 border-2 focus:border-indigo-500"
                                            required
                                            autoFocus
                                        />
                                        {errors.code && <p className="text-sm text-red-500 font-medium">{errors.code}</p>}
                                    </div>

                                    <Button type="submit" className="w-full h-12 text-lg bg-indigo-600 hover:bg-indigo-700 transition-all font-bold" disabled={processing}>
                                        {processing ? 'Processing...' : 'Redeem Now'}
                                    </Button>

                                    <p className="text-[10px] text-center text-gray-400 uppercase tracking-tighter">
                                        Tokens will be added to your account immediately after validation.
                                    </p>
                                </form>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
