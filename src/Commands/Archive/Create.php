<?php

namespace App\Commands\Archive;

use App\Commands\BaseCommand;
use App\Services\Thunar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create archive from files/directories.
 */
class Create extends BaseCommand implements Thunar
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('archive:create')
            ->setDescription('Create archive from files/folders.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }

    /**
     * Get display name of command.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Create archive';
    }

    /**
     * Get command to run for Thunar.
     *
     * @return string
     */
    public function command(): string
    {
        return $this->config->get('phar_path') . ' ' . $this->getName() . ' %F';
    }

    /**
     * Path to an image file or system icon identifier.
     *
     * @return string
     */
    public function icon(): string
    {
        return $this->config->get('icon_path') . '/archive.png';
    }

    /**
     * Extensions on which the command should be run.
     * For directories leave empty.
     * To support all file extensions also leave empty.
     *
     * @return string[]
     */
    public function extensions(): array
    {
        return [];
    }

    /**
     * Can command be run on directories?
     *
     * @return bool
     */
    public function runOnDirectories(): bool
    {
        return true;
    }

    /**
     * Can command be run on files?
     *
     * @return bool
     */
    public function runOnFiles(): bool
    {
        return true;
    }
}
