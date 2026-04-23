import React, { PropsWithChildren } from 'react';
import FlashToast from '@/Components/Admin/FlashToast';
import CategoryNav from '@/Components/Storefront/CategoryNav';
import StorefrontHeader from '@/Components/Storefront/StorefrontHeader';

export default function CheckoutLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-gray-50">
            <FlashToast />
            <StorefrontHeader />
            <CategoryNav />
            <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
        </div>
    );
}
