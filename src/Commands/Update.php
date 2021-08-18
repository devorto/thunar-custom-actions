<?php

namespace App\Commands;

use DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Update this application.
 */
class Update extends BaseCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setAliases(['self-update', 'selfupdate'])
            ->setDescription('Update this application.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $curl = curl_init('https://api.github.com/repos/devorto/thunar-custom-actions/releases/latest');
        curl_setopt_array(
            $curl,
            [
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERAGENT => '@Devorto',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.github.v3+json'
                ],
                CURLOPT_RETURNTRANSFER => true
            ]
        );
        $json = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        unset($curl);

        if (empty($json) || $status !== 200) {
            /**
             * Something went wrong, maybe no internet connection or GitHub down?
             * Anyhow, checking for updates is not so important that program has to die imho,
             * so just try again in the future.
             */
            $this->config->set('recheck-for-updates', (new DateTime('+1 hour'))->format('Y-m-d H:i:s'));

            return 1;
        }
        unset($status);

        $json = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return 1;
        }

        if ($json['tag_name'] === $this->getApplication()->getVersion() || $json['draft'] || empty($json['assets'])) {
            $this->config->set('recheck-for-updates', (new DateTime('+1 hour'))->format('Y-m-d H:i:s'));

            return 0;
        }

        $message = sprintf('New %srelease found do you want to update?', $json['prerelease'] ? 'pre-' : '');
        if (!$this->yad->question($message, 'Update application?')) {
            $this->config->set('recheck-for-updates', (new DateTime('+1 hour'))->format('Y-m-d H:i:s'));

            return 0;
        }

        $path = $this->config->get('phar_path');
        if (!is_writable(dirname($path))) {
            $this->yad->error(sprintf('Directory %s is not writeable.', dirname($path)));
            $this->config->set('recheck-for-updates', (new DateTime('+1 hour'))->format('Y-m-d H:i:s'));

            return 1;
        }

        foreach ($json['assets'] as $asset) {
            if ($asset['name'] === 'tca.phar' && $asset['state'] === 'uploaded') {
                file_put_contents($path, file_get_contents($asset['browser_download_url']));
                chmod($path, 0777);

                // Run installer on new file.
                exec($path . ' install');
                break;
            }
        }

        return 0;
    }
}
