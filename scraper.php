<?php
include("config.php");
include("offer.php");
include("pdo.php");

if (!(isset($useme_direct_ip))) {
    echo "ERROR: Configuration variables not found!";
    exit();
}

$pdo = getPDO();

$homepage = getPage('https://useme.com/pl/jobs/', $useme_direct_ip);

$offers = [];
$regex = '/<a\s*href="(.*?,(\d+)\/)"\s*class="[^"]*job__title-link/m';
$links = [];
preg_match_all($regex, $homepage, $links, PREG_SET_ORDER, 0);

foreach ($links as $link) {
    array_push($offers, new Offer($pdo, 'https://useme.com' . $link[1], $link[2]));
}

foreach ($offers as $offer) {
    if ($offer->isNew()) {

        echo "New offer</br>";
        $offer->getDetails();

        if ($offer->save())
            $offer->sendMail();
    }
    echo "Offer exist</br>";
}

function getPage($url, string $ip)
{
    $parsed = parse_url($url);
    $host = $parsed['host'];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Host: {$host}"
        ],
        CURLOPT_RESOLVE => [
            "{$host}:443:{$ip}"
        ],
    ]);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
