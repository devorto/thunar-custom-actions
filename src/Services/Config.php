<?php

namespace App\Services;

use DateTime;
use RuntimeException;

/**
 * Persistent configuration between runs.
 */
class Config
{
    /**
     * @var array
     */
    protected array $config = [];

    /**
     * Initialize config file.
     */
    public function __construct()
    {
        $home = filter_input(INPUT_SERVER, 'HOME');
        if (empty($home)) {
            throw new RuntimeException('Environment $HOME not set.');
        }

        // Default config
        $this->config = [
            'config_path' => $home . '/.config/thunar-custom-actions',
            'config_file' => $home . '/.config/thunar-custom-actions/config.json',
            'phar_path' => $home . '/.local/bin/tca',
            'icon_path' => $home . '/.config/thunar-custom-actions/icons',
            'tca_file' => $home . '/.config/Thunar/uca.xml',
            'recheck-for-updates' => (new DateTime())->format('Y-m-d H:i:s')
        ];

        // Create folders.
        if (!file_exists($this->config['config_path'])) {
            mkdir($this->config['config_path'], 0777, true);
        }
        if (!file_exists($this->config['icon_path'])) {
            mkdir($this->config['icon_path']);
        }

        // Parse config json file or create if not exists.
        if (file_exists($this->config['config_file'])) {
            $json = json_decode(file_get_contents($this->config['config_file']), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                foreach ($json as $key => $value) {
                    $this->config[$key] = $value;
                }
            }
        } else {
            file_put_contents($this->config['config_file'], json_encode($this->config, JSON_PRETTY_PRINT));
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function get(string $key): ?string
    {
        if (!$this->has($key)) {
            return null;
        }

        return $this->config[$key];
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function set(string $key, string $value): Config
    {
        $this->config[$key] = $value;
        file_put_contents($this->config['config_file'], json_encode($this->config, JSON_PRETTY_PRINT));

        return $this;
    }
}
