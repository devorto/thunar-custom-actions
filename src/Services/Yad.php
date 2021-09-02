<?php

namespace App\Services;

/**
 * Yad GTK+ GUI
 */
class Yad
{
    /**
     * @param string $text
     * @param string $title
     */
    public function error(string $text, string $title = 'Error'): void
    {
        $command = sprintf(
            'yad --title=%s --text=%s --button=gtk-ok:0 --center',
            escapeshellarg($title),
            escapeshellarg($text)
        );
        exec($command);
    }

    /**
     * @param string $startFolder
     * @param string $title
     *
     * @return string|null
     */
    public function fileSelection(string $startFolder, string $title): ?string
    {
        $command = sprintf(
            'yad --file --filename=%s --title=%s',
            escapeshellarg(rtrim($startFolder, '/') . '/'),
            $title
        );
        exec($command, $output);

        return empty($output[0]) ? null : trim($output[0]);
    }

    /**
     * @param string $text
     * @param string $title
     *
     * @return bool
     */
    public function question(string $text, string $title = 'Question'): bool
    {
        $command = sprintf(
            'yad --title=%s --text=%s --button=gtk-yes:0 --button=gtk-no:1',
            escapeshellarg($title),
            escapeshellarg($text)
        );
        $null = null;
        exec($command, $null, $result);

        return $result == 0;
    }

    /**
     * @param string $title
     *
     * @return string|null
     */
    public function askPassword(string $title = 'Password?'): ?string
    {
        $command = sprintf(
            'yad --entry --title="%s" --entry-text=Password --hide-text',
            $title
        );

        return trim(shell_exec($command)) ?: null;
    }
}
