<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php HighriseAPI.test.php [account-name] [access-token]\n");

$hr = new HighriseAPI();
$hr->debug = false;

$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

$people = $hr->findAllPeople();

print_r($people);
foreach($people as $person)
{
	foreach($person->phone_numbers as $number)
	{
		print $number . "\n";
	}
}

// Add a new person

	// TODO: Add Address	
	$new_person = new HighrisePerson($hr);

	$new_person->setFirstName("John Test");
	$new_person->setLastName("Doe");
	$new_person->setTitle("Nowhere Man");
	$new_person->setBackground("Some background here");

	$new_person->setCompanyName("Test Corp");

	$new_person->addEmailAddress("johndoe@gmail.com");
	$new_person->addEmailAddress("johndoe@corporation.com", "work");

	$new_person->addPhoneNumber("+1 555-555-5555", "Work");
	$new_person->addPhoneNumber("+1 555-555-1111", "Home");

	$new_person->addTwitterAccount("johndoe");
	$new_person->addTwitterAccount("johndoework", "Business");

	$new_person->addWebAddress("http://john.wordpress.com", "Personal");
	$new_person->addWebAddress("http://corporation.com/~john");

	$new_person->addInstantMessenger("MSN", "johnnydoe@live.com");
	$new_person->addInstantMessenger("AIM", "johndoe@corporation.com", "Work");

	$new_person->save();
	
	$new_person = null;
	
	$people = $hr->findPeopleBySearchTerm("John Test Doe");
	
	print count($people) . " matched search term John Test before deletion.\n";
	
	foreach($people as $person)
	{
		if ($person->getFirstName() != "John Test" || $person->getLastName() != "Doe" || $person->getTitle() != "Nowhere Man" || $person->getBackground() != "Some background here")
		{
			die("INVALID PERSON");
		}

		// TODO: Test Company
		// TODO: Test Address
		
		// Test Email Addresses
			if (count($person->email_addresses) != 2)
				die("Invalid number of email addresses");				
	
			if ($person->email_addresses[0]->getAddress() != "johndoe@gmail.com" || $person->email_addresses[0]->getLocation() != "Home")
				die("Invalid Home email address 1");

			if ($person->email_addresses[1]->getAddress() != "johndoe@corporation.com" || $person->email_addresses[1]->getLocation() != "Work")
				die("Invalid Work email address 2");

		// Test Telephone Numbers
			if (count($person->phone_numbers) != 2)
				die("Invalid number of phone numbers");				
	
			if ($person->phone_numbers[0]->getNumber() != "+1 555-555-5555" || $person->phone_numbers[0]->getLocation() != "Work")
				die("Invalid Work phone number 1");

			if ($person->phone_numbers[1]->getNumber() != "+1 555-555-1111" || $person->phone_numbers[1]->getLocation() != "Home")
				die("Invalid Home phone number 2");

		// Test Twitter Accounts
			if (count($person->twitter_accounts) != 2)
				die("Invalid number of twitter accounts");				

			if ($person->twitter_accounts[0]->getUsername() != "johndoe" || $person->twitter_accounts[0]->getUrl() != "http://twitter.com/johndoe" || $person->twitter_accounts[0]->getLocation() != "Personal")
				die("Invalid Personal Twitter Account");

			if ($person->twitter_accounts[1]->getUsername() != "johndoework" || $person->twitter_accounts[1]->getUrl() != "http://twitter.com/johndoework" || $person->twitter_accounts[1]->getLocation() != "Business")
				die("Invalid Business Twitter Account");

		// Test Web Address
			if (count($person->web_addresses) != 2)
				die("Invalid number of twitter accounts");				

			if ($person->web_addresses[0]->getUrl() != "http://john.wordpress.com" || $person->web_addresses[0]->getLocation() != "Personal")
				die("Invalid Personal Web Address");

			if ($person->web_addresses[1]->getUrl() != "http://corporation.com/~john" || $person->web_addresses[1]->getLocation() != "Work")
				die("Invalid Work Web Address");

		// Delete Person
			$person->delete();
	}
	

	$people = $hr->findPeopleBySearchTerm("John Test Doe");		
	print count($people) . " matched search term John Test after deletion.\n";
	die();
	


$person = $hr->findPersonByID(64751412);



print "Find By Title\n\n";

$people = $hr->findPeopleByTitle("Test CEO");
print_r($people);

print "Find By Company ID\n\n";

$people = $hr->findPeopleByCompanyId(64755398);
print_r($people);

print "Find By Search Term\n\n";

$people = $hr->findPeopleBySearchTerm("Test");
print_r($people);


print "Find By Search Criteria\n\n";

$people = $hr->findPeopleBySearchCriteria(array("email"=>"test@test.com", "phone"=>"111-111-1111"));
print_r($people);

// Update Person

	$person->setLastName("Person Test");
	$person->setBackground("We are overriding the background here");
	$person->save();