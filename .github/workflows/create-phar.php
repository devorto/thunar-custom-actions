<?php

$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tca.phar';

$phar = new Phar($file);
$phar->buildFromDirectory(__DIR__ . '/../../');
$phar->setDefaultStub('bin/console.php');
$phar->setStub("#!/usr/bin/env php\n" . $phar->getStub());
$phar->compressFiles(Phar::GZ);
unset($phar);

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
echo $json . PHP_EOL;
if ($code !== 200) {
    exit(1);
}

$tag = str_replace('refs/tags/', '', $_SERVER['GITHUB_REF']);
$json = json_decode($json, true);
foreach ($json as $item) {
    if ($item['tag_name'] === $tag) {
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
        $json = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        echo $json . PHP_EOL;
        if ($code !== 201) {
            exit(1);
        }

        break;
    }
}

exit(0);
