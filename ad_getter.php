#!/usr/bin/php

<?php
	$ad_setter_url = "165a7.v.fwmrm.net";
	$xml_req_template_file = "ad_request_template.txt";
	$html_req_template_file = "html_template.txt";

	function get_xml($video_url)
	{
		global $ad_setter_url;
		global $xml_req_template_file;
		global $html_req_template_file;


		$xml_req = file_get_contents($xml_req_template_file);

		if(strlen($xml_req) == 0)
		{
			echo "Error in reading xml template from" . $xml_req_template_file ."\n";
			exit(1);
		}
		$xml_req = str_replace("__VIDEO_URL__", $video_url, $xml_req);


		$html_req = file_get_contents($html_req_template_file);

		if(strlen($html_req) == 0)
		{
			echo "Error in reading xml template from" . $html_req_template_file ."\n";
			exit(1);
		}

		$html_req = str_replace("__CONTENT_LENGTH__", strlen($xml_req), $html_req);

		$html_req .= $xml_req;

		$sock = fsockopen($ad_setter_url, 80);

		if(!$sock)
		{
			echo "Error in connection to url" . $ad_setter_url . "\n";
			exit(1);
		}

		fwrite($sock, $html_req);

		$responce = "";

		while(!feof($sock))
			$responce .= fread($sock, 1);


		$pos = strpos($responce, "\r\n\r\n");
		$responce = substr($responce, $pos+4);

		file_put_contents("/tmp/ad_getting_tmp_file.gz", $responce);
		system("gunzip /tmp/ad_getting_tmp_file.gz");


		$responce = file_get_contents("/tmp/ad_getting_tmp_file");
		system("unlink /tmp/ad_getting_tmp_file");

		return $responce;
	}


	if($argc != 3)
	{
		echo "Usage: ".$argv[0]." video_url file_name\n";
		exit(3);
	}

	$responce = get_xml($argv[1]);

	

	$adResponse = new SimpleXMLElement($responce);

	if(!$adResponse)
	{
		echo "xml parsing error\n";
		exit(2);
	}

	foreach($adResponse -> ads -> ad as $ad)
	{
		$url = $ad -> creatives -> creative -> creativeRenditions -> creativeRendition -> asset['url'];
		if(strlen($url))
		{
			system("wget $url --output-document=$argv[2]");
			break;
		}
	}
?>
