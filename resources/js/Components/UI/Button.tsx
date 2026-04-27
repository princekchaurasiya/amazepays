import React from 'react';

type Variant = 'primary' | 'secondary' | 'muted' | 'danger';
type Size = 'sm' | 'md';

type Props = React.ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: Variant;
    size?: Size;
    leftIcon?: React.ReactNode;
    rightIcon?: React.ReactNode;
    loading?: boolean;
};

const base =
    'inline-flex items-center justify-center gap-2 font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-product-primary/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

const sizes: Record<Size, string> = {
    sm: 'h-10 px-4 text-sm rounded-xl',
    md: 'h-11 px-5 text-sm rounded-xl',
};

const variants: Record<Variant, string> = {
    primary: 'bg-product-primary text-white hover:opacity-90 shadow-sm',
    secondary: 'bg-product-accent text-white hover:opacity-90 shadow-sm',
    muted: 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    danger: 'bg-red-500 text-white hover:bg-red-600 shadow-sm',
};

export default function Button({
    variant = 'primary',
    size = 'md',
    leftIcon,
    rightIcon,
    loading,
    children,
    className,
    ...rest
}: Props) {
    return (
        <button className={[base, sizes[size], variants[variant], className].filter(Boolean).join(' ')} {...rest}>
            {leftIcon ? <span className="shrink-0">{leftIcon}</span> : null}
            <span className="min-w-0">{loading ? 'Please wait…' : children}</span>
            {rightIcon ? <span className="shrink-0">{rightIcon}</span> : null}
        </button>
    );
}

