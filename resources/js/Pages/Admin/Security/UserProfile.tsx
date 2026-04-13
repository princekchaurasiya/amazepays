import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft, User } from 'lucide-react';

type UserT = {
    id: number;
    name: string;
    email: string;
    mobile: string | null;
};

type SecEvent = {
    id: number;
    event_type: string;
    created_at: string | null;
    ip_address: string | null;
};

type Profile = {
    recent_logins: SecEvent[];
    unique_ips: number;
    vpn_detections: number;
    fraud_flags: number;
};

type Props = {
    user: UserT;
    profile: Profile;
};

export default function UserProfile({ user, profile }: Props) {
    return (
        <AdminLayout>
            <Head title={`Security: ${user.name}`} />
            <div className="space-y-6 max-w-3xl">
                <Breadcrumbs items={[{ label: 'Security', href: '/panel/security' }, { label: 'User profile' }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/security/events" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <User className="text-indigo-600" size={24} />
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{user.name}</h1>
                        <p className="text-sm text-gray-500">{user.email}</p>
                    </div>
                    <Link href={`/panel/users/${user.id}`} className="ml-auto text-sm text-indigo-600">Admin user page</Link>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                        <p className="text-xs text-gray-500">Unique IPs</p>
                        <p className="text-2xl font-bold">{profile.unique_ips}</p>
                    </div>
                    <div className="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                        <p className="text-xs text-gray-500">VPN flags</p>
                        <p className="text-2xl font-bold">{profile.vpn_detections}</p>
                    </div>
                    <div className="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                        <p className="text-xs text-gray-500">Fraud flags</p>
                        <p className="text-2xl font-bold">{profile.fraud_flags}</p>
                    </div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 className="font-semibold mb-4">Recent logins</h2>
                    {profile.recent_logins.length === 0 ? (
                        <p className="text-sm text-gray-500">No login events.</p>
                    ) : (
                        <ul className="space-y-2 text-sm">
                            {profile.recent_logins.map(ev => (
                                <li key={ev.id} className="flex justify-between border-b dark:border-gray-700 pb-2">
                                    <span>{ev.created_at ? new Date(ev.created_at).toLocaleString() : '—'}</span>
                                    <code className="text-xs">{ev.ip_address}</code>
                                    <Link href={`/panel/security/events/${ev.id}`} className="text-indigo-600 text-xs">Event</Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
