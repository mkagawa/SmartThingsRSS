<?php
$hdr = getallheaders();
if($hdr['User-Agent'] !== 'Apache-HttpClient/UNAVAILABLE') {
    http_response_code(500);
    die();
}

$token = file_get_contents(".token");
$ch = curl_init();
$res = apicall($ch, "");
$items = [];
date_default_timezone_set('America/Los_Angeles');
foreach($res as $id=>$name) {
    $res = apicall($ch, $id."/status");
    $ts = date("h:i A",$res['ts']);
    $items[$name] = ["${res['t']}F / ${res['h']}%","$name is ${res['t']} degree and humidity is ${res['h']} percent as of $ts"];
}
curl_close($ch);

//------
function apicall($ch, $suffix) {
    global $token;

    $url = "https://api.smartthings.com/v1/devices/$suffix";
	curl_setopt($ch, CURLOPT_URL,            $url );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
	//curl_setopt($ch, CURLOPT_HEADER, 1 );
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Authorization: Bearer ' . $token)); 
    $ret = curl_exec($ch);
    if($ret) {
        $resp =  json_decode($ret,true);

        if($suffix == "") {
          $resp = array_filter($resp['items'], function($dev) { return strpos($dev['name'], "Temp Sensor") > 10; });
          $result = [];
          foreach($resp as $r) {
             $result[$r['deviceId']] = $r['label'];
          }
          return ($result);
        } else {
            $main = $resp['components']['main'];
            $h = $main['relativeHumidityMeasurement']['humidity'];
            $t = $main['temperatureMeasurement']['temperature'];
            return [ 'h' => $h['value'], 't' => $t['value'], 'ts' => max(strtotime($h['timestamp']),strtotime($t['timestamp'])) ];
        }
    }
}

header("content-type: text/xml");
?><?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
<channel>
  <title>House temperature</title>
  <link>https://rss.mkagawa.com</link>
  <pubDate><?=date("r")?></pubDate>
  <description>House climate status</description>
<image>
<url>face.jpg</url>
<title>face.jpg</title>
<link>face.jpg</link>
</image>
<?php foreach($items as $n=>$i) {?>
  <item>
  <title><?=$n?> = <?=$i[0]?></title>
    <link>https://rss.mkagawa.com/xml/xml_rss.asp</link>
    <description><?=$i[1]?></description>
    <pubDate><?=date('r')?></pubDate>
  </item>
<?php } ?>
</channel>
</rss>
