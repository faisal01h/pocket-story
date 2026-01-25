import AuthenticatedLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { Edit, Trash2, PlusCircle, ShieldAlert } from 'lucide-react';

export default function Index({ roles }: { roles: any[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Roles', href: '/admin/roles' },
    ];

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this role?')) {
            router.delete(`/admin/roles/${id}`);
        }
    };

    return (
        <AuthenticatedLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Roles" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 flex justify-end">
                        <Link href="/admin/roles/create">
                            <Button>
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Create Role
                            </Button>
                        </Link>
                    </div>

                    <Card className='py-5'>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ShieldAlert className="h-5 w-5 text-indigo-600" />
                                User Roles
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" className="px-6 py-3">Role Name</th>
                                            <th scope="col" className="px-6 py-3">Permissions</th>
                                            <th scope="col" className="px-6 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {roles.map((role: any) => (
                                            <tr key={role.id} className="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td className="px-6 py-4 font-bold text-gray-900 dark:text-white">{role.name}</td>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        {role.permissions.map((p: any) => (
                                                            <span key={p.id} className="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] rounded-full uppercase font-bold tracking-tighter">
                                                                {p.name}
                                                            </span>
                                                        ))}
                                                        {role.permissions.length === 0 && <span className="text-gray-400 italic text-xs">No permissions</span>}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 flex justify-end gap-2">
                                                    <Link href={`/admin/roles/${role.id}/edit`}>
                                                        <Button variant="ghost" size="icon">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    {role.name !== 'Super Admin' && (
                                                        <Button variant="ghost" size="icon" className="text-red-500" onClick={() => handleDelete(role.id)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
