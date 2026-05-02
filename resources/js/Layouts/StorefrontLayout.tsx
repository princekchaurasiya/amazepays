import React, { PropsWithChildren, useState } from 'react';
import FlashToast from '@/Components/Admin/FlashToast';
import AuthModal from '@/Components/Storefront/AuthModal';
import CategoryNav from '@/Components/Storefront/CategoryNav';
import StorefrontFooter from '@/Components/Storefront/StorefrontFooter';
import StorefrontHeader from '@/Components/Storefront/StorefrontHeader';
import { StorefrontAuthModalProvider } from '@/contexts/StorefrontAuthModalContext';

export default function StorefrontLayout({ children }: PropsWithChildren) {
    const [authOpen, setAuthOpen] = useState(false);

    return (
        <StorefrontAuthModalProvider openAuthModal={() => setAuthOpen(true)}>
            <div className="flex min-h-screen flex-col bg-gray-50">
                <FlashToast />
                <StorefrontHeader onOpenAuth={() => setAuthOpen(true)} />
                <CategoryNav />
                <main className="flex-1">{children}</main>
                <StorefrontFooter />
                <AuthModal open={authOpen} onClose={() => setAuthOpen(false)} />
            </div>
        </StorefrontAuthModalProvider>
    );
}
