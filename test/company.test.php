<?php
require_once("../lib/HighriseAPI.class.php");

if (count($argv) != 3)
	die("Usage: php company.test.php [account-name] [access-token]\n");


$highrise = new HighriseAPI();
$highrise->debug = false;
$highrise->setAccount($argv[1]);
$highrise->setToken($argv[2]);

$companies = $highrise->findCompaniesByName("Test Company");
foreach ($companies as $c) {
  print_r ($c);
}

$companies = $highrise->findAllCompanies();
foreach ($companies as $c) {
  print_r ($c);
}

$company = new HighriseCompany($highrise);
$company->setName("Test Company");
$company->setBackground("API Test");
$company->setVisibleTo("Owner");
$company->addEmailAddress("test@example.com");
$company->addPhoneNumber("8008888888");
$company->save();
print "Company ID is: " . $company->getId() . "\n";
$company->addEmailAddress("test2@example.com");
$company->save();
print "Company ID after save is: " . $company->getId() . "\n";
print_r($company);

$companies = $highrise->findCompaniesBySearchTerm("Test Company");
print_r($companies);

$company->delete();
