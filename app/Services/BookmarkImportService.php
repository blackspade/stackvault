<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Parses NETSCAPE-Bookmark-file-1 HTML format exported by Chrome, Edge, Firefox, Safari.
 * Supports one level of folders only.
 */
class BookmarkImportService
{
    /**
     * Parse NETSCAPE bookmark HTML and return structured data.
     *
     * @return array{
     *   folders: array<array{name: string, bookmarks: array}>,
     *   root: array
     * }
     */
    public static function parse(string $html): array
    {
        $result = ['folders' => [], 'root' => []];

        // 1. Extract folder blocks: <H3>...</H3> followed by <DL><p>...</DL>
        preg_match_all(
            '/<DT>\s*<H3[^>]*>(.*?)<\/H3>\s*<DL[^>]*><p>(.*?)<\/DL[^>]*>/is',
            $html,
            $folderMatches,
            PREG_SET_ORDER
        );

        foreach ($folderMatches as $fm) {
            $folderName = html_entity_decode(strip_tags($fm[1]), ENT_QUOTES, 'UTF-8');
            $folderName = trim($folderName);

            if ($folderName === '') {
                continue;
            }

            $result['folders'][] = [
                'name'      => $folderName,
                'bookmarks' => self::extractBookmarks($fm[2]),
            ];
        }

        // 2. Strip all folder blocks from the HTML, then extract remaining root bookmarks
        $stripped = preg_replace(
            '/<DT>\s*<H3[^>]*>.*?<\/H3>\s*<DL[^>]*><p>.*?<\/DL[^>]*>/is',
            '',
            $html
        );

        $result['root'] = self::extractBookmarks($stripped ?? $html);

        return $result;
    }

    /**
     * Extract individual bookmarks from an HTML fragment.
     *
     * @return array<array{title: string, url: string, favicon: string|null, add_date: int|null}>
     */
    private static function extractBookmarks(string $html): array
    {
        $bookmarks = [];

        preg_match_all('/<A\s([^>]+)>(.*?)<\/A>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $attrs = $m[1];
            $title = html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8');
            $title = trim($title);

            // Require HREF
            if (!preg_match('/\bHREF="([^"]+)"/i', $attrs, $href)) {
                continue;
            }

            $url = $href[1];

            // Skip non-navigable URLs
            if (preg_match('/^(javascript:|data:text\/html)/i', $url)) {
                continue;
            }

            // ADD_DATE (unix timestamp)
            $addDate = null;
            if (preg_match('/\bADD_DATE="(\d+)"/i', $attrs, $ad)) {
                $addDate = (int) $ad[1];
            }

            // ICON (base64 data URI favicon — may be very long)
            $favicon = null;
            if (preg_match('/\bICON="(data:[^"]+)"/i', $attrs, $ic)) {
                $favicon = $ic[1];
            }

            $bookmarks[] = [
                'title'    => $title !== '' ? $title : $url,
                'url'      => $url,
                'favicon'  => $favicon,
                'add_date' => $addDate,
            ];
        }

        return $bookmarks;
    }
}
