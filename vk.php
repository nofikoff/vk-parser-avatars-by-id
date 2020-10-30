<?php
//- берем id контакта из БД со статусом 0 - заходим на страницу вида vk.com/idXXX - полученоого контента парсим адрес аватарки
//- читаем аватарку - сохраняем на диск ввиде 7f1de29e/6da19d22/b51c6800/1e7e0e54/135.jpg
//- апдейтим в БД соответсвующий статус id

class vk
{
    public $http_client_use_proxy = 0;
    public $DB;
    public $error = [];
    public $client;


    public $headers =
        [
            'authority=>vk.com',
            'pragma=>no-cache',
            'cache-control=>no-cache',
            'upgrade-insecure-requests=>1',
            'user-agent=>Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
            'accept=>text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'sec-fetch-site=>none',
            'sec-fetch-mode=>navigate',
            'sec-fetch-user=>?1',
            'sec-fetch-dest=>document',
            'accept-language=>en,ru;q=0.9,uk;q=0.8',
        ];


    function __construct()
    {
        $this->client = new GuzzleHttp\Client(['headers' => $this->headers]);
    }


    //-1 Страница скрыта - толко для авторизированных пользователей
    function get_vk_page_avatar_link($id_profile)
    {

        $res = $this->client->request('GET', 'https://vk.com/id' . $id_profile);
        if ($res->getStatusCode() !== 200) {
            $this->error[] = $id_profile . " get_vk_page_avatar_link - код не 200 -> " . $res->getStatusCode();
            return -1;
        }

        // "200"
        //echo $res->getHeader('content-type')[0];
        //print_r($res->getHeader('content-type'));
        // 'application/json; charset=utf8'
        $content = $res->getBody();
        // {"type":"User"...'

        echo "\nhttps://vk.com/id" . $id_profile . " смотрим аватарку \n";

        if (preg_match('~"([^"]+?)&ava=1~um', $content, $urls)) {
            // директория
            $dir = md5($id_profile);
            $parts = str_split($dir, 8);
            $dir = 'images/' . implode("/", $parts);
            if (!mkdir($concurrentDirectory = $dir, 0777, true) && !is_dir($concurrentDirectory)) {
                //throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                $this->error[] = $id_profile . sprintf(' Directory "%s" was not created', $concurrentDirectory);
                return -1;
            }
            // качаем файл
            try {
                $this->client->request('GET', $urls[1], ['sink' => $dir . '/' . $id_profile . '.jpg']);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->error[] = $id_profile . "Не могу прочитать скачать аватрку по URL " . $urls[1] . ' ' . print_r($e, true);
                return -1;
            }
            // все норм
            echo $urls[1];
            return 1;
        }

        if (preg_match('~deactivated_hid_~um', $content, $urls)) {
            $this->error[] = $id_profile . " get_vk_page_avatar_link - страница скрыта";
            return -2;

        } else {
            $this->error[] = $id_profile . " get_vk_page_avatar_link - не известная ошибка";
            return -1;

        }


    }


    // чтбы не отвалилась вызывем перед каждой операциенй тяжелой
    function connect_mysql()
    {


    }

    function disconnect_mysql()
    {


    }





}
