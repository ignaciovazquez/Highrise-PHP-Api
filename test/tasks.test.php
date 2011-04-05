<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php tasks.test.php [account-name] [access-token]\n");

$hr = new HighriseAPI();
$hr->debug = false;
$hr->setAccount($argv[1]);
$hr->setToken($argv[2]);

print "Creating Task...\n";
$task = new HighriseTask($hr);
$task->setBody("Task Body");
$task->setPublic(false);
$task->setFrame("Tomorrow");
$task->save();


print "Updating Task...\n";
$task->setBody("Task Body2");
$task->setPublic(true);
$task->save();

print "Creating Task...\n";
$task = new HighriseTask($hr);
$task->setBody("This will be completed");
$task->setFrame("today");
$task->save();

print "Completing Task ID: " . $task->getId() . "\n";
$task->complete();
print_r($task);

print "Finding Completed Tasks...\n";
$completed_tasks = $hr->findCompletedTasks();
foreach($completed_tasks as $completed_task)
{
	if ($completed_task->getId() == $task->getId())
	{
		$found_completed = true;
		$completed_task->delete();
	}
}

if (!isset($found_completed))
	throw new Exception("Couldn't find the completed task");
	

print "Finding Upcoming Tasks...\n";
$tasks = $hr->findUpcomingTasks();
print_r($tasks);

// For this to work you need at least two users in the system
print "Assigning task to user";
$assigned_task = new HighriseTask($hr);
$assigned_task->setBody("Assigned Task");
$assigned_task->setFrame("today");
$users = $hr->findAllUsers();
$me = $hr->findMe();
foreach($users as $user)
{
	if ($user->getId() != $me->getId())
		$assigned_user = $user;
}

$assigned_task->assignToUser($assigned_user);
$assigned_task->save();

print "Finding all Assigned Tasks...";
$assigned_tasks = $hr->findAssignedTasks();
print_r($assigned_tasks);
foreach($assigned_tasks as $a_task)
{
	if ($a_task->getId() == $assigned_task->getId())
	{
		$found_assigned = true;
		$a_task->delete(); 
	}
}

if (!isset($found_assigned))
	throw new Exception("Couldn't find the assigned task");

$tasks = $hr->findUpcomingTasks();

foreach($tasks as $task)
{
	if ($task->body == "Task Body2")
	{
		$found_one = true;
		$task->delete();
		
	}
}

if (!isset($found_one))
	throw new Exception("Couldn't find the right task");
