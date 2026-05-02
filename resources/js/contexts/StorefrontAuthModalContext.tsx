import React, { createContext, useContext, type PropsWithChildren } from 'react';

type StorefrontAuthModalContextValue = {
    openAuthModal: () => void;
};

const StorefrontAuthModalContext = createContext<StorefrontAuthModalContextValue | null>(null);

export function StorefrontAuthModalProvider({
    children,
    openAuthModal,
}: PropsWithChildren<StorefrontAuthModalContextValue>) {
    return <StorefrontAuthModalContext.Provider value={{ openAuthModal }}>{children}</StorefrontAuthModalContext.Provider>;
}

export function useStorefrontAuthModal(): StorefrontAuthModalContextValue | null {
    return useContext(StorefrontAuthModalContext);
}
