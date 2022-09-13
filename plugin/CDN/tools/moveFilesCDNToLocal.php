<?php
$config = dirname(__FILE__) . '/../../../videos/configuration.php';
require_once $config;

if (!isCommandLineInterface()) {
    return die('Command Line only');
}

$isCDNEnabled = AVideoPlugin::isEnabledByName('CDN');

if (empty($isCDNEnabled)) {
    return die('Plugin disabled');
}

ob_end_flush();
set_time_limit(300);
ini_set('max_execution_time', 300);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$global['rowCount'] = $global['limitForUnlimitedVideos'] = 999999;
$path = getVideosDir();
$total = Video::getTotalVideos("", false, true, true, false, false);
$videos = Video::getAllVideosLight("", false, true, false);
$count = 0;

$countStatusNotActive = 0;
$countMoved = 0;

$sites_id_to_move = [];

foreach ($videos as $key => $value) {
    $count++;
    //echo "{$count}/{$total} Checking {$global['webSiteRootURL']}v/{$value['id']} {$value['title']}" . PHP_EOL;
    if (empty($value['sites_id'])) {
        echo "sites_id is empty {$value['sites_id']}" . PHP_EOL;
        continue;
    }
    $sites_id_to_move[] = $value['id'];
    echo "{$key}/{$total} added to move {$global['webSiteRootURL']}v/{$value['id']} {$value['title']}" . PHP_EOL;
}

$total = count($sites_id_to_move);
foreach ($sites_id_to_move as $key => $value) {
    echo "{$key}/{$total} Start move {$value}" . PHP_EOL;
    $startF = microtime(true);
    
    //$response = CDNStorage::get($value, 4);
    $response = CDNStorage::ftp_get($value);
    if (empty($response)) {
        echo "{$key}/{$total} ERROR " . PHP_EOL;
    } else {
        $endF = microtime(true) - $startF;
        $ETA = ($total - $key + 1) * $endF;
        $ps = humanFileSize($response['totalBytesTransferred'] / ($endF));
        echo "{$key}/{$total} Moved done {$value} filesCopied={$response['filesCopied']} totalBytesTransferred=" . humanFileSize($response['totalBytesTransferred']) . " in " . secondsToDuration($endF) . " ETA: " . secondsToDuration($ETA) . " " . $ps . 'ps' . PHP_EOL;
    }
    exit;
}

echo "StatusNotActive=$countStatusNotActive; Moved=$countMoved;" . PHP_EOL;
echo PHP_EOL . " Done! " . PHP_EOL;
die();
