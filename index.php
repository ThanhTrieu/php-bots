<?php
require 'Checkbots.php';

// tao thu muc - co quyen ghi du lieu, se ghi log cac bots truy cap vao
// nho thu muc phai co quyen ghi
$dirTemplate = 'requestBlocker/';

$rules = [
	[
    //if > 5 requests in 5 Seconds then Block client 15 Seconds
    'requests' => 5, //5 requests
    'sek' => 5, //5 requests in 5 Seconds
    'blockTime' => 15 // Block client 15 Seconds
	],
	[
    //if > 10 requests in 30 Seconds then Block client 20 Seconds
    'requests' => 10, //10 requests
    'sek' => 30, //10 requests in 30 Seconds
    'blockTime' => 20 // Block client 20 Seconds
	],
	[
    //if > 200 requests in 1 Hour then Block client 10 Minutes
    'requests' => 200, //200 requests
    'sek' => 60 * 60, //200 requests in 1 Hour
    'blockTime' => 60 * 10 // Block client 10 Minutes
	]
];

$bot = new Checkbots($dirTemplate, $rules);

$checkBots = $bot->requestBlocker();
if($checkBots){
	// la bots request qua nhieu lan
	header("Location: test.php");
} else {
	// khong phai bots
	echo "Xu ly tiep cac cong viec khac di";
}