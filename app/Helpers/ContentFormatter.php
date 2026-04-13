<?php

namespace App\Helpers;

/**
 * Normalize provider text/HTML into safe display fragments (descriptions, TnC, how-to-redeem).
 */
final class ContentFormatter
{
    public static function extractTnc(mixed $data): string
    {
        if (is_string($data)) {
            return self::formatContent($data);
        }
        if (is_array($data) && ! empty($data['content'])) {
            return self::formatContent($data['content']);
        }

        return 'No terms & conditions available.';
    }

    public static function extractHowToRedeem(mixed $data): string
    {
        if (is_string($data)) {
            return self::formatContent($data);
        }
        if (is_array($data) && ! empty($data['content'])) {
            return self::formatContent($data['content']);
        }

        return '<ul>
            <li><div style="margin-bottom: 8px; font-size: 14px;">How to redeem instructions are not available.</div></li>
        </ul>';
    }

    public static function extractDescription(mixed $data): string
    {
        if (is_string($data)) {
            return self::formatContent($data);
        }
        if (is_array($data) && ! empty($data['content'])) {
            return self::formatContent($data['content']);
        }

        return '<p>No description available.</p>';
    }

    public static function formatContent(mixed $data): string
    {
        if (is_string($data)) {
            return self::cleanHtmlContent($data);
        }
        if (is_array($data) && isset($data['content'])) {
            return self::cleanHtmlContent($data['content']);
        }

        return 'No terms & conditions available.';
    }

    private static function cleanHtmlContent(string $content): string
    {
        if (str_contains($content, '•')) {
            $lines = preg_split('/•\s*/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $formattedLines = array_map(fn ($line) => '<li>'.trim($line).'</li>', $lines);

            return '<ul>'.implode('', $formattedLines).'</ul>';
        }

        $content = trim($content);
        if (! preg_match('/<[^>]+>/', $content)) {
            return '<p>'.htmlspecialchars($content).'</p>';
        }

        return $content;
    }
}
