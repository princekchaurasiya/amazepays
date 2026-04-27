import React from 'react';

export default function Skeleton({ className }: { className?: string }) {
    return <div className={['animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800', className].filter(Boolean).join(' ')} />;
}

