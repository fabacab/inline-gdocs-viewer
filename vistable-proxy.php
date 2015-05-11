<?php
require_once dirname(__FILE__) . '/lib/vistable.php';

$url = isset($_GET['url']) ? rawurldecode($_GET['url']) : '';
$tqx = isset($_GET['tqx']) ? $_GET['tqx'] : '';
$tq = isset($_GET['tq']) ? $_GET['tq'] : '';
$tqrt = isset($_GET['tqrt']) ? $_GET['tqrt'] : '';
$tz = isset($_GET['tz']) ? $_GET['tz'] : 'PDT';
$locale = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? $_SERVER["HTTP_ACCEPT_LANGUAGE"] : 'en_US';

if (isset($_GET['locale'])) {
    $locale = $_GET['locale'];
}

$extra = array();
if (isset($_GET['debug']) && $_GET['debug']) {
    header('Content-type: text/plain; charset="UTF-8"');
    $extra['debug'] = 1;
}

$vt = new csv_vistable($tqx, $tq, $tqrt,$tz,$locale,$extra);

$data = file_get_contents($url);

$vt->setup_table($data);

$result = $vt->execute();

print $result;
