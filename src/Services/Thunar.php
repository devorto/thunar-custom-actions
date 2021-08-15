<?php

namespace App\Services;

/**
 * Interface Thunar
 *
 * @package App\Services
 */
interface Thunar
{
    /**
     * Get display name of command.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get command to run for Thunar.
     *
     * @return string
     */
    public function command(): string;

    /**
     * Path to an image file or system icon identifier.
     *
     * @return string
     */
    public function icon(): string;

    /**
     * Extensions on which the command should be run.
     * For directories leave empty.
     * To support all file extensions also leave empty.
     *
     * @return string[]
     */
    public function extensions(): array;

    /**
     * Can command be run on directories?
     *
     * @return bool
     */
    public function runOnDirectories(): bool;

    /**
     * Can command be run on files?
     *
     * @return bool
     */
    public function runOnFiles(): bool;
}
