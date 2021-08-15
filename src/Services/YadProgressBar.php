<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class YadProgressBar
{
    /**
     * @var resource
     */
    protected $process;

    /**
     * @var resource[]
     */
    protected array $pipes;

    /**
     * @param string $title
     * @param string|null $text
     * @param string|null $progressText
     */
    public function __construct(string $title, string $text = null, string $progressText = null)
    {
        // By using exec, all the proc_* functions operate on the real pid.
        $command = sprintf(
            'exec yad --progress --percentage=0 --title=%s%s%s --no-buttons --center --skip-taskbar --no-escape --fixed',
            escapeshellarg($title),
            empty(trim($text)) ? '' : sprintf(' --text=%s', $text),
            empty(trim($progressText)) ? '' : sprintf(' --progress-text=%s', $text)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $this->process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($this->process)) {
            throw new RuntimeException('Could not create Yad progress bar.');
        }

        stream_set_blocking($pipes[0], false);
        $this->pipes = $pipes;
    }

    public function progress(int $progress, string $progressText = null): YadProgressBar
    {
        if ($progress < 0 || $progress > 100) {
            throw new InvalidArgumentException('Progress should be within range 0-100%');
        }

        fwrite($this->pipes[0], "$progress\n#$progressText\n");

        return $this;
    }

    public function __destruct()
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        proc_terminate($this->process);
    }
}
