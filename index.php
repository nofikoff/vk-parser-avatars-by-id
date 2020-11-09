<?php

require('./vendor/autoload.php');
require('config.php');
require('vk.php');

$vk = new vk();
$res = $vk->get_stat();
$total = $res['ok'] + $res['deleted'] + $res['zero'];
echo "<br> Всего в базе 1048574<br> 
- картинки сохранены: " . $res['ok'] . " 
<br>- нет картинок у заблокированных/удаленых эккаунтов: " . $res['deleted']."
<br>- анкету или не смог открыть или картинка битая <a href='https://vk.com/id37627246'>пример</a>: " . $res['zero']."
<br>- в очереди на сканирование: " . $res['left'];


//echo $vk->get_vk_page_avatar_link(116580319);
