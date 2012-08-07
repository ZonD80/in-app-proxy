<?php
/*
 in-app-proxy developer server emulator
 Copyright ZonD80
 Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 */
date_default_timezone_set('UTC');

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$headers = getallheaders();

if ($headers['Host'] == $_SERVER['SERVER_ADDR']) die('loop request');


$file = fopen('log.txt', 'a+');

function write_log($line)
{
//return;
    global $file;
    if ($_SERVER['REMOTE_ADDR'] == '62.117.81.116')
        fwrite($file, $line);
}

function NS_to_array($data)
{
    preg_match_all('#"(.*?)" \= "(.*?)"\;#si', $data, $matches);
    foreach ($matches[1] as $key => $match) {
        $return[$match] = $matches[2][$key];
    }
    return $return;
}

function array_to_NS($ar)
{
    $return = array();
    foreach ($ar as $k => $v) {
        $return[] = "\t\"$k\" = \"$v\"";
    }
    return "{\n" . implode(";\n", $return) . ";\n}";
}


function getval($name, $type = 'string')
{
    if ($_GET[$name]) {
        $t = $_GET[$name];
    } else
        $t = $_POST[$name];
    eval('$t = (' . $type . ')$t;');
    return $t;
}

function gunzip($zipped)
{
    $offset = 0;
    if (substr($zipped, 0, 2) == "\x1f\x8b")
        $offset = 2;
    if (substr($zipped, $offset, 1) == "\x08") {
        # file_put_contents("tmp.gz", substr($zipped, $offset - 2));
        return gzinflate(substr($zipped, $offset + 8));
    }
    return "Unknown Format";
}

$text = '';
$uri = (string)$_GET['URI'];
$server = var_export($_SERVER, true);
$post = var_export($_POST, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $METHOD = 'POST';
    $POST_CONTENT = http_get_request_body();
    //  $POST_CONTENT = http_build_query($_POST);
    //$POST_CONTENT = urlencode(http_get_request_body());
    write_log("\n\nPOST RAW: " . var_export(http_get_request_body(), true) . "\n\n\n");
    $text = '!!!NOW POST!!!';
} else $METHOD = 'GET';


// caching request for future processing

if ($_SERVER['SERVER_PORT'] == 443) $text .= ' !!! HTTPS'; else $text .= " !!! PLAIN";

$get = var_export($_GET, true);


$text .= "\n\nuri:$uri\n\nserver:$server\n\n\nget:$get\n\n\npost:$post\n\npost_content:" . var_export($POST_CONTENT, true) . "\n\noriginal_headers:" . var_export($headers, true) . "\n\n";


//var_dump($headers);

$url = "http" . ($_SERVER['SERVER_PORT'] == 443 ? 's' : '') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$urlInfo = @parse_url($url);

$http_path = $urlInfo['path'];
$http_host = $urlInfo['host'];


$h = '';

foreach ($headers AS $hkey => $hval) {
    // if ($hkey=='Cookie') continue;
    if ($hkey == 'Content-Length' && $METHOD == 'POST') $hval = strlen($POST_CONTENT);
    if ($hkey == 'Host') $hval = $_SERVER['HTTP_HOST'];
    if ($hkey == 'Connection') $hval = 'close';
    if ($hkey == 'Expect') continue;

    $h .= "$hkey: $hval\r\n";
    //}
}

$h .= "Connection: close\r\n";
$opts = array('http' =>
array(
    'method' => $METHOD,
    'header' => $h,
    'protocol_version' => 1.1,
    'timeout' => 3,
    'content' => $POST_CONTENT
    //'Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
)
);

$text .= "context_options:" . var_export($opts, true) . "\n\nrequest_headers:" . var_export($h, true) . "\n\n";


$context = stream_context_create($opts);

write_log($text);

// gameloft emulation

if (preg_match('/gameloft/', $_SERVER['HTTP_HOST'])) {
    if ($_POST['action'] == 'transaction') {
        $receipt = NS_to_array(base64_decode($_POST['rdata']));
        $receipt = NS_to_array(base64_decode($receipt['purchase-info']));
        $product_id = $receipt['product-id'];

        $result = gzencode('{"id":"' . $product_id . '","type":"virtual_cash","virtual_cash_type":"cash","amount":9999999,"validation_type":"consumable"}');
    } elseif ($_POST['action'] == 'end_transaction') {
        $result = gzencode('{"code":1}');
    }
    else
        $result = file_get_contents($url, false, $context); // UNCOMMENT THIS TO FETCH PURCHUASE LISTS FROM APPSTORE
} elseif (preg_match('/terrhq/', $_SERVER['HTTP_HOST'])) {
   if (preg_match('/bank/', $_SERVER['REQUEST_URI'])) {
       $result = gzencode('{ "status": 1, "error": "", "data": "[\"170000030704571\"]", "signature": "15b7d0e8b239a44a7f04c3ec4fae1c11" }');
   } elseif (preg_match('/sync/', $_SERVER['REQUEST_URI'])) {
       die();
   }
   elseif (preg_match('/metric/', $_SERVER['REQUEST_URI'])) {
       die();
   }else
   $result = file_get_contents($url, false, $context); // UNCOMMENT THIS TO FETCH PURCHUASE LISTS FROM APPSTORE

}

else        $result = file_get_contents($url, false, $context); // UNCOMMENT THIS TO FETCH PURCHUASE LISTS FROM APPSTORE

$text = '';
$result_out = $result;

$text .= "response_raw:$result\n\n";
$text .= "to_out:$result_out\n\n";
$text .= "response:" . var_export(gunzip($result), true) . "\n\nresponse_headers:" . var_export($http_response_header, true);
$text .= "\n=======================================================\n";
write_log($text);
//fclose($file);
//file_put_contents('iapcracker.txt', $result);

if ($OWNHEADERS || !$http_response_header) $http_response_header = array(

    0 => 'HTTP/1.1 200 OK',
    1 => 'Server: nginx/1.1.14',
    2 => 'Date: Sat, 28 Jul 2012 13:46:50 GMT',
    3 => 'Content-Type: text/html',
    4 => 'Connection: keep-alive',
    5 => 'Content-Encoding: gzip',
    6 => 'Date: ' . gmdate('D, M Y G:i:s \\G\\M\\T')
);

foreach ($http_response_header as $header) {
    // if (preg_match('/Cookie/',$header)) continue;
    header($header);
}
echo $result_out;
?>