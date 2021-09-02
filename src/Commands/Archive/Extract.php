<?php

namespace App\Commands\Archive;

use App\Commands\BaseCommand;
use App\Services\Thunar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extract different archive types.
 */
class Extract extends BaseCommand implements Thunar
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('archive:extract')
            ->setDescription('Extract archive to directory with same name.')
            ->addArgument('file', InputArgument::REQUIRED, 'Archive file to extract.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        if (!file_exists($file)) {
            $this->yad->error(sprintf('Could not find file "%s".', $file));
        }

        $info = pathinfo($file);
        if (!is_writable($info['dirname'])) {
            $this->yad->error(sprintf('Cannot extract archive, directory "%s" not writable.', $info['dirname']));
        }

        switch ($info['extension']) {
            case 'zip':
                return $this->extractZip($file, $info);
            default:
                $this->yad->error('Unsupported extension: ' . $info['extension']);

                return 1;
        }
    }

    /**
     * @param string $file
     * @param array $info
     *
     * @return int
     */
    protected function extractZip(string $file, array $info): int
    {
        $this->checkAndInstallDependency('unzip');

        $extractDirectory = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'];
        if (file_exists($extractDirectory)) {
            $this->yad->error(sprintf('Cannot extract archive directory "%s" already exists.', $extractDirectory));

            return 1;
        }

        mkdir($extractDirectory);
        chdir($info['dirname']);

        exec(sprintf('zipinfo -v %s', escapeshellarg($file)), $results, $code);
        if ($code !== 0) {
            $this->yad->error('Could not read zip file.');

            return 1;
        }

        $encryption = array_map('trim', explode(':', current(preg_grep('/file security status/', $results))));
        if ($encryption[1] === 'encrypted') {
            $password = $this->yad->askPassword();
            if (empty($password)) {
                $this->yad->error('No password provided.');

                return 1;
            }
        }

        return 0;
    }

    /**
     * Get display name of command.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Extract archive';
    }

    /**
     * Get command to run for Thunar.
     *
     * @return string
     */
    public function command(): string
    {
        return $this->config->get('phar_path') . ' ' . $this->getName() . ' %f';
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
        return ['zip'];
    }

    /**
     * Can command be run on directories?
     *
     * @return bool
     */
    public function runOnDirectories(): bool
    {
        return false;
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