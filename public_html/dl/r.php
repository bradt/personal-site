<?php 
set_time_limit(0);

$data = file_get_contents('filesizes.txt');
$data = split("\n", $data);

//$_GET['img'] = 'http://cdn.nhl.com/images/captcha/02/q1BbMtMgvmjAt6ERTuGOG200.gif';

$size = remotesize($_GET['img']);

foreach ($data as $line) {
	$line = split("[\t]+", $line);
	if ($size == $line[1]) {
		echo $line[0];
		exit;
	}
}

function remotesize($url) {
	preg_match('/http\:\/\/([^\/]+)(.*)$/', $url, $matches);
	$dl_server = $matches[1];
	$dl_url = $matches[2];

	$fp = fsockopen($dl_server, 80, $errno, $errstr, 30);
	if (!$fp) {
		 echo "$errstr ($errno)<br />\n";
	} else {
		 $out = "GET $dl_url HTTP/1.1\r\n";
		 $out .= "Host: $dl_server\r\n";
		 $out .= "Connection: Close\r\n\r\n";

		 $response = '';
		 fwrite($fp, $out);
		 while (!feof($fp)) {
				 $response .= fgets($fp, 128);
		 }
		 fclose($fp);

		$response = split("\r\n\r\n", $response);
		$headers = $response[0];
		$data = $response[1];

		preg_match('/Content-Length\: ?([0-9]+)/', $headers, $matches);
	
		return $matches[1];
	}
}
?>