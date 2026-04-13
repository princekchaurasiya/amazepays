<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Invoice PDF / HTML snippets from site settings.
 */
final class InvoiceHelper
{
    public static function getFormattedInvoiceTermsAndConditions(): string
    {
        $content = DB::table('settings')->where('key', 'company.invoice_tnc')->value('value');
        if (empty($content)) {
            $content = DB::table('settings')->where('key', 'site.invoice_t&c')->value('value');
        }
        if (empty($content)) {
            return 'No terms & conditions available.';
        }
        if (stripos($content, '<ul>') !== false || stripos($content, '<li>') !== false) {
            return $content;
        }
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_filter($lines, fn ($line) => ! empty(trim($line)));
        $formattedLines = array_map(fn ($line) => '<li>'.trim($line).'</li>', $lines);

        return '<ul class="no-bullets">'.implode('', $formattedLines).'</ul>';
    }
}
