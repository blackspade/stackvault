<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Generates NETSCAPE-Bookmark-file-1 HTML for export.
 * Compatible with Chrome, Edge, Firefox, and Safari.
 */
class BookmarkExportService
{
    /**
     * Build the NETSCAPE HTML export string.
     *
     * @param array $set       bookmark_set row
     * @param array $folders   bookmark_folder rows (ordered)
     * @param array $bookmarks all bookmark rows for the set
     */
    public static function generate(array $set, array $folders, array $bookmarks): string
    {
        $now = time();

        // Group bookmarks by folder_id; null/0 = root
        $byFolder = [];
        $root     = [];

        foreach ($bookmarks as $bm) {
            if (!empty($bm['folder_id'])) {
                $byFolder[(int) $bm['folder_id']][] = $bm;
            } else {
                $root[] = $bm;
            }
        }

        $lines   = [];
        $lines[] = '<!DOCTYPE NETSCAPE-Bookmark-file-1>';
        $lines[] = '<!-- This is an automatically generated file.';
        $lines[] = '     It will be read and overwritten.';
        $lines[] = '     DO NOT EDIT! -->';
        $lines[] = '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">';
        $lines[] = '<TITLE>Bookmarks</TITLE>';
        $lines[] = '<H1>Bookmarks</H1>';
        $lines[] = '<DL><p>';

        // Folders with their bookmarks
        foreach ($folders as $folder) {
            $fid     = (int) $folder['id'];
            $name    = htmlspecialchars($folder['name'], ENT_QUOTES, 'UTF-8');
            $lines[] = "    <DT><H3 ADD_DATE=\"{$now}\" LAST_MODIFIED=\"{$now}\">{$name}</H3>";
            $lines[] = '    <DL><p>';

            foreach ($byFolder[$fid] ?? [] as $bm) {
                $lines[] = self::bookmarkLine($bm, '        ');
            }

            $lines[] = '    </DL><p>';
        }

        // Root (unfiled) bookmarks
        foreach ($root as $bm) {
            $lines[] = self::bookmarkLine($bm, '    ');
        }

        $lines[] = '</DL><p>';

        return implode("\n", $lines) . "\n";
    }

    private static function bookmarkLine(array $bm, string $indent): string
    {
        $title    = htmlspecialchars($bm['title'], ENT_QUOTES, 'UTF-8');
        $url      = htmlspecialchars($bm['url'],   ENT_QUOTES, 'UTF-8');
        $addDate  = $bm['add_date'] ?? time();
        $iconAttr = '';

        if (!empty($bm['favicon'])) {
            $iconAttr = ' ICON="' . htmlspecialchars($bm['favicon'], ENT_QUOTES, 'UTF-8') . '"';
        }

        return "{$indent}<DT><A HREF=\"{$url}\" ADD_DATE=\"{$addDate}\"{$iconAttr}>{$title}</A>";
    }
}
