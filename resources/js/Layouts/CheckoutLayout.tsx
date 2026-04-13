import React, { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import FlashToast from '@/Components/Admin/FlashToast';
import { paths } from '@/lib/paths';

export default function CheckoutLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-gray-50">
            <FlashToast />
            <header className="border-b border-gray-200 bg-white px-4 py-3">
                <div className="mx-auto flex max-w-5xl items-center justify-between">
                    <Link href={paths.home} className="inline-flex items-center gap-2">
                        <img src="/images/logo.png" alt="" className="h-8 w-auto" />
                    </Link>
                    <Link href={paths.home} className="text-sm font-medium text-gray-600 hover:text-brand-600">
                        Continue shopping
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
        </div>
    );
}
