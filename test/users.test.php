<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php users.test.php [account-name] [access-token]\n");

$hr = new HighriseAPI();
$hr->debug = false;
$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

print "Finding my user...\n";
$user = $hr->findMe();
print_r($user);

print "Finding all users...\n";
$users = $hr->findAllUsers();
print_r($users);

