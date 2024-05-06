<?php
include("offer.php");
include("pdo.php");

$pdo = getPDO();

$homepage = getPage('https://useme.com/pl/jobs/');

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

function getPage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
