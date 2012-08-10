<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php tags.test.php [account-name] [access-token]\n");


$highrise = new HighriseAPI();
$highrise->debug = false;
$highrise->setAccount($argv[1]);
$highrise->setToken($argv[2]);

$people = $highrise->findPeopleBySearchTerm("Personality Changer");
foreach($people as $p)
	$p->delete();


$person = new HighrisePerson($highrise);
$person->setFirstName("Personality");
$person->setLastName("Changer");
$person->addEmailAddress("personalityc@gmail.com");

// Getting global subject fields
$subject_fields = $highrise->getSubjectFields();

// $key corresponds to the field key and $value to it label
foreach ($subject_fields as $key => $value)
{
  // If I found the label I wanted to edit
  if ($value == "MyCustomField")
    {
      // Setting the new value for the user custom field
      $person->setCustomField($key, 'MyNewValue');
    }
}

$person->save();
print "Person ID is: " . $person->getId() . "\n";
$person->addEmailAddress("personalitychanger@hotmail.com");
$person->save();
print "Person ID after save is: " . $person->getId() . "\n";
print_r($person);

$people = $highrise->findPeopleBySearchTerm("Personality Changer");
print_r($people);

$person->delete();
