Highrise-PHP-Api is a project (currently in alpha) to provide easy access to 37signals' Highrise API to PHP Developers.

Documentation is coming soon, please check the test directory for examples.

This PHP Class currently allow CRUD support for People objects only. Support for other object types will be uploaded shortly.

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

Create a new person
------ - --- ------

	$person = new HighrisePerson($highrise);
	$person->setFirstName("Personality");
	$person->setLastName("Changer");
	$person->addEmailAddress("personalityc@gmail.com");
	$person->save();

Find People by Search Term
---- ------ -- ------ ----

	$people = $highrise->findPeopleBySearchTerm("Search Term");
	foreach($people as $p)
		print $person->getFirstName() . "\n";

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

	$assigned_tasks = $hr->findAssignedTasks();
	print_r($assigned_tasks);

