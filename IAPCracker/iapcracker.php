<?php
/*
 in-app-proxy
 Copyright ZonD80
 Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 */


define ('PROXY',false); // if false, emulation, if true acts as proxy

date_default_timezone_set('UTC');

function NS_to_array($data) {
    preg_match_all('#"(.*?)" \= "(.*?)"\;#si',$data,$matches);
    foreach ($matches[1] as $key=>$match) {
        $return[$match] = $matches[2][$key];
    }
    return $return;
}

function array_to_NS($ar) {
    $return = array();
    foreach ($ar as $k=>$v) {
        $return[] = "\t\"$k\" = \"$v\"";
    }
    return "{\n".implode(";\n",$return).";\n}";
}

if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$OWNHEADERS = false;
if ($_SERVER['HTTP_X_APPLE_CLIENT_APPLICATION']||$_SERVER['HTTP_HOST']=='se.itunes.apple.com') die('<h1>Hi, dude!</h1><p style="color:green;">You <b>connected</b> to in-appstore.com, but...</p><p style="color:red;">Looks like you are using AppStore client. Please use <b>application itself</b> or remove DNS setting if you want to use AppStore client.</p><p><b>REMEMBER, THAT IN-APPSTORE.COM IS ONLY FOR LEGALLY PURCHASED APPS!</b></p>');
$fpath = 'log.txt';

//$file = fopen($fpath, 'a+'); // uncomment all fwrite and fopen/fclose to allow logging

$db = array(
    'host' => 'localhost',
    'user' => 'dababase user',
    'pass' => 'database password',
    'db' => 'database',
    'charset' => 'utf8'
);

//require_once ('classes/database.class.php');

//$DB = new DB($db);

unset($db);

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
    if (preg_match('/inAppBuy/', $_SERVER['REQUEST_URI']) || preg_match('/verifyReceipt/', $_SERVER['REQUEST_URI'])) $POST_CONTENT = http_get_request_body(); else
        $POST_CONTENT = http_build_query($_POST);
    //$POST_CONTENT = urlencode(http_get_request_body());
    // //fwrite($file,"\n\nPOST RAW: ".var_export(http_get_request_body(),true)."\n\n\n");
    $text = '!!!NOW POST!!!';
} else $METHOD = 'GET';


// caching request for future processing

if (!PROXY) {
if (preg_match('/offerAvailabilityAndInfoDialog/', $_SERVER['REQUEST_URI'])) {
    $to_db_get = explode(',', 'restrictionLevel,id,versionId,guid,quantity,offerName,lang,bid,bvrs,icuLocale');
    foreach ($to_db_get as $gv) {
        if ($gv == 'id') $key = 'salableadamid'; else $key = $gv;
        $to_db[$key] = getval($gv);

        if (in_array($gv,explode(',','restrictionLevel,lang,icuLocale,guid'))) continue;
        elseif ($gv == 'versionId') $key = 'appExtVrsId';
        elseif ($gv == 'id') $key = 'appAdamId'; else $key = $gv;
        $to_plist[$key] = getval($gv);
    }

    /// here salableadamid checks
    $app_adamids = array(
        'com.zeptolab.ctrexperiments'=>534185042,
        'com.ea.fca.inc'=>516027964,
        "com.sega.SangokushiConquest"=>492200219,
        "com.firemint.flightcontrolipad"=>363727129,
        "ru.mail.jugger"=>512970482,
        "com.fullfat.ios.agentdash"=>540410480
    );
    //
    $to_plist['salableAdamId'] = (array_key_exists($to_db['bid'],$app_adamids)?$app_adamids[$to_db['bid']]:'1');
    $to_plist['productType'] = 'A';
    $to_plist['price'] = '1';
    $to_plist['pricingParameters'] = 'STDQ';
    //$DB->query("INSERT INTO `cache` " . $DB->build_insert_query($to_db));
}
}
if ($_SERVER['SERVER_PORT']==443) $text .= ' !!! HTTPS'; else $text .= " !!! PLAIN";

$get = var_export($_GET, true);

$headers = getallheaders();

$text .= "\n\nuri:$uri\n\nserver:$server\n\n\nget:$get\n\n\npost:$post\n\npost_content:" . var_export($POST_CONTENT, true) . "\n\noriginal_headers:" . var_export($headers, true) . "\n\n";


//var_dump($headers);

$url = "http" . ($_SERVER['SERVER_PORT']==443 ? 's' : '') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$urlInfo = @parse_url($url);

$http_path = $urlInfo['path'];
$http_host = $urlInfo['host'];


if (preg_match('/authenticate/', $_SERVER['REQUEST_URI']) ) { // write now
    if (PROXY) {
    //fwrite($file, 'USING CURL!!!!!!!!');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, '_cookie.txt');

    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    foreach ($headers AS $hkey => $hval) {
        // if ($hkey == 'Accept-Encoding') continue;
        //if ($hkey == 'Host') $hval = $_SERVER['HTTP_HOST'];
        // if ($hkey == 'Connection') $hval = 'close';
        if (in_array($hkey, explode(',', 'Cookie,Host,Accept-Language,X-Apple-Store-Front')))
            $ha[] = "$hkey: $hval";
        // }
    }

    //$ha[] = 'Expect: 100-continue';

    //fwrite($file, "\n\nREQUEST HEADERS:" . var_export($ha, true));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $ha);
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $POST_CONTENT);
    $result2 = curl_exec($ch);

    $CURL = true;
    } else {
    $result2='<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd"> 

  <plist version="1.0">
    <dict>
       
  
    
    
    
         
    
    
        <key>accountInfo</key>
        <dict>
    <key>appleId</key><string>'.getval('appleId').'</string>
    <key>accountKind</key><string>0</string>
    <key>address</key>
    <dict>
      <key>firstName</key><string>John</string>
      <key>lastName</key><string>Appleseed</string>
    </dict>
  </dict>
        <key>passwordToken</key><string>38E551613891422919A2326B3AE8EB'.rand(0,16).'</string>
        <key>clearToken</key><string>303030303030313236383135333532'.rand(0,32).'</string>
        
        <key>is-cloud-enabled</key><string>false</string>
        
        <key>dsPersonId</key><string>'.$_SERVER['HTTP_X_DSID'].'</string>
<key>creditDisplay</key><string></string>

<key>creditBalance</key><string>1311811</string>
<key>freeSongBalance</key><string>1311811</string>


        
        
        
    
    
    <key>status</key><integer>0</integer>
    
    
       
       
    
    
    
    
    
    
    
    
    
    
    
    
    
  


    
    </dict>
  </plist>
  


';
    $OWNHEADERS=true;
    }
} else {
    $h = '';

    foreach ($headers AS $hkey => $hval) {
        // if ($hkey=='Cookie') continue;
        if ($hkey == 'Content-Length' && $METHOD == 'POST') $hval = strlen($POST_CONTENT);
        if ($hkey == 'Host') $hval = $_SERVER['HTTP_HOST'];
        if ($hkey == 'Connection') $hval = 'close';

        $h .= "$hkey: $hval\r\n";
        //}
    }
    $opts = array('http' =>
    array(
        'method' => $METHOD,
        'header' => $h,
        'protocol_version' => '1.1',
        'timeout' => 3,
        'content' => $POST_CONTENT
        //'Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
    )
    );

    $text .= "context_options:" . var_export($opts, true) . "\n\nrequest_headers:" . var_export($h, true) . "\n\n";


    $context = stream_context_create($opts);
    // THIS LINE IS VERY, VERY, EXTREMELY IMPORTANT, I SPENT OVER $300 TO CODE IT!
    if (PROXY) $result = file_get_contents($url, false, $context); // UNCOMMENT THIS TO FETCH PURCHUASE LISTS FROM APPSTORE
    else {
    if (!preg_match('/inAppBuy/', $_SERVER['REQUEST_URI']) && !preg_match('/inAppTransactionDone/', $_SERVER['REQUEST_URI']) && !preg_match('/verifyReceipt/', $_SERVER['REQUEST_URI'])&& !preg_match('/offerAvailabilityAndInfoDialog/', $_SERVER['REQUEST_URI'])&& !preg_match('/verifyReceipt/', $_SERVER['REQUEST_URI']) && !preg_match('/inAppCheckRecurringDownloadQueue/', $_SERVER['REQUEST_URI']))
        $result = '';//$result = file_get_contents($url, false, $context); // UNCOMMENT THIS TO FETCH PURCHUASE LISTS FROM APPSTORE
    else {
        $result = '';
        $OWNHEADERS = true;
    }
    }
    //fwrite($file, "\n\nREQUEST-RESULT:$url " . var_export((!preg_match('/inAppBuy/', $_SERVER['REQUEST_URI']) && !preg_match('/inAppTransactionDone/', $_SERVER['REQUEST_URI']) && !preg_match('/verifyReceipt/', $_SERVER['REQUEST_URI'])), true) . var_export(gunzip($result), true) . "\n\n");
}

//fwrite($file, $text);
$text = '';
if (!$CURL&&!$OWNHEADERS) $result2 = gunzip($result); // else $result2=$result;

require_once('classes/plist.php');
$parser = new plistParser();

if (!PROXY) {
if (preg_match('/inAppCheckRecurringDownloadQueue/', $_SERVER['REQUEST_URI'])) {

    $result2 = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">

  <plist version="1.0">
    <dict>






  <key>jingleDocType</key><string>inAppSuccess</string>
  <key>jingleAction</key><string>inAppPendingTransactions</string>
  <key>dsid</key><string>' . $_SERVER['HTTP_X_DSID'] . '</string>




    <key>download-queue-item-count</key><integer>0</integer>





















    </dict>
  </plist>



';
}
elseif (preg_match('/offerAvailabilityAndInfoDialog/', $_SERVER['REQUEST_URI'])) {
    // //fwrite($file, "\n\nPARSED_RESPONSE_AVAILABILITY:" . var_export($parser->parseString($result2), true));


    $words = array('Vodka','Bears','Matryoshka','Ushanka','Balalaika','Samovar');
    $result2 = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">

  <plist version="1.0">
    <dict>






  <key>jingleDocType</key><string>inAppSuccess</string>
  <key>jingleAction</key><string>offerAvailabilityAndInfoDialog</string>
  <key>dsid</key><string></string>








      <key>dialog</key>
      <dict>


    <key>message</key><string>Do you like in-appstore.com?</string>
    <key>explanation</key><string>"'.$to_db['bid'].'"'."\n".'Tap "LIKE" to like. Enter random credentials on auth popup.</string>
    <key>defaultButton</key><string>Buy</string>


    <key>okButtonString</key><string>LIKE</string>
    <key>okButtonAction</key><dict>

    <key>kind</key><string>Buy</string>
    <key>buyParams</key><string>'.str_replace('&','&amp;',http_build_query($to_plist)).'</string>
    <key>itemName</key><string>'.$to_plist['offerName'].'</string>















</dict>


    <key>cancelButtonString</key><string>'.$words[array_rand($words)].'!</string>









</dict>


















    </dict>
  </plist>



';
    /*$replacements = $parser->parseString($result2);

    /*$bpsql = $DB->sqlesc($replacements['dialog']['okButtonAction']['buyParams']);
    $bp = $DB->query_row("SELECT id,invalid FROM bps WHERE bp=$bpsql");
    $reportnumber = $bp['id'];
    $invalid_purchase = $bp['invalid'];
    if (!$reportnumber) {

        $DB->query("INSERT IGNORE INTO bps (bp) VALUES (" . $bpsql . ")");
        $reportnumber = mysql_insert_id();
    }

    $reportnumber = 'SAFE_MODE';

    if ($invalid_purchase) {
        $result2 = str_replace($replacements['dialog']['message'], 'Like in-appstore.com?', $result2);
        $result2 = str_replace($replacements['dialog']['explanation'], "This purchase ($reportnumber) reported as invalid.\nIt means that this application requires signed by apple receipt.\n Would you like to really buy this in-app feature via AppStore?\n It will be cached for you to receive this in-app for free in future.", $result2);
        $result2 = str_replace("<key>okButtonString</key><string>{$replacements['dialog']['okButtonString']}</string>","<key>okButtonString</key><string>$$$</string>", $result2);

    } else {
        $result2 = str_replace($replacements['dialog']['message'], 'Like in-appstore.com?', $result2);
        $result2 = str_replace($replacements['dialog']['explanation'], "If purchuase fails, report this number ($reportnumber) to http://www.in-appstore.com/p/report-about-failed-purchases-here.html.\n If you like in-appstore.com, tap LIKE button!", $result2);
        $result2 = str_replace("<key>okButtonString</key><string>{$replacements['dialog']['okButtonString']}</string>","<key>okButtonString</key><string>LIKE</string>", $result2);
    }
    $result2 = str_replace($replacements['dialog']['cancelButtonString'], 'Nope', $result2);
*/

} elseif (preg_match('/inAppBuy/', $_SERVER['REQUEST_URI'])) {
    // get purchase

    $plist = $parser->parseString($POST_CONTENT);

    // $guid = $matches[0][0];
    //fwrite($file, "\n\n\nMATCHES " . var_export($plist, true));

    $to_transactions = array(
        'item-id' => $plist['salableAdamId'],
        'app-item-id' => $plist['appAdamId'],
        'version-external-identifier' => $plist['appExtVrsId'],
        'bid' => $plist['bid'],
        'bvrs' => $plist['bvrs'],
        'offer-name' => $plist['offerName'],
        'quantity' => $plist['quantity']

    );

    //$DB->query("INSERT INTO `transactions` " . $DB->build_insert_query($to_transactions));

    if (preg_match('/MacAppStore/',$_SERVER['HTTP_USER_AGENT'])) {
        $result2 = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">

  <plist version="1.0">
    <dict>



  <key>jingleDocType</key><string>inAppSuccess</string>
  <key>jingleAction</key><string>inAppBuy</string>
  <key>dsid</key><string></string>




    <key>download-queue-item-count</key><integer>' . $plist['quantity'] . '</integer>


    <key>app-list</key>
    <array>

      <dict>
        <key>item-id</key><integer>' . $plist['salableAdamId'] . '</integer>
        <key>app-item-id</key><integer>' . $plist['appAdamId'] . '</integer>

        <key>version-external-identifier</key><integer>' . $plist['appExtVrsId'] . '</integer>
        <key>bid</key><string>' . $plist['bid'] . '</string>
        <key>bvrs</key><string>' . $plist['bvrs'] . '</string>
        <key>offer-name</key><string>' . $plist['offerName'] . '</string>
        <key>transaction-id</key><string>17000002987893</string>
        <key>original-transaction-id</key><string>17000009878393</string>
        <key>purchase-date</key><date>2012-07-19T17:29:51Z</date>
        <key>original-purchase-date</key><date>2012-07-19T17:29:53Z</date>
        <key>quantity</key><integer>1</integer>

      </dict>

    </array>
    <key>receipt-data</key><data>fuck_you_all</data>





















    </dict>
  </plist>



';
    } else {


    $transid = rand(0,1800000);

        // apps that require valid receipts

        $custom_valid_receipt_required = array(
            "com.iconology",
            "com.zinio",
            "com.zeptolab",
            "com.futurenet",
            "com.disney",
            "com.gamevil",
            "com.appyentertainment",
            "ch.zattoo",
            "com.teamlava",
            "com.backpacker",
            "com.kalmbach",
            "com.pixelImages",
            "com.thedaily",
            "ubi.084",
            "com.ea",
            "com.firemint",
            "com.ndemiccreations",
            "com.gameloft",
            "com.utw",
            "com.kongzhong",
            "com.funzio",
            "com.sega",
            "ru.mail",
            "com.fullfat",
            "pl.presspublica",
            "com.paperlit",
            "pl.m2a.echodnia",
            "com.gsmchoice.Angora",
            "pl.przekroj.przekrojipad",
            "pl.pb.pulsPl",
            "pl.presspublica.Politykahd",
            "pl.agora.agorareader",
            "com.bodunov"
        );

        if (!preg_match("/(".implode('|',str_replace('.','\.',$custom_valid_receipt_required)).")/",$plist['bid'])) {

            $purchase_info = array (
                'original-purchase-date-pst' => date('Y-m-d H:i:s',time()-7*3600).' America/Los_Angeles',
                'purchase-date-ms' => time().'000',
                'original-transaction-id' => $transid,
                'bvrs' => $plist['bvrs'],
                'app-item-id' => $plist['appAdamId'],
                'transaction-id' => $transid,
                'quantity' => $plist['quantity'],
                'original-purchase-date-ms' => time().'000',
                'item-id' => $plist['salableAdamId'],
                'version-external-identifier' => $plist['appExtVrsId'],
                'product-id' => $plist['offerName'],
                'purchase-date' => date('Y-m-d H:i:s'). ' Etc/GMT',
                'original-purchase-date' => date('Y-m-d H:i:s'). ' Etc/GMT',
                'bid' => $plist['bid'],
                'purchase-date-pst' => date('Y-m-d H:i:s',time()-7*3600).' America/Los_Angeles'//,
                // "environment" = "Sandbox"
            );


            $receipt_data = 'ewoJInNpZ25hdHVyZSIgPSAiQXBkeEpkdE53UFUyckE1L2NuM2tJTzFPVGsyNWZlREthMGFhZ3l5UnZlV2xjRmxnbHY2UkY2em5raUJTM3VtOVVjN3BWb2IrUHFaUjJUOHd5VnJITnBsb2YzRFgzSXFET2xXcSs5MGE3WWwrcXJSN0E3ald3dml3NzA4UFMrNjdQeUhSbmhPL0c3YlZxZ1JwRXI2RXVGeWJpVTFGWEFpWEpjNmxzMVlBc3NReEFBQURWekNDQTFNd2dnSTdvQU1DQVFJQ0NHVVVrVTNaV0FTMU1BMEdDU3FHU0liM0RRRUJCUVVBTUg4eEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURXpNREVHQTFVRUF3d3FRWEJ3YkdVZ2FWUjFibVZ6SUZOMGIzSmxJRU5sY25ScFptbGpZWFJwYjI0Z1FYVjBhRzl5YVhSNU1CNFhEVEE1TURZeE5USXlNRFUxTmxvWERURTBNRFl4TkRJeU1EVTFObG93WkRFak1DRUdBMVVFQXd3YVVIVnlZMmhoYzJWU1pXTmxhWEIwUTJWeWRHbG1hV05oZEdVeEd6QVpCZ05WQkFzTUVrRndjR3hsSUdsVWRXNWxjeUJUZEc5eVpURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd2daOHdEUVlKS29aSWh2Y05BUUVCQlFBRGdZMEFNSUdKQW9HQkFNclJqRjJjdDRJclNkaVRDaGFJMGc4cHd2L2NtSHM4cC9Sd1YvcnQvOTFYS1ZoTmw0WElCaW1LalFRTmZnSHNEczZ5anUrK0RyS0pFN3VLc3BoTWRkS1lmRkU1ckdYc0FkQkVqQndSSXhleFRldngzSExFRkdBdDFtb0t4NTA5ZGh4dGlJZERnSnYyWWFWczQ5QjB1SnZOZHk2U01xTk5MSHNETHpEUzlvWkhBZ01CQUFHamNqQndNQXdHQTFVZEV3RUIvd1FDTUFBd0h3WURWUjBqQkJnd0ZvQVVOaDNvNHAyQzBnRVl0VEpyRHRkREM1RllRem93RGdZRFZSMFBBUUgvQkFRREFnZUFNQjBHQTFVZERnUVdCQlNwZzRQeUdVakZQaEpYQ0JUTXphTittVjhrOVRBUUJnb3Foa2lHOTJOa0JnVUJCQUlGQURBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQUVhU2JQanRtTjRDL0lCM1FFcEszMlJ4YWNDRFhkVlhBZVZSZVM1RmFaeGMrdDg4cFFQOTNCaUF4dmRXLzNlVFNNR1k1RmJlQVlMM2V0cVA1Z204d3JGb2pYMGlreVZSU3RRKy9BUTBLRWp0cUIwN2tMczlRVWU4Y3pSOFVHZmRNMUV1bVYvVWd2RGQ0TndOWXhMUU1nNFdUUWZna1FRVnk4R1had1ZIZ2JFL1VDNlk3MDUzcEdYQms1MU5QTTN3b3hoZDNnU1JMdlhqK2xvSHNTdGNURXFlOXBCRHBtRzUrc2s0dHcrR0szR01lRU41LytlMVFUOW5wL0tsMW5qK2FCdzdDMHhzeTBiRm5hQWQxY1NTNnhkb3J5L0NVdk02Z3RLc21uT09kcVRlc2JwMGJzOHNuNldxczBDOWRnY3hSSHVPTVoydG04bnBMVW03YXJnT1N6UT09IjsKCSJwdXJjaGFzZS1pbmZvIiA9ICJld29KSW05eWFXZHBibUZzTFhCMWNtTm9ZWE5sTFdSaGRHVXRjSE4wSWlBOUlDSXlNREV5TFRBM0xURXlJREExT2pVME9qTTFJRUZ0WlhKcFkyRXZURzl6WDBGdVoyVnNaWE1pT3dvSkluQjFjbU5vWVhObExXUmhkR1V0YlhNaUlEMGdJakV6TkRJd09UYzJOelU0T0RJaU93b0pJbTl5YVdkcGJtRnNMWFJ5WVc1ellXTjBhVzl1TFdsa0lpQTlJQ0l4TnpBd01EQXdNamswTkRrME1qQWlPd29KSW1KMmNuTWlJRDBnSWpFdU5DSTdDZ2tpWVhCd0xXbDBaVzB0YVdRaUlEMGdJalExTURVME1qSXpNeUk3Q2draWRISmhibk5oWTNScGIyNHRhV1FpSUQwZ0lqRTNNREF3TURBeU9UUTBPVFF5TUNJN0Nna2ljWFZoYm5ScGRIa2lJRDBnSWpFaU93b0pJbTl5YVdkcGJtRnNMWEIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFek5ESXdPVGMyTnpVNE9ESWlPd29KSW1sMFpXMHRhV1FpSUQwZ0lqVXpOREU0TlRBME1pSTdDZ2tpZG1WeWMybHZiaTFsZUhSbGNtNWhiQzFwWkdWdWRHbG1hV1Z5SWlBOUlDSTVNRFV4TWpNMklqc0tDU0p3Y205a2RXTjBMV2xrSWlBOUlDSmpiMjB1ZW1Wd2RHOXNZV0l1WTNSeVltOXVkWE11YzNWd1pYSndiM2RsY2pFaU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVaUlEMGdJakl3TVRJdE1EY3RNVElnTVRJNk5UUTZNelVnUlhSakwwZE5WQ0k3Q2draWIzSnBaMmx1WVd3dGNIVnlZMmhoYzJVdFpHRjBaU0lnUFNBaU1qQXhNaTB3TnkweE1pQXhNam8xTkRvek5TQkZkR012UjAxVUlqc0tDU0ppYVdRaUlEMGdJbU52YlM1NlpYQjBiMnhoWWk1amRISmxlSEJsY21sdFpXNTBjeUk3Q2draWNIVnlZMmhoYzJVdFpHRjBaUzF3YzNRaUlEMGdJakl3TVRJdE1EY3RNVElnTURVNk5UUTZNelVnUVcxbGNtbGpZUzlNYjNOZlFXNW5aV3hsY3lJN0NuMD0iOwoJInBvZCIgPSAiMTciOwoJInNpZ25pbmctc3RhdHVzIiA9ICIwIjsKfQ==';
       } else {

            $purchase_info = array (
                'original-purchase-date-pst' => date('Y-m-d H:i:s',time()-7*3600).' America/Los_Angeles',
                'purchase-date-ms' => time().'000',
                'original-transaction-id' => $transid,
                'bvrs' => $plist['bvrs'],
                'app-item-id' => $plist['appAdamId'],
                'transaction-id' => $transid,
                'quantity' => $plist['quantity'],
                'original-purchase-date-ms' => time().'000',
                'item-id' => $plist['salableAdamId'],
                'version-external-identifier' => $plist['appExtVrsId'],
                'product-id' => $plist['offerName'],
                'purchase-date' => date('Y-m-d H:i:s'). ' Etc/GMT',
                'original-purchase-date' => date('Y-m-d H:i:s'). ' Etc/GMT',
                'bid' => $plist['bid'],
                'purchase-date-pst' => date('Y-m-d H:i:s',time()-7*3600).' America/Los_Angeles'//,
                // "environment" = "Sandbox"
            );
        $NSpurchase_info = array_to_NS($purchase_info);
   $purchase_info = base64_encode($NSpurchase_info);

        $private_key='-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAM2mruqEJIG6TNlQ
906ht7+8Nd5Zas6ST0KC7bUQNM52wHQ0vj6XmljvL/cqul6N4bBQsFrwS7SNNZ/s
iChH/teyHOz87ia//NBbIMkKdVi7hoqAY+aSYX4g+kEiXcqVtdSnvb/Yao9+MIyo
GF6iKQ5TOFVT7/huLf71Y7N/ARiRAgMBAAECgYAwBRbc7eQ0YpMlP3Gv67UjUUhm
1gxJlgJp7nahC9q4xyPjPpmZtf61e4yAs3p3L7weVokHgwq6ayq1YB7fAQixW8X1
V2hiAC4gNPqktUdkkmZHqFsY16EJUPUiPdphP4lZcesp3zDYYJ7si+CH8FClRkTT
q1VENzqGGJBUKA7YkQJBAO98LYvJwDJxF2aqxM3HR2VhOs9rr+3oaSNxbbMLY1t9
qj8VqjAHBYX54ywqusCd2yco4meENTmHLGG3oGWNHZUCQQDb1TUOPxfEkIRkNibE
Nu2XNNFg7TgnE4oXeSXS+zphCW0EqJ+Y2kDbb16knH357KseX+G7aCPSl5PGkhUz
ZjgNAkEA00KTFy6JmrXC8/GPHQw/gkJMU+/mSZPtM7P7FqfkJTBs/6uH70gyaiav
bSXgisx2KExbtP+eyDnjP+xx1UOwJQJBAIgxTM9oszbqObtD+TxyszuMU3NzQ+ih
qFnmilJtprtbdZj/RvERtkC8fKwK79kYkOMej+DlIdxkX/8TneLcHzkCQQCWoZUC
kWXIN8JeWkfCDLOtf1+XdZ53n4jn+ciMzX7zu3FATHgr36tsNs7Q5bncoKLiefuN
U5tRKPm+9w0Bm+Pi
-----END PRIVATE KEY-----
';
        $pkeyid = openssl_get_privatekey($private_key);
// compute signature
        openssl_sign(chr(2).$NSpurchase_info, $signature, $pkeyid);


        // free the key from memory
        openssl_free_key($pkeyid);
        $pucert = file_get_contents('pucert.cer');
   $to_receipt = array (
       'signature' => base64_encode(chr(2).$signature.pack('N',strlen($pucert)).$pucert),
       'purchase-info' => $purchase_info,
       'pod' => $_COOKIE['Pod'],
       'signing-status' => '0',
   );

   $receipt_data = base64_encode(array_to_NS($to_receipt));
        }
    $result2 = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">

  <plist version="1.0">
    <dict>






  <key>jingleDocType</key><string>inAppSuccess</string>
  <key>jingleAction</key><string>inAppBuy</string>
  <key>dsid</key><string></string>




    <key>download-queue-item-count</key><integer>' . $plist['quantity'] . '</integer>


    <key>app-list</key>
    <array>

      <dict>
        <key>item-id</key><integer>' . $plist['salableAdamId'] . '</integer>
        <key>app-item-id</key><integer>' . $plist['appAdamId'] . '</integer>

        <key>version-external-identifier</key><integer>' . $plist['appExtVrsId'] . '</integer>
        <key>bid</key><string>' . $plist['bid'] . '</string>
        <key>bvrs</key><string>' . $plist['bvrs'] . '</string>
        <key>offer-name</key><string>' . $plist['offerName'] . '</string>
        <key>transaction-id</key><string>'.$transid.'</string>
        <key>original-transaction-id</key><string>'.$transid.'</string>
        <key>purchase-date</key><date>'.date('Y-m-d\TH:i:s\Z').'</date>
        <key>original-purchase-date</key><date>'.date('Y-m-d\TH:i:s\Z').'</date>
        <key>quantity</key><integer>' . $plist['quantity'] . '</integer>
        <key>receipt-data</key><data>'. $receipt_data.'</data>
      </dict>

    </array>






















    </dict>
  </plist>';

    }
}
elseif (preg_match('/inAppTransactionDone/', $_SERVER['REQUEST_URI'])) {
    $result2 = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">

  <plist version="1.0">
    <dict>






  <key>jingleDocType</key><string>inAppSuccess</string>
  <key>jingleAction</key><string>inAppTransactionDone</string>
  <key>dsid</key><string>' . $_SERVER['HTTP_X_DSID'] . '</string>
























    </dict>
  </plist>';
    //counter
    file_put_contents($fpath,(file_get_contents($fpath)+1));
}
elseif (preg_match('/verifyReceipt/', $_SERVER['REQUEST_URI'])) {
    //$CURL=true;
    $receipt = (array)json_decode(str_replace(array("\r\n", "\n", "\r"),"",$POST_CONTENT));
    $receipt = base64_decode($receipt['receipt-data']);
    $receipt = NS_to_array($receipt);

    $to_receipt = NS_to_array(base64_decode($receipt['purchase-info']));
    /*$to_receipt['bid'] = 'com.zeptolab.ctrexperiments';
    $to_receipt['bvrs'] = '1.4';
    $to_receipt['app-item-id'] = '450542233';
    $to_receipt['version-external-identifier'] = '9051236';
    $to_receipt['item-id'] = '534194173';
    $to_receipt['product-id'] = 'com.zeptolab.ctrbonus.superpower4';
    $to_receipt['transaction-id'] = '23';
    $to_receipt['orginal-transaction-id'] = '23';*/

    //$pcdecode = json_decode(base64_decode(json_decode($POST_CONTENT['receipt-data'])));
    //$receipt = base64_decode($pcdecode['purchase-info']);


    $result2 = str_replace('-','_',stripslashes(json_encode(array('receipt' => $to_receipt, 'status' => 0))));
}
//$plistdoc =

}
if (!$CURL) $result_out = gzencode($result2); else {

    list($http_response_header, $result_out) = explode("\r\n\r\n", $result2, 2);
    $http_response_header = explode("\r\n", $http_response_header);
}

$text .= "response_raw:" . var_export($result, true) . "\n\n";
$text .= "to_out:" . var_export($result_out, true) . "\n\n";
$text .= "response:" . var_export($result2, true) . "\n\nresponse_headers:" . var_export($http_response_header, true);
$text .= "\n=======================================================\n";
//fwrite($file, $text);
//fclose($file);
//file_put_contents('iapcracker.txt', $result);

if ($OWNHEADERS||!$http_response_header) $http_response_header = array(
    0 => 'HTTP/1.1 200 Apple WebObjects',
    1 => 'x-apple-max-age: 0',
    2 => 'pod: '.$_COOKIE['Pod'],
    3 => 'x-apple-timing-app: 23 ms',
    4 => 'content-encoding: gzip',
    5 => 'x-apple-request-store-front: '.$_SERVER['HTTP_X_APPLE_STORE_FRONT'],
    6 => 'x-apple-translated-wo-url: '.$_SERVER['REQUEST_URI'],
    7 => 'x-apple-orig-url-path: '.$_SERVER['REQUEST_URI'],
    8 => 'x-apple-application-site: NWK',
    9 => 'edge-control: cache-maxage=60s',
    10 => 'edge-control: no-store',
    11 => 'edge-control: max-age=0',
    12 => 'set-cookie: Pod='.$_COOKIE['Pod'].'; version="1"; expires=Sat, 11-Aug-2020 23:08:06 GMT; path=/; domain=.apple.com',
    13 => 'cache-control: private',
    14 => 'cache-control: no-cache',
    15 => 'cache-control: no-store',
    16 => 'cache-control: no-transform',
    17 => 'cache-control: must-revalidate',
    18 => 'cache-control: max-age=0',
    19 => 'x-apple-asset-version: 110151',
    20 => 'expires: Wed, 11 Jul 2020 23:08:06 GMT',
    21 => 'content-type: text/xml; charset=UTF-8',
    22 => 'x-apple-lokamai-no-cache: true',
    23 => 'x-apple-date-generated: '.gmdate('D, M Y G:i:s \\G\\M\\T'),
    24 => 'x-apple-application-instance: 171108',
    25 => 'pragma: no-cache',
    26 => 'x-webobjects-loadaverage: 0',
    27 => 'content-length: '.strlen($result_out),
    28 => 'Date: '.gmdate('D, M Y G:i:s \\G\\M\\T')
);

foreach ($http_response_header as $header) {
    // if (preg_match('/Cookie/',$header)) continue;
    header($header);
}
echo $result_out;
?>