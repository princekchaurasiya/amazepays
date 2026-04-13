import React, { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import FlashToast from '@/Components/Admin/FlashToast';
import { paths } from '@/lib/paths';

export default function AuthLayout({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col bg-gradient-to-b from-slate-100 to-white">
            <FlashToast />
            <header className="border-b border-gray-200 bg-white/90 px-4 py-4">
                <Link href={paths.home} className="inline-flex items-center gap-2">
                    <img src="/images/logo.png" alt="AmazePays" className="h-9 w-auto" />
                </Link>
            </header>
            <main className="flex flex-1 items-center justify-center px-4 py-10">{children}</main>
        </div>
    );
}
