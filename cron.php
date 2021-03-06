<?php
//
//Дано : написать парсер аватарок в контакте, дан текстовый файл со списком id контактов.
//Будем писать на PHP т.к. высока веротяность что вконтакте будет блокировать по IP и нонадобится использовать прокси в ограниченное количество потоков.
//
//- инициализаируем БД, выполн запрос для импорта миллиона id в MySQL
//	LOAD DATA INFILE '/list.csv' INTO TABLE images (`id`)
//- берем id контакта из БД со статусом 0 - заходим на страницу вида vk.com/idXXX - полученоого контента парсим адрес аватарки
//- читаем аватарку - сохраняем на диск в виде 7f1de29e/6da19d22/b51c6800/1e7e0e54/135.jpg (т.к. миллион картинок  водной директории хранить нельзя)
//- апдейтим в БД соответсвующий статус id
//- запуск по крону каждые 5 минут, таймаут выставить 30 секунд, если что то пойдет не так - подключить Sentry сервис
//	за один такт в цикле можно обрабатывать N записей
//- если получим бан - подключаем прокси (поддерживает до 10 потоков)
//
//Расчетное время обработки базы
//- один такт 5 минут х 50 анкет
//- прокси поддерживает 10 потоков
//- итого в сутки 5 Х 12час Х 24 х 100 анкеттакт Х 10 потоков 250 тыщ сутки


// снять процесс
// kill -9 18722
// kill -9 8878
// найти процесс по имени
// ps aux | grep -i php

require('./vendor/autoload.php');
require('config.php');
require('vk.php');

// бесконечный цикл
while (true) {
$vk = new vk();
$id = $vk->get_id();
if (!$id) die ("\n ************** End id $id *****************\n");
// отключаем БД
$vk->pdo = null;
// читаем из БД id
$status = $vk->get_vk_page_avatar_link($id);
// пишем в БД ответ
$vk->update_status($id, $status);
// отключаем БД
//$vk->pdo = null;
//логируем ошибки
if (count($vk->error)) print_r($vk->error);
}


