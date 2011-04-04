<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php notes.test.php [account-name] [access-token]\n");

$hr = new HighriseAPI();
$hr->debug = false;
$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

$people = $hr->findPeopleBySearchTerm("Person Test");

$person = $people[0];
$notes = $person->getNotes();
foreach($notes as $note)
{
	print_r($note);
	print $note->toXML();
}

// Create new note

$new_note = new HighriseNote($hr);
$new_note->setSubjectType("Party");
$new_note->setSubjectId($person->getId());
$new_note->setBody("Test");
$new_note->save();

print "New note ID: " . $new_note->getId() . " Created at: " . $new_note->getCreatedAt() . "\n";

print "Updating note...";
$new_note->setBody("Testi");
$new_note->save();

$find_new_note = $hr->findNoteByID($new_note->id);
if ($find_new_note->getBody() != $new_note->getBody())
	throw new Exception("Retrieving a note by ID failed");
	
$notes = $person->getNotes();
foreach($notes as $note)
{
	if ($note->body == "Testi")
	{
		print "Deleting: " . $note->id . "\n";
		$note->delete();
		$found_one = true;
	}
	
}

if (!isset($found_one))
	throw new Exception("Couldn't find created note");

