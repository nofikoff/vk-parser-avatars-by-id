<?php
//- берем id контакта из БД со статусом 0 - заходим на страницу вида vk.com/idXXX - полученоого контента парсим адрес аватарки
//- читаем аватарку - сохраняем на диск ввиде 7f1de29e/6da19d22/b51c6800/1e7e0e54/135.jpg
//- апдейтим в БД соответсвующий статус id

class vk
{
    public $http_client_use_proxy = 1;
    public $pdo;
    public $error = [];
    public $client;
    public $curl_debug = 0;


    public $headers =
        [
            'authority: vk.com',
            'pragma: no-cache',
            'cache-control: no-cache',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'sec-fetch-site: none',
            'sec-fetch-mode: navigate',
            'sec-fetch-user: ?1',
            'sec-fetch-dest: document',
            'accept-language: en,ru;q=0.9,uk;q=0.8',
        ];


    function __construct()
    {


    }


    //-1 Страница скрыта - толко для авторизированных пользователей
    function get_vk_page_avatar_link($id_profile)
    {


        $content = $this->send_http('https://vk.com/id' . $id_profile, "GET", $this->headers);


        // нихуя заголовки не работают
        // оставил старый добрый CURL
        // $res = $this->client->request('GET', 'https://vk.com/id' . $id_profile, ['headers', $this->headers]);
        //echo $res->getStatusCode();
        //        if ($res->getStatusCode() !== 200) {
        //            $this->error[] = $id_profile . " get_vk_page_avatar_link - код не 200 -> " . $res->getStatusCode();
        //            return -1;
        //        }
        //echo $res->getHeader('content-type')[0];
        //print_r($res->getHeader('content-type'));
        // 'application/json; charset=utf8'
        //echo $content = $res->getBody();

        echo "\nhttps://vk.com/id" . $id_profile . " смотрим аватарку \n";

        if (preg_match('~(deactivated|camera_200|service_msg_null|spamfight|404 Not Found)~m', $content, $urls)) {
            $this->error[] = $id_profile . " get_vk_page_avatar_link - страница скрыта или удалена";
            return -2;

        } else if (preg_match('~"([^"]+?)ava=1~m', $content, $urls)) {

            // директория
            $dir = md5($id_profile);
            $parts = str_split($dir, 2);
            //$parts = str_split($dir, 8);
            //$dir = 'images/' . implode("/", $parts);
            $dir = 'images/' . $parts[0] . "/" . $parts[1] . "/" . $parts[2];


            if (!mkdir($concurrentDirectory = $dir, 0777, true) && !is_dir($concurrentDirectory)) {
                //throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                $this->error[] = $id_profile . sprintf(' Directory "%s" was not created', $concurrentDirectory);
                return -1;
            }
            // качаем файл

            $urls[1] = str_replace('&amp;', '&', $urls[1]);

            try {
                $this->client = new GuzzleHttp\Client();
                $this->client->request('GET', $urls[1], ['sink' => $dir . '/' . $id_profile . '.jpg', 'headers', $this->headers]);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->error[] = $id_profile . "Не могу прочитать скачать аватрку по URL " . $urls[1] . ' ' . print_r($e, true);
                $this->logs("bad_url_ava.txt", $id_profile . "Не могу прочитать скачать аватрку по URL " . $urls[1]);
                return -1;
            }
            // все норм
            echo $urls[1];
            return 1;
        } else {

            $this->error[] = $id_profile . " get_vk_page_avatar_link - не известная ошибка" . $content;
            $this->logs("bad_responce.txt", $id_profile . " get_vk_page_avatar_link - не известная ошибка" . $content);
            return -1;

        }


    }


    // чтбы не отвалилась вызывем перед каждой операциенй тяжелой
    function connect_mysql()
    {
        //Custom PDO options.
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        );

        //Connect to MySQL and instantiate our PDO object.
        $this->pdo = new PDO("mysql:host=localhost;dbname=" . _DBNAME, _USER, _PASSWORD, $options);

    }

    function disconnect_mysql()
    {

    }


    function get_stat()
    {
        $this->connect_mysql();

        $sql = "SELECT count(*) as count FROM `images` WHERE `status` = 1";
        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $ok = $statement->fetch(PDO::FETCH_ASSOC)['count'];

        $sql = "SELECT count(*) as count FROM `images` WHERE `status` = '-2'";
        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $notok = $statement->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'ok' => $ok,
            'notok' => $notok,
        ];
    }

    function get_id()
    {
        $this->connect_mysql();

        $sql = "SELECT `id` FROM `images` WHERE `status` = 0 LIMIT 0,1";

//Prepare our SELECT statement.
        $statement = $this->pdo->prepare($sql);

//Execute our SELECT statement.
        $statement->execute();

//Fetch the row.
        return $statement->fetch(PDO::FETCH_ASSOC)['id'];

    }

    function update_status($id, $status)
    {

        $this->connect_mysql();
        $sql = "UPDATE `images` SET `status` = :status WHERE `id` = :id;";

//Prepare our statement.
        $statement = $this->pdo->prepare($sql);

//Bind our values to our parameters (we called them :make and :model).
        $statement->bindValue(':status', $status);
        $statement->bindValue(':id', $id);

//Execute the statement and insert our values.
        $inserted = $statement->execute();

//Because PDOStatement::execute returns a TRUE or FALSE value,
//we can easily check to see if our insert was successful.
        if ($inserted) {
            echo 'Row updated!\n';
        }
        $this->pdo = null;

    }

    function send_http($url, $method, $headers, $post_fields = '')
    {


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }

        if ($this->http_client_use_proxy) {
            // сенил на вадим погоняло
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($ch, CURLOPT_PROXY, '83.149.70.159:13012');
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->file_cookies);
//        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->file_cookies);
// Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


// DEBUG
        if ($this->curl_debug) {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        }

        $server_output = curl_exec($ch);
        if ($this->curl_debug) echo "\r\nCurl ответ: " . $server_output;


// DEBUG
        if ($this->curl_debug) {
            print_r(curl_getinfo($ch));
        }

        curl_close($ch);

        // Магия, генерим на стороне клиента куки city_id в локальном хранилище
        // Без него авторизация и другие запросы не проканают

        return $server_output;
    }

    private function logs($filelog_name, $message)
    {
        $fd = fopen(__DIR__ . "/logs/" . $filelog_name, "a");
        fwrite($fd, date("Ymd-G:i:s")
            . " -------------------------------- \n\n" . $message . "\n\n");
        fclose($fd);
    }


}
