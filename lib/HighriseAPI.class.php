<?php

	/*
		* http://developer.37signals.com/highrise/people
		* Add Tags to Person
		* findPeopleByTagName
		* Get Company Name, etc proxy
		* Add Tags to Person
	*/
	
	class HighriseAPI
	{
		public $account;
		public $token;
		protected $curl;
		public $debug;
		
		public function __construct()
		{
			$this->curl = curl_init();
			curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);

	    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
			// curl_setopt($curl,CURLOPT_POST,true);
			curl_setopt($this->curl,CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($this->curl,CURLOPT_SSL_VERIFYHOST,0);	
		}
	
		public function setAccount($account)
		{
			$this->account = $account;
		}
		
		public function setToken($token)
		{
			$this->token = $token;
			curl_setopt($this->curl,CURLOPT_USERPWD,$this->token.':x');
		}

		protected function postDataWithVerb($path, $request_body, $verb = "POST")
		{

			$url = "https://" . $this->account . ".highrisehq.com" . $path;

			if ($this->debug)
				print "postDataWithVerb $url ============================\n";

			
			curl_setopt($this->curl, CURLOPT_URL,$url);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request_body);
			if ($this->debug == true)
				curl_setopt($this->curl, CURLOPT_VERBOSE, true);
				
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		  curl_setopt($this->curl, CURLOPT_USERPWD,$this->token.':x');
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,true);
			
							
			if ($verb != "POST")
			  curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $verb);
 

			$ret = curl_exec($this->curl);
			
			if ($this->debug == true)
				print "Begin Request Body ============================\n" . $request_body . "End Request Body ==============================\n";
			curl_setopt($this->curl,CURLOPT_HTTPGET, true);
			
			return $ret;
		}
		
		protected function getURL($path)
		{
			curl_setopt($this->curl,CURLOPT_URL,"https://" . $this->account . ".highrisehq.com" . $path);
			return curl_exec($this->curl);
		}
		
		protected function getLastReturnStatus()
		{
			return curl_getinfo($this->curl, CURLINFO_HTTP_CODE); 
		}
		
		protected function getXMLObjectForUrl($url)
		{
			$xml = $this->getURL($url);
			$xml_object = simplexml_load_string($xml);
			return $xml_object;
		}
		
		protected function checkForErrors($type, $expected_status_code = 200)
		{
			if ($this->getLastReturnStatus() != $expected_status_code)
			{
				switch($this->getLastReturnStatus())
				{
					case 404:
						throw new Exception("$type not found");
						break;
					case 403:
						throw new Exception("Access denied to $type resource");
						break;
					case 507:
						throw new Exception("Cannot create $type: Insufficient storage in your Highrise Account");
						break;
					
					default:
						throw new Exception("API for $type returned Status Code: " . $this->getLastReturnStatus() . " Expected Code: $expected_status_code");
						break;
				}				
			}
		}
		
		public function findPersonById($id)
		{
			$xml = $this->getURL("/people/$id.xml");
			
			$this->checkForErrors("Person");
			
			
			$xml_object = simplexml_load_string($xml);
			
			$person = new HighrisePerson($this);
			$person->loadFromXMLObject($xml_object);
			return $person;
		}
		
		public function findAllPeople()
		{
			return $this->parsePeopleListing("/people.xml");	
		}
		
		public function findPeopleByTitle($title)
		{
			$url = "/people.xml?title=" . urlencode($title);
			
			$people = $this->parsePeopleListing($url);
			return $people;
		}

		public function findPeopleByTagId($tag_id)
		{
			$url = "/people.xml?tag_id=" . urlencode($tag_id);
			
			$people = $this->parsePeopleListing($url);
			return $people;
		}
		
		public function findPeopleByCompanyId($company_id)
		{
			$url = "/companies/" . urlencode($company_id) . "/people.xml";
			$people = $this->parsePeopleListing($url);
			return $people;
		}

		public function findPeopleBySearchTerm($search_term)
		{
			$url = "/people/search.xml?term=" . urlencode($search_term);
			$people = $this->parsePeopleListing($url, 25);
			return $people;
		}
		
		public function findPeopleBySearchCriteria($search_criteria)
		{
			$url = "/people/search.xml";
			
			$sep = "?";
			foreach($search_criteria as $criteria=>$value)
			{
				$url .= $sep . "criteria[" . urlencode($criteria) . "]=" . urlencode($value);
				$sep = "&";
			}
			
			$people = $this->parsePeopleListing($url, 25);
			return $people;
		}
		
		public function findPeoplSinceTime($time)
		{
			$url = "/people/search.xml?since=" . urlencode($time);
			$people = $this->parsePeopleListing($url);
			return $people;
		}
		public function parsePeopleListing($url, $paging_results = 500)
		{
			if (strstr($url, "?"))
				$sep = "&";
			else
				$sep = "?";
				
			$offset = 0;
			$return = array();
			while(true) // pagination
			{
				$xml_url = $url . $sep . "n=$offset";
				// print $xml_url;
				$xml = $this->getUrl($xml_url);
				$this->checkForErrors("People");
				$xml_object = simplexml_load_string($xml);

				foreach($xml_object->person as $xml_person)
				{
					// print_r($xml_person);
					$person = new HighrisePerson($this);
					$person->loadFromXMLObject($xml_person);
					$return[] = $person;
				}
				
				if (count($xml_object) != $paging_results)
					break;
				
				$offset += $paging_results;
			}
			
			return $return;
		}
		
	}
	
	class HighriseTag
	{
		public $id;
		public $name;
		
		public function __construct($id = null, $name = null)
		{
			$this->setId($id);
			$this->setName($name);
		}
		
		public function toXML()
		{
			$xml  = "<tag>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<name>' . $this->getName() . "</name>\n";
			$xml .= "</tag>\n";
			return $xml;
		}
		
		
		public function __toString()
		{
			return $this->name;
		}
			
		public function setName($name)
		{
		  $this->name = (string)$name;
		}

		public function getName()
		{
		  return $this->name;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}

		
	}
	
	class HighriseEmailAddress 
	{
		public $id;
		public $address;
		public $location;
		
		public function __construct($id = null, $address = null, $location = null)
		{
			$this->setId($id);
			$this->setAddress($address);
			$this->setLocation($location);			
		}
		
		public function toXML()
		{
			$xml  = "<email-address>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<address>' . $this->getAddress() . "</address>\n";
			$xml .= '<location>' . $this->getLocation() . "</location>\n";
			$xml .= "</email-address>\n";
			return $xml;
		}
		
		public function __toString()
		{
			return $this->getAddress();
		}
		
		public function setAddress($address)
		{
		  $this->address = (string)$address;
		}

		public function getAddress()
		{
		  return $this->address;
		}
	
		public function setLocation($location)
		{
			$valid_locations = array("Work", "Home", "Other");
			$location = ucwords(strtolower($location));
			if ($location != null && !in_array($location, $valid_locations))
				throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));
				
		  $this->location = (string)$location;
		}

		public function getLocation()
		{
		  return $this->location;
		}

		
		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}	
	}
	
	
	class HighriseInstantMessenger
	{
		public $id;
		private $location;
		private $protocol;
		public $address;
		
		public function __construct($id = null, $protocol = null, $address = null, $location = null)
		{
			$this->setId($id);
			$this->setProtocol($protocol);
			$this->setAddress($address);		
			$this->setLocation($location);				
		}
		
		public function toXML()
		{
			$xml  = "<instant-messenger>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<protocol>' . $this->getProtocol() . "</protocol>\n";
			$xml .= '<location>' . $this->getLocation() . "</location>\n";
			$xml .= '<address>' . $this->getAddress() . "</address>\n";
			$xml .= "</instant-messenger>\n";
			return $xml;
		}
		
		
		
		public function __toString()
		{
			return $this->getProtocol() . ":" . $this->getAddress();
		}
		
		public function setAddress($address)
		{
		  $this->address = (string)$address;
		}

		public function getAddress()
		{
		  return $this->address;
		}

		public function setProtocol($protocol)
		{
			$valid_protocols = array("AIM", "MSN", "ICQ", "Jabber", "Yahoo", "Skype", "QQ", "Sametime", "Gadu-Gadu", "Google Talk", "Other");
			if ($protocol != null && !in_array($protocol, $valid_protocols))
				throw new Exception("$protocol is not a valid protocol. Available protocols: " . implode(", ", $valid_protocols));
			
		  $this->protocol = (string)$protocol;
		}

		public function getProtocol()
		{
		  return $this->protocol;
		}

		public function setLocation($location)
		{
			$valid_locations = array("Work", "Personal", "Other");
			$location = ucwords(strtolower($location));
			if ($location != null && !in_array($location, $valid_locations))
				throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));
			
		  $this->location = (string)$location;
		}

		public function getLocation()
		{
		  return $this->location;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}

		
	}
	class HighriseAddress
	{
		public $id;
		public $city;
		public $country;
		public $location;
		public $state;
		public $street;
		public $zip;
		
		public function toXML()
		{
			$xml  = "<address>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<city>' . $this->getCity() . "</city>\n";
			$xml .= '<country>' . $this->getCountry() . "</country>\n";
			$xml .= '<location>' . $this->getLocation() . "</location>\n";
			$xml .= '<state>' . $this->getState() . "</state>\n";
			$xml .= '<street>' . $this->getStreet() . "</street>\n";
			$xml .= '<zip>' . $this->getZip() . "</zip>\n";
			$xml .= "</address>\n";
			return $xml;
		}
		
		public function setZip($zip)
		{
		  $this->zip = (string)$zip;
		}

		public function getZip()
		{
		  return $this->zip;
		}
		
		public function setStreet($street)
		{
		  $this->street = (string)$street;
		}

		public function getStreet()
		{
		  return $this->street;
		}

		
		public function setState($state)
		{
		  $this->state = (string)$state;
		}

		public function getState()
		{
		  return $this->state;
		}

		
		public function setLocation($location)
		{
			$valid_locations = array("Work", "Home", "Other");
			$location = ucwords(strtolower($location));
			if ($location != null && !in_array($location, $valid_locations))
				throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));
			
		  $this->location = (string)$location;
		}

		public function getLocation()
		{
		  return $this->location;
		}

		
		public function setCountry($country)
		{
		  $this->country = (string)$country;
		}

		public function getCountry()
		{
		  return $this->country;
		}

		
		public function setCity($city)
		{
		  $this->city = (string)$city;
		}

		public function getCity()
		{
		  return $this->city;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}	
	}
	
	class HighrisePhoneNumber
	{
		public $id;
		public $number;
		public $location;
	
		public function __construct($id = null, $number = null, $location = null)
		{
			$this->setId($id);
			$this->setNumber($number);
			$this->setLocation($location);			
		}
		
		public function toXML()
		{
			$xml  = "<phone-number>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<number>' . $this->getNumber() . "</number>\n";
			$xml .= '<location>' . $this->getLocation() . "</location>\n";
			$xml .= "</phone-number>\n";
			return $xml;
		}
		
		
		public function setLocation ($location)
		{
			$valid_locations = array("Work", "Mobile", "Fax", "Pager", "Home", "Skype", "Other");
			$location = ucwords(strtolower($location));
			if ($location != null && !in_array($location, $valid_locations))
				throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));
			
		  $this->location = (string)$location;
		}

		public function getLocation ()
		{
		  return $this->location;
		}

		
		public function setNumber ($number)
		{
		  $this->number = (string)$number;
		}

		public function getNumber ()
		{
		  return $this->number;
		}

		
		public function setId ($id)
		{
		  $this->id = (string)$id;
		}

		public function getId ()
		{
		  return $this->id;
		}

		public function __toString()
		{
			return $this->number;
		}
		
	}
	
	class HighriseWebAddress
	{
		public $id;
		public $location;
		public $url;
		
		public function toXML()
		{
			$xml  = "<web-address>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<location>' . $this->getLocation() . "</location>\n";
			$xml .= '<url>' . $this->getUrl() . "</url>\n";
			$xml .= "</web-address>\n";
			return $xml;
		}
		
		public function __construct($id = null, $url = null, $location = null)
		{
			$this->setId($id);
			$this->setUrl($url);
			$this->setLocation($location);			
		}
		
		public function setUrl($url)
		{
		  $this->url = (string)$url;
		}

		public function getUrl()
		{
		  return $this->url;
		}

		
		public function setLocation($location)
		{
			$valid_locations = array("Work", "Personal", "Other");
			$location = ucwords(strtolower($location));
			if ($location != null && !in_array($location, $valid_locations))
				throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));
			
		  $this->location = (string)$location;
		}

		public function getLocation()
		{
		  return $this->location;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}

		
		
	}
	
	class HighriseTwitterAccount
	{
		public $id;
		public $location;
		public $username;
		
		
		public function __construct($id = null, $username = null, $location = null)
		{
			$this->setId($id);
			$this->setUsername($username);
			$this->setLocation($location);			
		}
		
		public function toXML()
		{
			$xml  = "<twitter-account>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			$xml .= '<username>' . $this->getUsername() . "</username>\n";
			$xml .= '<location>' . $this->getLocation() . "</location>\n";
			$xml .= "</twitter-account>\n";
			return $xml;
		}
		
		public function setUrl($url)
		{
		 	throw new Exception("Cannot set URLs, change Username instead");
		}

		public function getUrl()
		{
			return "http://twitter.com/" . $this->getUsername();
		}
		
		public function setUsername($username)
		{
		  $this->username = (string)$username;
			$this->url = $this->getUrl();
		}

		public function getUsername()
		{
		  return $this->username;
		}

		
		public function setLocation($location)
		{
			$valid_locations = array("Business", "Personal", "Other");
			$location = ucwords(strtolower($location));
			if ($location != null && !in_array($location, $valid_locations))
				throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));
			
		  $this->location = (string)$location;
		}

		public function getLocation()
		{
		  return $this->location;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}

		
	}
	
	class HighrisePerson extends HighriseAPI
	{
		
		public $id;
		public $title;
		public $first_name;
		public $last_name;
		public $background;
		public $company_name;
		public $created_at;
		public $updated_at;
		public $company_id;
		
		// TODO: public $owner_id;
		// TODO: public $group_id;
		public $author_id;
		public $contact_details;
		public $visible_to;
		
		// contact-data
		
		public $email_addresses;
		public $phone_numbers;
		public $addresses;
		public $web_addresses;
		public $instant_messengers;
		public $twitter_accounts;


		public function delete()
		{
			$this->postDataWithVerb("/people/" . $this->getId() . ".xml", "", "DELETE");
			$this->checkForErrors("Person", 200);	
		}
		
		public function save()
		{
			$person_xml = $this->toXML(false);
			if ($this->getId() != null)
			{
				$this->postDataWithVerb("/people/" . $this->getId() . ".xml", $person_xml, "PUT");
				$this->checkForErrors("Person");
			}
			else
			{
				$this->postDataWithVerb("/people.xml", $person_xml, "POST");
				$this->checkForErrors("Person", 201);
			}
		}
		
		public function toXML($with_id = true)
		{
			$xml[] = "<person>";
			
			// TODO: Update company_id
			// TODO: Get Company Id
			$fields = array("title", "first_name", "last_name", "background", "visible_to");
			
			
			if ($this->getId() != null)
				$xml[] = '<id type="integer">' . $this->getId() . '</id>';
			
			$optional_fields = array("company_name");
				
			foreach($fields as $field)
			{
				$xml_field_name = str_replace("_", "-", $field);
				$xml[] = "\t<" . $xml_field_name . ">" . $this->$field . "</" . $xml_field_name . ">";
			}
			
			
			foreach($optional_fields as $field)
			{
				if ($this->$field != "")
				{
					$xml_field_name = str_replace("_", "-", $field);
					$xml[] = "\t<" . $xml_field_name . ">" . $this->$field . "</" . $xml_field_name . ">";
				}
			}
			
			$xml[] = "<contact-data>";
			
			foreach(array("email_address", "instant_messenger", "twitter_account", "web_address", "address", "phone_number") as $contact_node)
			{
				if (!strstr($contact_node, "address"))
					$contact_node_plural = $contact_node . "s";		
				else
					$contact_node_plural = $contact_node . "es";
				
				
				if (count($this->$contact_node_plural) > 0)
				{
					$xml[] = "<" . str_replace("_", "-", $contact_node_plural) . ">";
					foreach($this->$contact_node_plural as $items)
					{
						$xml[] = $items->toXML();
					}
					$xml[] = "</" . str_replace("_", "-", $contact_node_plural) . ">";
				}
			}
			$xml[] = "</contact-data>";
			
			$xml[] = "</person>";

			return implode("\n", $xml);		
		}
		
		public function loadFromXMLObject($xml_obj)
		{
			// print_r($xml_obj);
			$this->setId($xml_obj->id);
			$this->setFirstName($xml_obj->{'first-name'});
			$this->setLastName($xml_obj->{'last-name'});
			$this->setTitle($xml_obj->{'title'});
			$this->setAuthorId($xml_obj->{'author-id'});
			$this->setBackground($xml_obj->{'background'});
			$this->setVisibleTo($xml_obj->{'visible-to'});	
			$this->setCreatedAt($xml_obj->{'created-at'});
			$this->setUpdatedAt($xml_obj->{'updated-at'});
			$this->setCompanyId($xml_obj->{'company-id'});
			
			$this->loadContactDataFromXMLObject($xml_obj->{'contact-data'});
			$this->loadTagsFromXMLObject($xml_obj->{'tags'});	
		}
		
		public function loadTagsFromXMLObject($xml_obj)
		{
			$this->tags = array();
			if (count($xml_obj->{'tag'}) > 0)
			{
				foreach($xml_obj->{'tag'} as $value)
				{
					$tag = new HighriseTag($value->{'id'}, $value->{'name'});
					$this->tags[] = $tag;
				}
			}
		}
		
		public function loadContactDataFromXMLObject($xml_obj)
		{
			$this->phone_numbers = array();
			$this->email_addresses = array();
			$this->web_addresses = array();
			$this->addresses = array();
			$this->instant_messengers = array();
			
			if (isset($xml_obj->{'phone-numbers'}))
			{
				foreach($xml_obj->{'phone-numbers'}->{'phone-number'} as $value)
				{
					$number = new HighrisePhoneNumber($value->{'id'}, $value->{'number'}, $value->{'location'});
					$this->phone_numbers[] = $number;
				}				
			}
			

			if (isset($xml_obj->{'email-addresses'}))
			{			
				foreach($xml_obj->{'email-addresses'}->{'email-address'} as $value)
				{
					$email_address = new HighriseEmailAddress($value->{'id'}, $value->{'address'}, $value->{'location'});
					$this->email_addresses[] = $email_address;
				}
			}
			
			if (isset($xml_obj->{'instant-messengers'}))
			{
				foreach($xml_obj->{'instant-messengers'}->{'instant-messenger'} as $value)
				{
					$instant_messenger = new HighriseInstantMessenger($value->{'id'}, $value->{'protocol'}, $value->{'address'}, $value->{'location'});
					$this->instant_messengers[] = $instant_messenger;
				}
			}
			
			if (isset($xml_obj->{'web-addresses'}))
			{
				foreach($xml_obj->{'web-addresses'}->{'web-address'} as $value)
				{
					$web_address = new HighriseWebAddress($value->{'id'}, $value->{'url'}, $value->{'location'});
					$this->web_addresses[] = $web_address;
				}
			}
			
			if (isset($xml_obj->{'twitter-accounts'}))
			{
				foreach($xml_obj->{'twitter-accounts'}->{'twitter-account'} as $value)
				{
					$twitter_account = new HighriseTwitterAccount($value->{'id'}, $value->{'username'}, $value->{'location'});
					$this->twitter_accounts[] = $twitter_account;
				}
			}
			
			if (isset($xml_obj->{'addresses'}))
			{
				foreach($xml_obj->{'addresses'}->{'address'} as $value)
				{
					$address = new HighriseAddress();

					$address->setId($value->id);
					$address->setCity($value->city);
					$address->setCountry($value->country);
					$address->setLocation($value->location);
					$address->setState($value->state);
					$address->setStreet($value->street);
					$address->setZip($value->zip);

					$this->addresses[] = $address;
				}			
			}
		}
		
		public function addEmailAddress($address, $location = "Home")
		{
			$item = new HighriseEmailAddress();
			$item->setAddress($address);
			$item->setLocation($location);
			
			$this->email_addresses[] = $item;
		}
		
		public function addPhoneNumber($number, $location = "Home")
		{
			$item = new HighrisePhoneNumber();
			$item->setNumber($number);
			$item->setLocation($location);
			
			$this->phone_numbers[] = $item;
		}

		public function addWebAddress($url, $location = "Work")
		{
			$item = new HighriseWebAddress();
			$item->setUrl($url);
			$item->setLocation($location);
			
			$this->web_addresses[] = $item;
		}


		public function addInstantMessenger($protocol, $address, $location = "Personal")
		{
			$item = new HighriseInstantMessenger();
			$item->setProtocol($protocol);
			$item->setAddress($address);
			$item->setLocation($location);
				
			$this->instant_messengers[] = $item;
		}

		public function addTwitterAccount($username, $location = "Personal")
		{
			$item = new HighriseTwitterAccount();
			$item->setUsername($username);
			$item->setLocation($location);
			
			$this->twitter_accounts[] = $item;
		}

		public function setCompanyId($company_id)
		{
		  $this->company_id = (string)$company_id;
		}

		public function getCompanyId()
		{
		  return $this->company_id;
		}
		
		public function setVisibleTo ($visible_to)
		{
			$valid_permissions = array("Everyone", "Owner");
			$visible_to = ucwords(strtolower($visible_to));
			if ($visible_to != null && !in_array($visible_to, $valid_permissions))
				throw new Exception("$visible_to is not a valid visibility permission. Available visibility permissions: " . implode(", ", $valid_permissions));
			
		  $this->visible_to = (string)$visible_to;
		}

		public function getVisibleTo ()
		{
		  return $this->visible_to;
		}

		
		public function setAuthorId ($author_id)
		{
		  $this->author_id = (string)$author_id;
		}

		public function getAuthorId ()
		{
		  return $this->author_id;
		}
	
		public function setUpdatedAt ($updated_at)
		{
		  $this->updated_at = (string)$updated_at;
		}

		public function getUpdatedAt ()
		{
		  return $this->updated_at;
		}

		
		public function setCreatedAt ($created_at)
		{
		  $this->created_at = (string)$created_at;
		}

		public function getCreatedAt ()
		{
		  return $this->created_at;
		}

		
		public function setCompanyName ($company_name)
		{
		  $this->company_name = (string)$company_name;
		}

		public function getCompanyName ()
		{
		  return $this->company_name;
		}

		
		public function setBackground ($background)
		{
		  $this->background = (string)$background;
		}

		public function getBackground ()
		{
		  return $this->background;
		}

		
		public function setLastName ($last_name)
		{
		  $this->last_name = (string)$last_name;
		}

		public function getLastName ()
		{
		  return $this->last_name;
		}

		
		public function setFirstName ($first_name)
		{
		  $this->first_name = (string)$first_name;
		}

		public function getFirstName ()
		{
		  return $this->first_name;
		}

		public function setTitle ($title)
		{
		  $this->title = (string)$title;
		}

		public function getTitle ()
		{
		  return $this->title;
		}

		
		public function setId ($id)
		{
		  $this->id = (string)$id;
		}

		public function getId ()
		{
		  return $this->id;
		}

		public function __construct(HighriseAPI $highrise)
		{
			$this->account = $highrise->account;
			$this->token = $highrise->token;
			$this->setVisibleTo("Everyone");
			$this->debug = $highrise->debug;
			$this->curl = curl_init();		
		}
	}
	
	
	