<?php

namespace App\Commands;

use App\Services\Config;
use App\Services\Yad;
use DateTime;
use Devorto\DependencyInjection\DependencyInjection;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * All commands must extend this command, so we can run some extra checks and stuff.
 */
abstract class BaseCommand extends Command
{
    /**
     * @var Yad
     */
    protected Yad $yad;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @param Yad $yad
     * @param Config $config
     */
    public function __construct(Yad $yad, Config $config)
    {
        parent::__construct();

        $this->yad = $yad;
        $this->config = $config;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->checkAndInstallDependency('yad');

        if (!$this->rootCheck()) {
            $this->yad->error('This command cannot be run as root!');
        }

        /** @var Config $config */
        $config = DependencyInjection::instantiate(Config::class);
        $this->config = $config;

        $now = new DateTime();
        $update = new DateTime($this->config->get('recheck-for-updates'));
        if ($now > $update) {
            $this->getApplication()->find('update')->execute($input, $output);
        }
    }

    protected function rootCheck(): bool
    {
        return !empty($_SERVER['USER']) && $_SERVER['USER'] !== 'root';
    }

    /**
     * @param string $application
     * @param string|null $package
     */
    public function checkAndInstallDependency(string $application, string $package = null): void
    {
        $app = shell_exec('command -v ' . escapeshellarg($application));
        if (!empty($app)) {
            return;
        }

        exec(sprintf("pkexec bash -c 'apt update && apt install -y %s'", $package ?? $application));
    }
}
