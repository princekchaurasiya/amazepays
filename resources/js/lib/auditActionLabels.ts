/** Maps audit `action` keys to human-readable labels for non-technical admins. */

export type AuditActionStyle = { label: string; color: string };

export const ACTION_LABELS: Record<string, AuditActionStyle> = {
    'mobile.manual_blocked': { label: 'Mobile blocked', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
    'mobile.unblocked': { label: 'Mobile unblocked', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' },
    'ip.manual_blocked': { label: 'IP blocked', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
    'ip.unblocked': { label: 'IP unblocked', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' },
    'product.updated': { label: 'Product updated', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' },
    'product.created': { label: 'Product created', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' },
    'product.deleted': { label: 'Product deleted', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
    'offer.created': { label: 'Offer created', color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' },
    'offer.updated': { label: 'Offer updated', color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' },
    'offer.toggled': { label: 'Offer toggled', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200' },
    'offer.deleted': { label: 'Offer deleted', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
    'order.status_changed': { label: 'Order status changed', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' },
    'settings.updated': { label: 'Settings updated', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' },
    'user.role_synced': { label: 'User role changed', color: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-200' },
    'demo.audit_sample': { label: 'Demo entry', color: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
    'demo.security_view': { label: 'Dashboard viewed', color: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
    'ticket.created': { label: 'Ticket created', color: 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200' },
    'ticket.updated': { label: 'Ticket updated', color: 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200' },
    'ticket.replied': { label: 'Ticket reply sent', color: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-200' },
    'ticket.resolved': { label: 'Ticket resolved', color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' },
};

export function formatAuditAction(action: string): AuditActionStyle {
    const entry = ACTION_LABELS[action];
    if (entry) {
        return entry;
    }
    const human = action
        .replace(/[._]/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
    return {
        label: human,
        color: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
    };
}
