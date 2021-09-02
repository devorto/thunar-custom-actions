<?php

// Get releases.
$curl = curl_init(sprintf(
    '%s/repos/%s/releases',
    $_SERVER['GITHUB_API_URL'],
    $_SERVER['GITHUB_REPOSITORY']
));
curl_setopt_array(
    $curl,
    [
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github.v3+json',
            'Authorization: Token ' . $_SERVER['GITHUB_TOKEN'],
            'User-Agent: ' . $_SERVER['GITHUB_ACTOR']
        ],
        CURLOPT_RETURNTRANSFER => true
    ]
);
$json = curl_exec($curl);
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
if ($code !== 200) {
    exit(1);
}

$json = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    exit(1);
}

// For each release check if there is an uploaded asset, if not create and upload.
foreach ($json as $item) {
    // Search for tca.phar
    $found = false;
    if (!empty($item['assets'])) {
        foreach ($item['assets'] as $asset) {
            if ($asset['name'] === 'tca.phar') {
                $found = true;
                break;
            }
        }
    }

    // Already created?
    if ($found) {
        continue;
    }

    // Checkout tag.
    exec('git checkout -f ' . escapeshellarg($item['tag_name']));

    // Install composer dependencies.
    exec('composer install --no-dev -o -n -q');

    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $item['tag_name'] . '.phar';

    $phar = new Phar($file);
    $phar->buildFromDirectory(__DIR__ . '/../../');
    $phar->setDefaultStub('bin/console.php');
    $phar->setStub("#!/usr/bin/env php\n" . $phar->getStub());
    $phar->compressFiles(Phar::GZ);
    unset($phar);

    $curl = curl_init(str_replace('{?name,label}', '?name=tca.phar', $item['upload_url']));
    curl_setopt_array(
        $curl,
        [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => file_get_contents($file),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Accept: application/vnd.github.v3+json',
                'Authorization: Token ' . $_SERVER['GITHUB_TOKEN'],
                'User-Agent: ' . $_SERVER['GITHUB_ACTOR']
            ],
            CURLOPT_RETURNTRANSFER => true
        ]
    );
    curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($code !== 201) {
        exit(1);
    }
}

exit(0);
