<?php

namespace App\Commands;

use App\Services\Thunar;
use DOMDocument;
use DOMElement;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Install Icons and Thunar xml file.
 */
class Install extends BaseCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install actions in thunar.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Install icons.
        $iconPath = $this->config->get('icon_path');
        if (!file_exists($iconPath) && is_writable($this->config->get('config_path'))) {
            mkdir($iconPath);
        }

        $icons = [
            __DIR__ . '/../Icons/cue-split.png' => 'cue-split.png'
        ];

        array_walk(
            $icons,
            function (string $name, string $path) use ($iconPath): void {
                file_put_contents($iconPath . '/' . $name, file_get_contents($path));
            }
        );
        unset($iconPath, $icons);

        // Install Thunar custom actions.
        $path = $this->config->get('tca_file');
        if (!file_exists($path)) {
            throw new RuntimeException('Missing Thunar uca.xml config file.');
        }

        $actions = $this->getXmlDocument($path);
        $commands = $this->getApplication()->all();

        foreach ($commands as $command) {
            if (!($command instanceof Thunar)) {
                continue;
            }

            $actions[$command->name()] = [
                'icon' => $command->icon(),
                'name' => $command->name(),
                'command' => $command->command(),
                'description' => null,
                'patterns' => $this->getPatterns($command->extensions()),
                'startup-notify' => true,
                'directories' => $command->runOnDirectories(),
                'audio-files' => $command->runOnFiles(),
                'image-files' => $command->runOnFiles(),
                'other-files' => $command->runOnFiles(),
                'text-files' => $command->runOnFiles(),
                'video-files' => $command->runOnFiles()
            ];
        }

        $this->saveXmlDocument($path, $actions);

        return 0;
    }

    /**
     * Retrieve xml document as array.
     *
     * @param string $path
     *
     * @return array
     */
    protected function getXmlDocument(string $path): array
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->load($path);

        $actions = [];

        /** @var DOMElement $node */
        foreach ($xml->getElementsByTagName('action') as $node) {
            $name = $node->getElementsByTagName('name')->item(0)->nodeValue;

            $actions[$name] = [
                'icon' => $node->getElementsByTagName('icon')->item(0)->nodeValue,
                'name' => $name,
                'command' => $node->getElementsByTagName('command')->item(0)->nodeValue,
                'patterns' => $node->getElementsByTagName('patterns')->item(0)->nodeValue,
                'startup-notify' => $node->getElementsByTagName('startup-notify')->length > 0,
                'directories' => $node->getElementsByTagName('directories')->length > 0,
                'audio-files' => $node->getElementsByTagName('audio-files')->length > 0,
                'image-files' => $node->getElementsByTagName('image-files')->length > 0,
                'other-files' => $node->getElementsByTagName('other-files')->length > 0,
                'text-files' => $node->getElementsByTagName('text-files')->length > 0,
                'video-files' => $node->getElementsByTagName('video-files')->length > 0,
            ];
        }

        return $actions;
    }

    /**
     * Save array to xml document.
     *
     * @param string $path
     * @param array $data
     */
    protected function saveXmlDocument(string $path, array $data): void
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $actions = $xml->createElement('actions');
        $xml->appendChild($actions);

        array_walk(
            $data,
            function ($data) use ($actions, $xml): void {
                $action = $xml->createElement('action');
                $actions->appendChild($action);

                $icon = $xml->createElement('icon');
                $icon->nodeValue = $data['icon'];
                $action->appendChild($icon);

                $name = $xml->createElement('name');
                $name->nodeValue = $data['name'];
                $action->appendChild($name);

                $command = $xml->createElement('command');
                $command->nodeValue = $data['command'];
                $action->appendChild($command);

                $patterns = $xml->createElement('patterns');
                $patterns->nodeValue = $data['patterns'];
                $action->appendChild($patterns);

                if ($data['startup-notify']) {
                    $action->appendChild($xml->createElement('startup-notify'));
                }
                if ($data['directories']) {
                    $action->appendChild($xml->createElement('directories'));
                }
                if ($data['audio-files']) {
                    $action->appendChild($xml->createElement('audio-files'));
                }
                if ($data['image-files']) {
                    $action->appendChild($xml->createElement('image-files'));
                }
                if ($data['other-files']) {
                    $action->appendChild($xml->createElement('other-files'));
                }
                if ($data['text-files']) {
                    $action->appendChild($xml->createElement('text-files'));
                }
                if ($data['video-files']) {
                    $action->appendChild($xml->createElement('video-files'));
                }
            }
        );

        $xml->save($path);
    }

    /**
     * @param array $extensions
     *
     * @return string
     */
    protected function getPatterns(array $extensions): string
    {
        if (empty($extensions) || in_array('*', $extensions, true)) {
            return '*';
        }

        $extensions = array_map(
            function (string $extension): string {
                $extension = trim($extension, " \t\n\r\0\x0B.*");

                $extensions = [
                    '*.' . strtolower($extension),
                    '*.' . strtoupper($extension),
                    '*.' . ucfirst($extension)
                ];

                return implode(';', $extensions);
            },
            $extensions
        );

        return implode(';', $extensions);
    }
}
