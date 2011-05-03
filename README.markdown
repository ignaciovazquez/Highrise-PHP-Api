Highrise-PHP-Api is a project (currently in alpha) to provide easy access to 37signals' Highrise API to PHP Developers.

Documentation is coming soon, please check the test directory for examples.

This PHP Class currently allows CRUD support for People objects only. Support for other object types will be uploaded shortly.

Please mind the tests are only supposed to run in a blank Highrise account and not on a live one.

Features currently implemented:

* People
* Tasks
* Notes
* Emails
* Tags
* Users


Examples
========

Create a new person and set his address
------ - --- ------ --- --- --- -------

	$person = new HighrisePerson($highrise);
	$person->setFirstName("John");
	$person->setLastName("Doe");
	$person->addEmailAddress("johndoe@gmail.com");
	
	$address = new HighriseAddress();
	$address->setAddress("165 Test St.");
	$address->setCity("Glasgow");
	$address->setCountry("Scotland");
	$address->setZip("GL1");
	$person->addAddress($address);
	
	$person->save();

Find People by Search Term
---- ------ -- ------ ----

	$people = $highrise->findPeopleBySearchTerm("John");
	foreach($people as $p)
		print $person->getFirstName() . "\n";

Print all notes
----- --- -----

	foreach($highrise->findAllPeople() as $person)
	{
		print_r($person->getNotes());
	}
	
Create a new note
------ - --- ----

	$note = new HighriseNote($highrise);
	$note->setSubjectType("Party");
	$note->setSubjectId($person->getId());
	$note->setBody("Test");
	$note->save();
	
	
Add tags
--- ----

	$people = $highrise->findPeopleByTitle("CEO");
	foreach($people as $person)
	{
		$person->addTag("CEO");
		$person->save();
	}

Remove Tags
------ ----

	$people = $highrise->findPeopleByTitle("Ex-CEO");
	foreach($people as $person)
	{
		unset($person->tags['CEO']);
		$person->save();
	}

Find all Tags
---- --- ----

	$all_tags = $highrise->findAllTags();
	print_r($all_tags);

Create Task
------ ----

	$task = new HighriseTask($highrise);
	$task->setBody("Task Body");
	$task->setPublic(false);
	$task->setFrame("Tomorrow");
	$task->save();
	
Assign all upcoming tasks
------ --- -------- -----

	$users = $highrise->findAllUsers();
	$user = $users[0]; // just select the first user
	
	foreach($highrise->findUpcomingTasks() as $task)
	{
		$task->assignToUser($user);
		$task->save();
	}

Find all assigned tasks
---- --- -------- -----

	$assigned_tasks = $highrise->findAssignedTasks();
	print_r($assigned_tasks);

Find all users
---- --- -----

	$users = $highrise->findAllUsers();
	print_r($users);
	
Find current user
---- ------- ----

	$me = $highrise->findMe();



