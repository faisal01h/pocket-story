import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import games from '@/routes/games';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Activity, ShieldCheck, Compass, Joystick, Ticket, ShieldAlert, Key, Users } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Discover',
        href: '/discover',
        icon: Compass,
    },
    {
        title: 'Games',
        href: games.index(),
        icon: Joystick,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'LLM Requests',
        href: '/admin/llm-requests',
        icon: Activity,
    },
    {
        title: 'LLM Limits',
        href: '/admin/llm-limits',
        icon: ShieldCheck,
    },
    {
        title: 'Redeem Code',
        href: '/redeem',
        icon: Ticket,
    },
    {
        title: 'Redeem Codes',
        href: '/admin/redeem-codes',
        icon: Ticket,
    },
    {
        title: 'Roles',
        href: '/admin/roles',
        icon: ShieldAlert,
    },
    {
        title: 'Permissions',
        href: '/admin/permissions',
        icon: Key,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
