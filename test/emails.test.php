<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php emails.test.php [account-name] [access-token]\n");

$hr = new HighriseAPI();
$hr->debug = false;
$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

$people = $hr->findPeopleBySearchTerm("Person Test");

$person = $people[0];
$emails = $person->getEmails();
foreach($emails as $email)
{
	print_r($email);
	print $email->toXML();
}

print $person->getId();
// Create new note

$new_email = new HighriseEmail($hr);
$new_email->debug = false;
$new_email->setSubjectType("Party");
$new_email->setSubjectId($person->getId());
$new_email->setTitle("Test Email");
$new_email->setBody("Test");
$new_email->save();

print "New email ID: " . $new_email->getId() . " Created at: " . $new_email->getCreatedAt() . "\n";

print "Updating email...";
$new_email->setBody("Testi");
$new_email->setTitle("Test Title");
$new_email->save();

$find_new_email = $hr->findEmailByID($new_email->id);
if ($find_new_email->getBody() != $new_email->getBody())
	throw new Exception("Retrieving a note by ID failed");
	
$emails = $person->getEmails();
foreach($emails as $email)
{
	if ($email->getTitle() == "Test Title")
	{
		print "Deleting: " . $email->id . "\n";
		$email->delete();
		$found_one = true;
	}
	
}

if (!isset($found_one))
	throw new Exception("Couldn't find created email");

