<?php

define( 'PARSE_SDK_DIR', './Parse/' );
require 'autoload.php';
use Parse\ParseClient;
use Parse\ParsePush;

$rand = rand(1, 10);

// I am sleeping
if (date('H') > 22 || date('H') < 6 || $rand <= 3) {
	return;
}

$options  = array('http' => array('user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36'));
$context  = stream_context_create($options);

$queries = array(
	"Aspley" => '{"sortType":"new-desc","filters":{"excludeNoSalePrice":false,"priceRange":{"minimum":"any","maximum":"450000"},"propertyTypes":["house"],"ex-under-contract":true,"surroundingSuburbs":false},"channel":"buy","pageSize":"30","localities":[{"displayValue":"Aspley, QLD 4034","locality":"Aspley","subdivision":"QLD","postcode":"4034"}]}',

	"Zillmere" => '{"sortType":"new-desc","filters":{"excludeNoSalePrice":false,"priceRange":{"minimum":"any","maximum":"450000"},"propertyTypes":["house"],"ex-under-contract":true,"surroundingSuburbs":false},"channel":"buy","pageSize":"30","localities":[{"displayValue":"Zillmere, QLD 4034","locality":"Zillmere","subdivision":"QLD","postcode":"4034"}]}',

	"Brighton" => '{"sortType":"new-desc","filters":{"excludeNoSalePrice":false,"priceRange":{"minimum":"any","maximum":"450000"},"propertyTypes":["house"],"ex-under-contract":true,"surroundingSuburbs":false},"channel":"buy","pageSize":"30","localities":[{"displayValue":"Brighton, QLD 4034","locality":"Brighton","subdivision":"QLD","postcode":"4017"}]}',
);

foreach ($queries as $key => $query) {
	$json = file_get_contents("https://services.realestate.com.au/services/listings/search?query=".urlencode($query), false, $context);
	$data = json_decode($json, true);

	$results = $data['tieredResults'][0]['results'];

	$username = "voidet";
	$password = "password";
	$hostname = "localhost"; 

	//connection to the database
	$db = new mysqli($hostname, $username, $password, "rea");

	$changed = 0;
	foreach ($results as &$result) {
		$listingId = $result["listingId"];
		$result = $db->query("SELECT rea_id FROM properties WHERE rea_id = '".$listingId."'");
		if ($result->num_rows == 0) {
			$changed++;
			$result = $db->query("INSERT INTO properties (rea_id) VALUES ('".$listingId."')");
		}
	}

	if ($changed) {
		ParseClient::initialize("", "", "");
		$data = array("alert" => "There are ".$changed." new houses in ".$key." for your search");

		// Push to Channels
		$yum = ParsePush::send(array(
		  "channels" => array("aspley"),
		  "data" => $data
		));
	}
}