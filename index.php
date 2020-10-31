<?php

require('./vendor/autoload.php');
require('config.php');
require('vk.php');

$vk = new vk();
$res = $vk->get_stat();

echo "Из просканированных анкет: картинки сохранены " . $res['ok'] . " + нет картинок у заблокированных эккаунтов " . $res['notok'];
