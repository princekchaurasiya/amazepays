import React, { useState, useRef, useEffect } from 'react';
import { HelpCircle } from 'lucide-react';

type Props = {
    text: string;
    className?: string;
};

export default function HelpTooltip({ text, className = '' }: Props) {
    const [visible, setVisible] = useState(false);
    const tooltipRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!visible) return;
        const handleClick = (e: MouseEvent) => {
            if (tooltipRef.current && !tooltipRef.current.contains(e.target as Node)) {
                setVisible(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, [visible]);

    return (
        <span className={`relative inline-flex items-center ${className}`} ref={tooltipRef}>
            <button
                type="button"
                onClick={() => setVisible(!visible)}
                onMouseEnter={() => setVisible(true)}
                onMouseLeave={() => setVisible(false)}
                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                aria-label="Help"
            >
                <HelpCircle size={14} />
            </button>

            {visible && (
                <div className="absolute z-50 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 px-3 py-2 text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg leading-relaxed">
                    {text}
                    <div className="absolute top-full left-1/2 -translate-x-1/2 -mt-px">
                        <div className="w-2.5 h-2.5 bg-white dark:bg-gray-700 border-r border-b border-gray-200 dark:border-gray-600 transform rotate-45" />
                    </div>
                </div>
            )}
        </span>
    );
}
