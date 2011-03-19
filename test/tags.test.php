<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php tags.test.php [account-name] [access-token]\n");

$hr = new HighriseAPI();
$hr->debug = false;
$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

$people = $hr->findPeopleBySearchTerm("Tag Tagger");
foreach($people as $p)
	$p->delete();

print "Adding a new person...\n";

$person = new HighrisePerson($hr);
$person->setFirstName("Tag");
$person->setLastName("Tagger");
$person->setVisibleTo("Owner");
$person->addTag("tag-1");
$person->addTag("tag-2");
$person->addTag("tag-300");

$person->save();

print "Saved Person:\n";
print_r($person);

print "Adding another tag...\n";
$person->addTag("tag-444");
$person->save();

print "Find People named Tag Tagger:\n";
$people = $hr->findPeopleBySearchTerm("Tag Tagger");
print_r($people);

print "Remove tag-1 from all people named Tag Tagger...\n";
foreach($people as $p)
{
	unset($p->tags['tag-1']);
	$p->save();
}

print "Find People named Tag Tagger:\n";
$people = $hr->findPeopleBySearchTerm("Tag Tagger");
print_r($people);

print "Find all tags...";

$all_tags = $hr->findAllTags();
print_r($all_tags);

print "Cleaning up...\n";
foreach($people as $p)
{
	$p->delete();
}

