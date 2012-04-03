<?php

	/*
		* http://developer.37signals.com/highrise/people
		*
		* TODO LIST:
		* Add Tasks support
		* Get comments for Notes / Emails
		* findPeopleByTagName
		* Get Company Name, etc proxy
		* Convenience methods for saving Notes $person->saveNotes() to check if notes were modified, etc.
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
			$this->curl = curl_init();
			
			$url = "https://" . $this->account . ".highrisehq.com" . $path;

			if ($this->debug)
				print "postDataWithVerb $verb $url ============================\n";

			
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
 			else
				curl_setopt($this->curl, CURLOPT_POST, true);
				
			$ret = curl_exec($this->curl);
			
			if ($this->debug == true)
				print "Begin Request Body ============================\n" . $request_body . "End Request Body ==============================\n";
			
			curl_setopt($this->curl,CURLOPT_HTTPGET, true);
			
			return $ret;
		}
		
		protected function getURL($path)
		{
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		  curl_setopt($this->curl, CURLOPT_USERPWD,$this->token.':x');
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,true);

			$url = "https://" . $this->account . ".highrisehq.com" . $path;
	
			if ($this->debug == true)
				curl_setopt($this->curl, CURLOPT_VERBOSE, true);
	
				
			curl_setopt($this->curl,CURLOPT_URL,$url);
			$response = curl_exec($this->curl);

			if ($this->debug == true)
				print "Response: =============\n" . $response . "============\n";
		
			return $response;
			
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
		
		protected function checkForErrors($type, $expected_status_codes = 200)
		{
			if (!is_array($expected_status_codes))
				$expected_status_codes = array($expected_status_codes);
			
			if (!in_array($this->getLastReturnStatus(), $expected_status_codes))
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
						throw new Exception("API for $type returned Status Code: " . $this->getLastReturnStatus() . " Expected Code: " . implode(",", $expected_status_codes));
						break;
				}				
			}
		}
		
		/* Users */
		
		public function findAllUsers()
		{
			$xml = $this->getUrl("/users.xml");
			$this->checkForErrors("User");
			
			$xml_object = simplexml_load_string($xml);
			
			$ret = array();
			foreach($xml_object->user as $xml_user)
			{
				$user = new HighriseUser();
				$user->loadFromXMLObject($xml_user);
				$ret[] = $user;
			}
			
			return $ret;
		}
		
		public function findMe()
		{
			$xml = $this->getUrl("/me.xml");
			$this->checkForErrors("User");
			
			$xml_obj = simplexml_load_string($xml);
			$user = new HighriseUser();
			$user->loadFromXMLObject($xml_obj);
			return $user;
		}
		
		/* Tasks */
		
		public function findCompletedTasks()
		{
			$xml = $this->getUrl("/tasks/completed.xml");
			$this->checkForErrors("Tasks");
			return $this->parseTasks($xml);
		}

		public function findAssignedTasks()
		{
			$xml = $this->getUrl("/tasks/assigned.xml");
			$this->checkForErrors("Tasks");
			return $this->parseTasks($xml);
		}

		
		public function findUpcomingTasks()
		{
			$xml = $this->getUrl("/tasks/upcoming.xml");
			$this->checkForErrors("Tasks");
			return $this->parseTasks($xml);
		}

		private function parseTasks($xml)
		{
			$xml_object = simplexml_load_string($xml);			
			$ret = array();
			foreach($xml_object->task as $xml_task)
			{
				$task = new HighriseTask($this);
				$task->loadFromXMLObject($xml_task);
				$ret[] = $task;
			}

			return $ret;
		
		}
		
		public function findTaskById($id)
		{
			$xml = $this->getURL("/tasks/$id.xml");
			$this->checkForErrors("Task");
			$task_xml = simplexml_load_string($xml);
			$task = new HighriseTask($this);
			$task->loadFromXMLObject($task_xml);
			return $task;
			
		}
		
		/* Notes & Emails */

		public function findEmailById($id)
		{
			$xml = $this->getURL("/emails/$id.xml");
			$this->checkForErrors("Email");
			$email_xml = simplexml_load_string($xml);
			$email = new HighriseEmail($this);
			$email->loadFromXMLObject($email_xml);
			return $email;
		}
				
		public function findNoteById($id)
		{
			$xml = $this->getURL("/notes/$id.xml");
			$this->checkForErrors("Note");
			$note_xml = simplexml_load_string($xml);
			$note = new HighriseNote($this);
			$note->loadFromXMLObject($note_xml);
			return $note;
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
		
		public function findAllTags()
		{
			$xml = $this->getUrl("/tags.xml");
			$this->checkForErrors("Tags");
			
			$xml_object = simplexml_load_string($xml);			
			$ret = array();
			foreach($xml_object->tag as $tag)
			{
				$ret[(string)$tag->name] = new HighriseTag((string)$tag->id, (string)$tag->name);
			}
			
			return $ret;
		}
		
		public function findAllPeople()
		{
			return $this->parsePeopleListing("/people.xml");	
		}
		
		public function findPeopleByTagName($tag_name)
		{
			$tags = $this->findAllTags();
			foreach($tags as $tag)
			{
				if ($tag->name == $tag_name)
					$tag_id = $tag->id;
			}
			
			if (!isset($tag_id))
				throw new Excepcion("Tag $tag_name not found");
			
			return $this->findPeopleByTagId($tag_id);
		}
		
		public function findPeopleByTagId($tag_id)
		{
			$url = "/people.xml?tag_id=" . $tag_id;
			$people = $this->parsePeopleListing($url);
			return $people;	
		}
		
		public function findPeopleByEmail($email)
		{
		 return $this->findPeopleBySearchCriteria(array("email"=>$email));
		}
		
		public function findPeopleByTitle($title)
		{
			$url = "/people.xml?title=" . urlencode($title);
			
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
		
		public function findPeopleSinceTime($time)
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
	
	class HighriseUser
	{
		public $id;
		public $name;
		public $email_address;
		public $token;
		public $dropbox;
		public $created_at;
		public $updated_at;
	
	
		public function loadFromXMLObject($xml_obj)
		{
			$this->setId($xml_obj->{'id'});
			$this->setName($xml_obj->{'name'});
			$this->setEmailAddress($xml_obj->{'email-address'});
			$this->setToken($xml_obj->{'token'});
			$this->setDropbox($xml_obj->{'dropbox'});
			$this->setCreatedAt($xml_obj->{'created-at'});
			$this->setUpdatedAt($xml_obj->{'updated-at'});
				
			return true;
		}
		
		public function setUpdatedAt($updated_at)
		{
		  $this->updated_at = (string)$updated_at;
		}

		public function getUpdatedAt()
		{
		  return $this->updated_at;
		}

		public function setCreatedAt($created_at)
		{
		  $this->created_at = (string)$created_at;
		}

		public function getCreatedAt()
		{
		  return $this->created_at;
		}

		
		public function setDropbox($dropbox)
		{
		  $this->dropbox = (string)$dropbox;
		}

		public function getDropbox()
		{
		  return $this->dropbox;
		}

		
		public function setToken($token)
		{
		  $this->token = (string)$token;
		}

		public function getToken()
		{
		  return $this->token;
		}

		
		public function setEmailAddress($email_address)
		{
		  $this->email_address = (string)$email_address;
		}

		public function getEmailAddress()
		{
		  return $this->email_address;
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
	
	class HighriseTask extends HighriseAPI
	{
		private $highrise;
		public $id;
		public $author_id;
		public $subject_id;
		public $subject_type;
		public $subject_name;
		public $category_id;
		public $body;
		public $frame;
		public $due_at;
		public $alert_at;
		public $created_at;
		public $updated_at;
		public $public;
		public $recording_id;
		public $notify;
		public $owner_id;
		public $deleted;
		
		public function complete()
		{
			$task_xml = $this->toXML();
			$new_task_xml = $this->postDataWithVerb("/tasks/" . $this->getId() . "/complete.xml", "", "POST");
			$this->checkForErrors("Task", 200);	
			$this->loadFromXMLObject(simplexml_load_string($new_task_xml));
			return true;	
		}
		
		public function save()
		{
			if ($this->getFrame() == null)
				throw new Exception("You need to specify a valid time frame to save a task");

			if ($this->id == null) // Create
			{
				$task_xml = $this->toXML();
				$new_task_xml = $this->postDataWithVerb("/tasks.xml", $task_xml, "POST");
				$this->checkForErrors("Task", 201);	
				$this->loadFromXMLObject(simplexml_load_string($new_task_xml));
				return true;
			}
			else
			{
				$task_xml = $this->toXML();
				$new_task_xml = $this->postDataWithVerb("/tasks/" . $this->getId() . ".xml", $task_xml, "PUT");
				$this->checkForErrors("Task", 200);	
				return true;	
			}
		}
		
		public function delete()
		{
			$this->postDataWithVerb("/tasks/" . $this->getId() . ".xml", "", "DELETE");
			$this->checkForErrors("Task", 200);	
			$this->deleted = true;
		}
		
		public function assignToUser(HighriseUser $user)
		{
			$this->setOwnerId($user->getId());
		}
		
		public function setOwnerId($owner_id)
		{
		  $this->owner_id = (string)$owner_id;
		}

		public function getOwnerId()
		{
		  return $this->owner_id;
		}

		
		public function setNotify($notify)
		{
			if ($notify == "true" || $notify == true || $notify == 1)
				$notify = true;
			else
				$notify = false;
				
		  $this->notify = (string)$notify;
		}

		public function getNotify()
		{
		  return $this->notify;
		}

		public function setRecordingId($recording_id)
		{
		  $this->recording_id = (string)$recording_id;
		}

		public function getRecordingId()
		{
		  return $this->recording_id;
		}

		public function setPublic($public)
		{
		  $this->public = (string)$public;
		}

		public function getPublic()
		{
		  return $this->public;
		}

		
		
		public function setUpdatedAt($updated_at)
		{
		  $this->updated_at = (string)$updated_at;
		}

		public function getUpdatedAt()
		{
		  return $this->updated_at;
		}

		
		public function setCreatedAt($created_at)
		{
		  $this->created_at = (string)$created_at;
		}

		public function getCreatedAt()
		{
		  return $this->created_at;
		}

		
		public function setAlertAt($alert_at)
		{
		  $this->alert_at = (string)$alert_at;
		}

		public function getAlertAt()
		{
		  return $this->alert_at;
		}

		
		public function setDueAt($due_at)
		{
		  $this->due_at = (string)$due_at;
		}

		public function getDueAt()
		{
		  return $this->due_at;
		}

		
		
		public function setFrame($subject_type)
		{
			$valid_frames = array("today", "tomorrow", "this_week", "next_week", "later", "overdue");
			$frame = str_replace(" ", "_", strtolower($subject_type));
			
			if ($frame != null && !in_array($frame, $valid_frames))
				throw new Exception("$subject_type is not a valid frame. Available frames: " . implode(", ", $valid_frames));
	
		  $this->frame = (string)$frame;
		}

		public function getFrame()
		{
		  return $this->frame;
		}

		
		public function setBody($body)
		{
		  $this->body = (string)$body;
		}

		public function getBody()
		{
		  return $this->body;
		}

		
		public function setCategoryId($category_id)
		{
		  $this->category_id = (string)$category_id;
		}

		public function getCategoryId()
		{
		  return $this->category_id;
		}

		
		public function setSubjectName($subject_name)
		{
		  $this->subject_name = (string)$subject_name;
		}

		public function getSubjectName()
		{
		  return $this->subject_name;
		}

		public function setSubjectType($subject_type)
		{
			$valid_types = array("Party", "Company", "Deal", "Kase");
			$subject_type = ucwords(strtolower($subject_type));
			if ($subject_type != null && !in_array($subject_type, $valid_types))
				throw new Exception("$subject_type is not a valid subject type. Available subject types: " . implode(", ", $valid_types));
	
		  $this->subject_type = (string)$subject_type;
		}

                public function getSubjectType()
                {
                  return $this->subject_type;
                }
	
		public function setSubjectId($subject_id)
		{
		  $this->subject_id = (string)$subject_id;
		}

		public function getSubjectId()
		{
		  return $this->subject_id;
		}

		
		public function setAuthorId($author_id)
		{
		  $this->author_id = (string)$author_id;
		}

		public function getAuthorId()
		{
		  return $this->author_id;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}

		
		public function toXML()
		{
			$xml  = "<task>\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";

			if ($this->getRecordingId() != null)
			{
				$xml .= '<recording-id>' . $this->getSubjectId() . "</subject-id>\n";
			}
			
			if ($this->getSubjectId() != null)
			{
				$xml .= '<subject-id>' . $this->getSubjectId() . "</subject-id>\n";
				$xml .= '<subject-type>' . $this->getSubjectType() . "</subject-type>\n";
			}
			
			$xml .= '<body>' . $this->getBody() . "</body>\n";
			$xml .= '<frame>' . $this->getFrame() . "</frame>\n";
			
			if ($this->getCategoryId() != null)
				$xml .= '<category-id>' . $this->getCategoryId() . "</category-id>\n";
		
			if ($this->getOwnerId() != null)	
				$xml .= '<owner-id>' . $this->getOwnerId() . "</owner-id>\n";
			
			if ($this->getDueAt() != null)
				$xml .= '<due-at>' . $this->getDueAt() . "</due-at>\n";
			if ($this->getAlertAt() != null)
				$xml .= '<alert-at>' . $this->getAlertAt() . "</alert-at>\n";
			
			if ($this->getPublic() != null)
				$xml .= '<public type="boolean">' . ($this->getPublic() ? "true" : "false") . "</public>\n";
			
			if ($this->getNotify() != null)
				$xml .= '<notify type="boolean">' . ($this->getNotify() ? "true" : "false") . "</notify>\n";
			

			$xml .= "</task>\n";
			return $xml;
		}		
		
		public function loadFromXMLObject($xml_obj)
		{
	
			if ($this->debug)
				print_r($xml_obj);

			$this->setId($xml_obj->{'id'});
			$this->setAuthorId($xml_obj->{'author-id'});
			$this->setSubjectId($xml_obj->{'subject-id'});
			$this->setSubjectType($xml_obj->{'subject-type'});
			$this->setSubjectName($xml_obj->{'subject-name'});
			$this->setCategoryId($xml_obj->{'category-id'});
			$this->setBody($xml_obj->{'body'});
			$this->setFrame($xml_obj->{'frame'});
			$this->setDueAt($xml_obj->{'due-at'});
			$this->setAlertAt($xml_obj->{'alert-at'});
		
			$this->setCreatedAt($xml_obj->{'created-at'});
			$this->setUpdatedAt($xml_obj->{'updated-at'});
			$this->setPublic(($xml_obj->{'public'} == "true"));
			return true;
		}

		public function __construct(HighriseAPI $highrise)
		{
			$this->account = $highrise->account;
			$this->token = $highrise->token;
			$this->debug = $highrise->debug;
			$this->curl = curl_init();		
		}
	}
	
	class HighriseEmail extends HighriseNote
	{
		public $title;
		
		public function setTitle($title)
		{
		  $this->title = (string)$title;
		}

		public function getTitle()
		{
		  return $this->title;
		}

		public function __construct(HighriseAPI $highrise)
		{
			parent::__construct($highrise);
			$this->_note_type = "email";
			$this->_note_url = "/emails";
		}
		
		public function loadFromXMLObject($xml_obj)
		{
			if ($this->debug)
				print_r($xml_obj);

			$this->setId($xml_obj->{'id'});
			$this->setAuthorId($xml_obj->{'author-id'});
			$this->setOwnerId($xml_obj->{'owner-id'});
			$this->setSubjectId($xml_obj->{'subject-id'});
			$this->setSubjectType($xml_obj->{'subject-type'});
			$this->setCreatedAt($xml_obj->{'created-at'});
			$this->setUpdatedAt($xml_obj->{'updated-at'});
			$this->setVisibleTo($xml_obj->{'visible-to'});
			$this->setSubjectName($xml_obj->{'subject-name'});
			$this->setTitle($xml_obj->{'title'});
			$this->setBody($xml_obj->{'body'});

			return true;
		}
	}
	
	class HighriseNote extends HighriseAPI
	{
		
		protected $_note_type;
		protected $_note_url;
		
		public $id;
		public $author_id;
		public $body;
		public $created_at;
		public $owner_id;
		public $subject_id;
		public $subject_type;
		public $updated_at;
		public $visible_to;
		public $subject_name;
		public $deleted;
		
		// public $group_id
		// public $collection_id;
		// public $collection_type;
	
		public function save()
		{
			if ($this->subject_type == null || $this->subject_id == null)
			{
				throw new Exception("Subject Type and Subject ID must be set in order to create a new " . $this->_note_type);
			}

			if ($this->id == null) // Create
			{
				$note_xml = $this->toXML();
				$new_xml = $this->postDataWithVerb($this->_note_url . ".xml", $note_xml, "POST");
				$this->checkForErrors(ucwords($this->_note_type), 201);	
				$this->loadFromXMLObject(simplexml_load_string($new_xml));
				return true;
			}
			else // Update
			{
				$note_xml = $this->toXML();
				$new_xml = $this->postDataWithVerb($this->_note_url . "/" . $this->getId() . ".xml", $note_xml, "PUT");
				$this->checkForErrors(ucwords($this->_note_type), 200);	
				return true;
			}
		}
		
		public function delete()
		{
			$this->postDataWithVerb($this->_note_url . "/" . $this->getId() . ".xml", "", "DELETE");
			$this->checkForErrors(ucwords($this->_note_type), 200);	
			$this->deleted = true;
		}
	
		public function loadFromXMLObject($xml_obj)
		{
			if ($this->debug)
				print_r($xml_obj);

			$this->setId($xml_obj->{'id'});
			$this->setAuthorId($xml_obj->{'author-id'});
			$this->setOwnerId($xml_obj->{'owner-id'});
			$this->setSubjectId($xml_obj->{'subject-id'});
			$this->setSubjectType($xml_obj->{'subject-type'});
			$this->setCreatedAt($xml_obj->{'created-at'});
			$this->setUpdatedAt($xml_obj->{'updated-at'});
			$this->setVisibleTo($xml_obj->{'visible-to'});
			$this->setSubjectName($xml_obj->{'subject-name'});
			$this->setBody($xml_obj->{'body'});
			
			return true;
		}


		public function setSubjectName($subject_name)
		{
		  $this->subject_name = (string)$subject_name;
		}

		public function getSubjectName()
		{
		  return $this->subject_name;
		}
		
		public function setVisibleTo($visible_to)
		{
		  $this->visible_to = (string)$visible_to;
		}

		public function getVisibleTo()
		{
		  return $this->visible_to;
		}

		
		public function setUpdatedAt($updated_at)
		{
		  $this->updated_at = (string)$updated_at;
		}

		public function getUpdatedAt()
		{
		  return $this->updated_at;
		}

		
		public function setSubjectType($subject_type)
		{
			$valid_types = array("Party", "Company", "Deal", "Kase");
			$subject_type = ucwords(strtolower($subject_type));
			if ($subject_type != null && !in_array($subject_type, $valid_types))
				throw new Exception("$subject_type is not a valid subject type. Available subject types: " . implode(", ", $valid_types));
	
		  $this->subject_type = (string)$subject_type;
		}

		public function getSubjectType()
		{
		  return $this->subject_type;
		}

		public function setSubjectId($subject_id)
		{
		  $this->subject_id = (string)$subject_id;
		}

		public function getSubjectId()
		{
		  return $this->subject_id;
		}

		
		public function setOwnerId($owner_id)
		{
		  $this->owner_id = (string)$owner_id;
		}

		public function getOwnerId()
		{
		  return $this->owner_id;
		}

		
		public function setCreatedAt($created_at)
		{
		  $this->created_at = (string)$created_at;
		}

		public function getCreatedAt()
		{
		  return $this->created_at;
		}

		
		public function setBody($body)
		{
		  $this->body = (string)$body;
		}

		public function getBody()
		{
		  return $this->body;
		}

		
		public function setAuthorId($author_id)
		{
		  $this->author_id = (string)$author_id;
		}

		public function getAuthorId()
		{
		  return $this->author_id;
		}

		public function __construct(HighriseAPI $highrise)
		{
			$this->account = $highrise->account;
			$this->token = $highrise->token;
			$this->setVisibleTo("Everyone");
			$this->debug = $highrise->debug;
			$this->curl = curl_init();		
			
			$this->_note_type = "note";
			$this->_note_url = "/notes";
		}
		
		public function toXML()
		{
			$xml  = "<" . $this->_note_type . ">\n";
			if ($this->getId() != null)
				$xml .= '<id type="integer">' . $this->getId() . "</id>\n";
			
			if ($this->author_id)
				$xml .= '<author-id>' . $this->getAuthorId() . "</author-id>\n";
			
			$xml .= '<body>' . $this->getBody() . "</body>\n";
			
			if ($this->owner_id)
				$xml .= '<owner-id>' . $this->getOwnerId() . "</owner-id>\n";
			
			$xml .= '<subject-id>' . $this->getSubjectId() . "</subject-id>\n";
			$xml .= '<subject-type>' . $this->getSubjectType() . "</subject-type>\n";
			$xml .= '<visible-to>' . $this->getVisibleTo() . "</visible-to>\n";
			
			if (isset($this->title)) // Email
				$xml .= '<title>' . $this->getTitle() . "</title>\n";
			
			// $xml .= '<subject-name>' . $this->getSubjectName() . "</subject-name>\n";
			
			$xml .= "</" . $this->_note_type . ">\n";
			return $xml;
		}
		
		public function __toString()
		{
			return $this->id;
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
		
		public function __toString()
		{
			return $this->getFullAddress();
		}

		public function getFullAddress()
		{
			$return = "";
			if ($this->getStreet() != "" && $this->getStreet() != null)
			{
				$return .= $this->getStreet(). ", ";
			}
			
			if ($this->getCity() != "" && $this->getCity() != null)
			{
				$return .= $this->getCity(). ", ";
			}

			if ($this->getState() != "" && $this->getState() != null)
			{
				$return .= $this->getState(). ", ";
			}

			if ($this->getZip() != "" && $this->getZip() != null)
			{
				$return .= $this->getZip(). ", ";
			}

			if ($this->getCountry() != "" && $this->getCountry() != null)
			{
				$return .= $this->getCountry(). ".";
			}
			
			if (substr($return, -2) == ", ")
				$return = substr($return, 0, -2);
				
			return $return;
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

		public $tags;
		private $original_tags;
		
		public $notes;
		public $emails;
		
		public function getEmailAddresses()
		{
			return $this->email_addresses;
		}

		public function getPhoneNumbers()
		{
			return $this->phone_numbers;
		}

		public function getAddresses()
		{
			return $this->addresses;
		}

		public function getWebAddresses()
		{
			return $this->web_addresses;
		}

		public function getInstantMessengers()
		{
			return $this->instant_messengers;
		}
		
		public function getTwitterAccounts()
		{
			return $this->twitter_accounts;
		}

		public function addEmail(HighriseEmail $email)
		{
			$this->emails[$email->id] = $email;
			
		}
		
		public function getEmails()
		{
			$this->emails = array();
			$xml = $this->getURL("/people/" . $this->id . "/emails.xml");
			$xml_obj = simplexml_load_string($xml);

			if ($this->debug == true);
				print_r($xml_obj);
			
			if (isset($xml_obj->email) && count($xml_obj->email) > 0)
			{
				foreach($xml_obj->email as $xml_email)
				{
					$email = new HighriseEmail($this->highrise);
					$email->loadFromXMLObject($xml_email);
					$this->addEmail($email);		
				}
			}
			
			return $this->emails;
		}
		
		public function addNote(HighriseNote $note)
		{
			$note->setSubjectId($this->id);
			$note->setSubjectType("Party");
			$note->save();
			$this->notes[$note->id] = $note;
		}
		
		public function getNotes()
		{
			$this->notes = array();
			$xml = $this->getURL("/people/" . $this->id . "/notes.xml");
			$xml_obj = simplexml_load_string($xml);

			if ($this->debug == true);
				print_r($xml_obj);
			
			if (isset($xml_obj->note) && count($xml_obj->note) > 0)
			{
				foreach($xml_obj->note as $xml_note)
				{
					$note = new HighriseNote($this->highrise);
					$note->loadFromXMLObject($xml_note);
					$this->addNote($note);		
				}
			}
			
			return $this->notes;
		}
		
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
				$new_xml = $this->postDataWithVerb("/people/" . $this->getId() . ".xml?reload=true", $person_xml, "PUT");
				$this->checkForErrors("Person");
			}
			else
			{
				$new_xml = $this->postDataWithVerb("/people.xml", $person_xml, "POST");
				$this->checkForErrors("Person", 201);
			}
			
			// Reload object and add tags.
				$tags = $this->tags;
				$original_tags = $this->original_tags;
				
				$this->loadFromXMLObject(simplexml_load_string($new_xml));
				$this->tags = $tags;
				$this->original_tags = $original_tags;
				$this->saveTags();
			
			return true;
		}
		
		public function saveTags()
		{
			if (is_array($this->tags))
			{
				foreach($this->tags as $tag_name => $tag)
				{
					if ($tag->getId() == null) // New Tag
					{
					 	
						if ($this->debug)
							print "Adding Tag: " . $tag->getName() . "\n";

						$new_tag_data = $this->postDataWithVerb("/people/" . $this->getId() . "/tags.xml", "<name>" . $tag->getName() . "</name>", "POST");
						$this->checkForErrors("Person (add tag)", array(200, 201));
						$new_tag_data = simplexml_load_string($new_tag_data);
						$this->tags[$tag_name]->setId($new_tag_data->id);
						unset($this->original_tags[$tag->getId()]);

					}
					else // Remove Tag from deletion list
					{
						unset($this->original_tags[$tag->getId()]);
					}
				}
				
				if (is_array($this->original_tags))
				{
					foreach($this->original_tags as $tag_id=>$v)
					{
						if ($this->debug)
							print "REMOVE TAG: " . $tag_id;
						$new_tag_data = $this->postDataWithVerb("/people/" . $this->getId() . "/tags/" . $tag_id . ".xml", "", "DELETE");
						$this->checkForErrors("Person (delete tag)", 200);
					}					
				}
				
				foreach($this->tags as $tag_name => $tag)
					$this->original_tags[$tag->getId()] = 1;	
			}
		}

		public function addTag($v)
		{
			if ($v instanceof HighriseTag && !isset($this->tags[$v->getName()]))
			{
				$this->tags[$v->getName()] = $v;
				$this->original_tags[$v->getId()] = 1;
				
			}
			elseif (!isset($this->tags[$v]))
			{
				$tag = new HighriseTag();
				$tag->name = $v;
				$this->tags[$v] = $tag;
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
			if ($this->debug)
				print_r($xml_obj);
			
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
			$this->setCompanyName($xml_obj->{'company-name'});
			
			$this->loadContactDataFromXMLObject($xml_obj->{'contact-data'});
			$this->loadTagsFromXMLObject($xml_obj->{'tags'});	
		}
		
		public function loadTagsFromXMLObject($xml_obj)
		{
			$this->original_tags = array();
			$this->tags = array();
			
			if (count($xml_obj->{'tag'}) > 0)
			{
				foreach($xml_obj->{'tag'} as $value)
				{
					$tag = new HighriseTag($value->{'id'}, $value->{'name'});
					$original_tags[$tag->getName()] = 1;	
					$this->addTag($tag);
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
		
		public function addAddress(HighriseAddress $address)
		{
			$this->addresses[] = $address;
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
		
		public function setVisibleTo($visible_to)
		{
			$valid_permissions = array("Everyone", "Owner");
			$visible_to = ucwords(strtolower($visible_to));
			if ($visible_to != null && !in_array($visible_to, $valid_permissions))
				throw new Exception("$visible_to is not a valid visibility permission. Available visibility permissions: " . implode(", ", $valid_permissions));
			
		  $this->visible_to = (string)$visible_to;
		}

		public function getVisibleTo()
		{
		  return $this->visible_to;
		}
		
		public function setAuthorId($author_id)
		{
		  $this->author_id = (string)$author_id;
		}

		public function getAuthorId()
		{
		  return $this->author_id;
		}
	
		public function setUpdatedAt($updated_at)
		{
		  $this->updated_at = (string)$updated_at;
		}

		public function getUpdatedAt()
		{
		  return $this->updated_at;
		}
		
		public function setCreatedAt($created_at)
		{
		  $this->created_at = (string)$created_at;
		}

		public function getCreatedAt()
		{
		  return $this->created_at;
		}

		public function setCompanyName($company_name)
		{
		  $this->company_name = (string)$company_name;
		}

		public function getCompanyName()
		{
		  return $this->company_name;
		}

		public function setBackground($background)
		{
		  $this->background = (string)$background;
		}

		public function getBackground()
		{
		  return $this->background;
		}

		
		public function getFullName()
		{
			return $this->getFirstName() . " " . $this->getLastName();
		}
		public function setLastName($last_name)
		{
		  $this->last_name = (string)$last_name;
		}

		public function getLastName()
		{
		  return $this->last_name;
		}

		public function setFirstName($first_name)
		{
		  $this->first_name = (string)$first_name;
		}

		public function getFirstName()
		{
		  return $this->first_name;
		}

		public function setTitle($title)
		{
		  $this->title = (string)$title;
		}

		public function getTitle()
		{
		  return $this->title;
		}

		
		public function setId($id)
		{
		  $this->id = (string)$id;
		}

		public function getId()
		{
		  return $this->id;
		}

		public function __construct(HighriseAPI $highrise)
		{
			$this->highrise = $highrise;
			$this->account = $highrise->account;
			$this->token = $highrise->token;
			$this->setVisibleTo("Everyone");
			$this->debug = $highrise->debug;
			$this->curl = curl_init();		
		}
	}
	
	
	
