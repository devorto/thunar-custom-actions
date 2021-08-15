<?php

namespace App\Commands\Cue;

use App\Commands\BaseCommand;
use App\Services\Thunar;
use App\Services\YadProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Split
 *
 * @package App\Commands\Cue
 */
class Split extends BaseCommand implements Thunar
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('cue:split')
            ->setDescription('Split file linked to cue file in separate files.')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to cue file');
    }

    /**
     * Execute the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkAndInstallDependency('shnsplit', 'shntool');
        $file = $input->getArgument('file');

        if (!file_exists($file)) {
            $this->yad->error(sprintf('Cannot find file "%s".', $file));

            return 1;
        }

        $file = realpath($file);
        $contents = file($file);
        $count = 0;
        $linkedFile = null;
        foreach ($contents as $content) {
            if (1 === preg_match('/^FILE "(.*?)".*$/', $content, $matches)) {
                $linkedFile = $matches[1];
            } elseif (1 === preg_match('/^.*?TRACK [0-9]+.*$/', $content)) {
                $count++;
            }
        }

        if (!empty($linkedFile)) {
            $linkedFile = dirname($file) . DIRECTORY_SEPARATOR . $linkedFile;
            if (file_exists($linkedFile)) {
                $linkedFile = realpath($linkedFile);
            } else {
                $linkedFile = null;
            }
        }

        if (empty($linkedFile)) {
            $linkedFile = $this->yad->fileSelection(dirname($file), 'Select CUE linked file.');
            if (empty($linkedFile)) {
                $this->yad->error('Cannot determine which file to use in conjunction with cue file.');
            }
        }

        if (strtolower(substr($linkedFile, -5)) !== '.flac') {
            $this->yad->error('Only splitting of flac based cue files is currently supported.');

            return 1;
        }

        $outputDirectory = dirname($file) . DIRECTORY_SEPARATOR . basename($file, '.cue');
        if (file_exists($outputDirectory)) {
            $this->yad->error(sprintf('Directory "%s" already exists.', basename($outputDirectory)));
        }

        mkdir($outputDirectory);
        chdir(dirname($file));

        $command = sprintf(
            "shnsplit -o flac -t '%%n - %%t' -d %s -O never -w -f %s %s",
            escapeshellarg(basename($file, '.cue')),
            escapeshellarg(basename($file)),
            escapeshellarg(basename($linkedFile))
        );

        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            $this->yad->error('Could not execute shnsplit.');

            return 1;
        }
        fclose($pipes[0]);
        fclose($pipes[1]);

        $maxSingleItemPercentage = 100 / $count;
        $bar = new YadProgressBar('Splitting cue file.');

        stream_set_blocking($pipes[2], false);
        $content = '';
        do {
            unset($status, $name, $percentage, $item);

            $status = proc_get_status($process);
            $content .= str_replace(chr(8), '', fread($pipes[2], 1000));

            preg_match_all('/^(.*)-->(.*):(.*)$/m', $content, $matches);
            if (!empty($matches[3]) && count($matches[3]) > 0) {
                $name = trim(array_pop($matches[2]));
                if (count($matches[3]) > 1) {
                    $percentage = $maxSingleItemPercentage * (count($matches[3]) - 1);
                } else {
                    $percentage = 0;
                }

                $lastProgress = explode('%', trim(str_replace([' ', 'OK'], '', array_pop($matches[3])), '%'));
                $lastProgress = (int)array_pop($lastProgress);
                if ($lastProgress > 0) {
                    $percentage += ($maxSingleItemPercentage / 100) * $lastProgress;
                }
                $bar->progress(ceil($percentage), $name);
            }
        } while ($status['running']);
        fclose($pipes[2]);
        proc_close($process);
        unset($bar);

        return 0;
    }

    /**
     * Get display name of command.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Split cue file';
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
        return $this->config->get('icon_path') . '/cue-split.png';
    }

    /**
     * Extensions on which the command should be run.
     * For directories leave empty.
     * For all files also leave empty.
     *
     * @return string[]
     */
    public function extensions(): array
    {
        return ['cue'];
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
