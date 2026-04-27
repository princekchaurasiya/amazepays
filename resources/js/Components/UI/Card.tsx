import React from 'react';

export function Card({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return (
        <section
            className={[
                'rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900',
                className,
            ]
                .filter(Boolean)
                .join(' ')}
            {...props}
        />
    );
}

export function CardHeader({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return <div className={['p-6 pb-0', className].filter(Boolean).join(' ')} {...props} />;
}

export function CardBody({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return <div className={['p-6', className].filter(Boolean).join(' ')} {...props} />;
}

