<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php tags.test.php [account-name] [access-token]\n");


$hr = new HighriseAPI();
$hr->debug = false;
$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

$people = $hr->findPeopleBySearchTerm("Personality Changer");
foreach($people as $p)
	$p->delete();


$person = new HighrisePerson($hr);
$person->setFirstName("Personality");
$person->setLastName("Changer");
$person->addEmailAddress("personalityc@gmail.com");
$person->save();
print "Person ID is: " . $person->getId() . "\n";
$person->addEmailAddress("personalitychanger@hotmail.com");
$person->save();
print "Person ID after save is: " . $person->getId() . "\n";
print_r($person);

$people = $hr->findPeopleBySearchTerm("Personality Changer");
print_r($people);

$person->delete();
