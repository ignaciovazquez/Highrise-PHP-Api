<?php

namespace Highrise\Resources;

use Highrise\HighriseAPI;
use Highrise\Resources\HighriseEmail;
use Highrise\Resources\HighriseNote;
use Highrise\Resources\HighriseTag;
use Highrise\Resources\HighrisePhoneNumber;
use Highrise\Resources\HighriseEmailAddress;
use Highrise\Resources\HighriseInstantMessenger;
use Highrise\Resources\HighriseWebAddress;
use Highrise\Resources\HighriseTwitterAccount;

class HighrisePerson {
    public  $id,
            $title,
            $first_name,
            $last_name,
            $background,
            $company_name,
            $created_at,
            $updated_at,
            $company_id,
            $author_id,
            $contact_details,
            $visible_to,
            $email_addresses,
            $phone_numbers,
            $addresses,
            $web_addresses,
            $instant_messengers,
            $twitter_accounts,
            $tags,
            $notes,
            $emails;
    
    private $original_tags;
    
    /**
     *
     * @var HighriseAPI
     */
    protected $client;

    /**
     *
     * @var array
     */
    protected $customFields = array();

    public function getEmailAddresses() {
        return $this->email_addresses;
    }

    public function getPhoneNumbers() {
        return $this->phone_numbers;
    }

    public function getAddresses() {
        return $this->addresses;
    }

    public function getWebAddresses() {
        return $this->web_addresses;
    }

    public function getInstantMessengers() {
        return $this->instant_messengers;
    }

    public function getTwitterAccounts() {
        return $this->twitter_accounts;
    }

    public function addEmail(HighriseEmail $email) {
        $this->emails[$email->id] = $email;
    }

    public function getEmails() {
        $this->emails = array();
        $xml = $this->client->getURL("/people/" . $this->id . "/emails.xml");
        $xml_obj = simplexml_load_string($xml);

        if ($this->client->debug == true)
            ;
        print_r($xml_obj);

        if (isset($xml_obj->email) && count($xml_obj->email) > 0) {
            foreach ($xml_obj->email as $xml_email) {
                $email = new HighriseEmail($this->client);
                $email->loadFromXMLObject($xml_email);
                $this->addEmail($email);
            }
        }

        return $this->emails;
    }

    public function addNote(HighriseNote $note) {
        $note->setSubjectId($this->id);
        $note->setSubjectType("Party");
        $note->save();
        $this->notes[$note->id] = $note;
    }

    public function getNotes() {
        $this->notes = array();
        $xml = $this->client->getURL("/people/" . $this->id . "/notes.xml");
        $xml_obj = simplexml_load_string($xml);

        if ($this->client->debug == true)
            ;
        print_r($xml_obj);

        if (isset($xml_obj->note) && count($xml_obj->note) > 0) {
            foreach ($xml_obj->note as $xml_note) {
                $note = new HighriseNote($this->client);
                $note->loadFromXMLObject($xml_note);
                $this->addNote($note);
            }
        }

        return $this->notes;
    }

    public function delete() {
        $this->client->postDataWithVerb("/people/" . $this->getId() . ".xml", "", "DELETE");
        $this->client->checkForErrors("Person", 200);
    }

    public function save() {
        $person_xml = $this->toXML(false);
        if ($this->getId() != null) {
            $new_xml = $this->client->postDataWithVerb("/people/" . $this->getId() . ".xml?reload=true", $person_xml, "PUT");
            $this->client->checkForErrors("Person");
        } else {
            $new_xml = $this->client->postDataWithVerb("/people.xml", $person_xml, "POST");
            $this->client->checkForErrors("Person", 201);
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

    public function saveTags() {
        if (is_array($this->tags)) {
            foreach ($this->tags as $tag_name => $tag) {
                if ($tag->getId() == null) { // New Tag
                    if ($this->client->debug)
                        print "Adding Tag: " . $tag->getName() . "\n";

                    $new_tag_data = $this->client->postDataWithVerb("/people/" . $this->getId() . "/tags.xml", "<name>" . $tag->getName() . "</name>", "POST");
                    $this->client->checkForErrors("Person (add tag)", array(200, 201));
                    $new_tag_data = simplexml_load_string($new_tag_data);
                    $this->tags[$tag_name]->setId($new_tag_data->id);
                    unset($this->original_tags[$tag->getId()]);
                }
                else { // Remove Tag from deletion list
                    unset($this->original_tags[$tag->getId()]);
                }
            }

            if (is_array($this->original_tags)) {
                foreach ($this->original_tags as $tag_id => $v) {
                    if ($this->client->debug)
                        print "REMOVE TAG: " . $tag_id;
                    $new_tag_data = $this->client->postDataWithVerb("/people/" . $this->getId() . "/tags/" . $tag_id . ".xml", "", "DELETE");
                    $this->client->checkForErrors("Person (delete tag)", 200);
                }
            }

            foreach ($this->tags as $tag_name => $tag)
                $this->original_tags[$tag->getId()] = 1;
        }
    }

    public function addTag($v) {
        if ($v instanceof HighriseTag && !isset($this->tags[$v->getName()])) {
            $this->tags[$v->getName()] = $v;
            $this->original_tags[$v->getId()] = 1;
        } elseif (!isset($this->tags[$v])) {
            $tag = new HighriseTag();
            $tag->name = $v;
            $this->tags[$v] = $tag;
        }
    }

    public function toXML() {
        $sxe = new \SimpleXMLElement('<person></person>');
        $sxe->addChild('id', $this->getId())->addAttribute('type', 'integer');

        $fields = array("title", "first_name", "last_name", "background", "visible_to", "company_name");
        foreach ($fields as $field) {
            $xml_field_name = str_replace("_", "-", $field);
            $sxe->addChild($xml_field_name, $this->$field);
        }

        $contact_data_node = $sxe->addChild('contact-data');
        $contact_data = array("email_addresses", "instant_messengers", "twitter_accounts", "web_addresses", "addresses", "phone_numbers");
        foreach ($contact_data as $contact_node) {
            if (count($this->$contact_node) > 0) {
                $type_data_node = $contact_data_node->addChild(str_replace("_", "-", $contact_node));
                foreach ($this->$contact_node as $items) {
                    $type_data_node = $items->addXMLIntoNode($type_data_node);
                }
            }
        }

        if ($this->customFields) {
            $subject_datas = $sxe->addChild('subject_datas');
            $subject_datas->addAttribute('type', 'array');
            foreach ($this->customFields as $subject_field_id => $value) {
                $data = $subject_datas->addChild('subject_data');
                $data->addChild('value', $value);
                $field_id = $data->addChild('subject_field_id', $subject_field_id);
                $field_id->addAttribute('type', 'integer');
            }
        }

        return $sxe->asXML();
    }

    public function loadFromXMLObject($xml_obj) {
        if ($this->client->debug)
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

    public function loadTagsFromXMLObject($xml_obj) {
        $this->original_tags = array();
        $this->tags = array();

        if (count($xml_obj->{'tag'}) > 0) {
            foreach ($xml_obj->{'tag'} as $value) {
                $tag = new HighriseTag($value->{'id'}, $value->{'name'});
                $original_tags[$tag->getName()] = 1;
                $this->addTag($tag);
            }
        }
    }

    public function loadContactDataFromXMLObject($xml_obj) {
        $this->phone_numbers = array();
        $this->email_addresses = array();
        $this->web_addresses = array();
        $this->addresses = array();
        $this->instant_messengers = array();

        if (isset($xml_obj->{'phone-numbers'})) {
            foreach ($xml_obj->{'phone-numbers'}->{'phone-number'} as $value) {
                $number = new HighrisePhoneNumber($value->{'id'}, $value->{'number'}, $value->{'location'});
                $this->phone_numbers[] = $number;
            }
        }

        if (isset($xml_obj->{'email-addresses'})) {
            foreach ($xml_obj->{'email-addresses'}->{'email-address'} as $value) {
                $email_address = new HighriseEmailAddress($value->{'id'}, $value->{'address'}, $value->{'location'});
                $this->email_addresses[] = $email_address;
            }
        }

        if (isset($xml_obj->{'instant-messengers'})) {
            foreach ($xml_obj->{'instant-messengers'}->{'instant-messenger'} as $value) {
                $instant_messenger = new HighriseInstantMessenger($value->{'id'}, $value->{'protocol'}, $value->{'address'}, $value->{'location'});
                $this->instant_messengers[] = $instant_messenger;
            }
        }

        if (isset($xml_obj->{'web-addresses'})) {
            foreach ($xml_obj->{'web-addresses'}->{'web-address'} as $value) {
                $web_address = new HighriseWebAddress($value->{'id'}, $value->{'url'}, $value->{'location'});
                $this->web_addresses[] = $web_address;
            }
        }

        if (isset($xml_obj->{'twitter-accounts'})) {
            foreach ($xml_obj->{'twitter-accounts'}->{'twitter-account'} as $value) {
                $twitter_account = new HighriseTwitterAccount($value->{'id'}, $value->{'username'}, $value->{'location'});
                $this->twitter_accounts[] = $twitter_account;
            }
        }

        if (isset($xml_obj->{'addresses'})) {
            foreach ($xml_obj->{'addresses'}->{'address'} as $value) {
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

    public function addAddress(HighriseAddress $address) {
        $this->addresses[] = $address;
    }

    public function addEmailAddress($address, $location = "Home") {
        $item = new HighriseEmailAddress();
        $item->setAddress($address);
        $item->setLocation($location);

        $this->email_addresses[] = $item;
    }

    public function removeEmailAddress($address)
    {
        if ($this->email_addresses)
        {
          foreach ($this->email_addresses as $email)
          {
              if ($email->address == $address)
              {
                  $email->id = '-'.$email->id;
              }
          }
        }
    }

    public function addPhoneNumber($number, $location = "Home") {
        $item = new HighrisePhoneNumber();
        $item->setNumber($number);
        $item->setLocation($location);

        $this->phone_numbers[] = $item;
    }

    public function addWebAddress($url, $location = "Work") {
        $item = new HighriseWebAddress();
        $item->setUrl($url);
        $item->setLocation($location);

        $this->web_addresses[] = $item;
    }

    public function addInstantMessenger($protocol, $address, $location = "Personal") {
        $item = new HighriseInstantMessenger();
        $item->setProtocol($protocol);
        $item->setAddress($address);
        $item->setLocation($location);

        $this->instant_messengers[] = $item;
    }

    public function addTwitterAccount($username, $location = "Personal") {
        $item = new HighriseTwitterAccount();
        $item->setUsername($username);
        $item->setLocation($location);

        $this->twitter_accounts[] = $item;
    }

    public function setCompanyId($company_id) {
        $this->company_id = (string) $company_id;
    }

    public function getCompanyId() {
        return $this->company_id;
    }

    public function setVisibleTo($visible_to) {
        $valid_permissions = array("Everyone", "Owner");
        $visible_to = ucwords(strtolower($visible_to));
        if ($visible_to != null && !in_array($visible_to, $valid_permissions))
            throw new \Exception("$visible_to is not a valid visibility permission. Available visibility permissions: " . implode(", ", $valid_permissions));

        $this->visible_to = (string) $visible_to;
    }

    public function getVisibleTo() {
        return $this->visible_to;
    }

    public function setAuthorId($author_id) {
        $this->author_id = (string) $author_id;
    }

    public function getAuthorId() {
        return $this->author_id;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = (string) $updated_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = (string) $created_at;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setCompanyName($company_name) {
        $this->company_name = (string) $company_name;
    }

    public function getCompanyName() {
        return $this->company_name;
    }

    public function setBackground($background) {
        $this->background = (string) $background;
    }

    public function getBackground() {
        return $this->background;
    }

    public function getFullName() {
        return $this->getFirstName() . " " . $this->getLastName();
    }

    public function setLastName($last_name) {
        $this->last_name = (string) $last_name;
    }

    public function getLastName() {
        return $this->last_name;
    }

    public function setFirstName($first_name) {
        $this->first_name = (string) $first_name;
    }

    public function getFirstName() {
        return $this->first_name;
    }

    public function setTitle($title) {
        $this->title = (string) $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function getId() {
        return $this->id;
    }

    public function setCustomField($subject_field_id, $value) {
        $this->customFields[$subject_field_id] = $value;
    }

    public function getCustomFields() {
        return $this->customFields;
    }

    public function __construct(HighriseAPI $client) {
        $this->client = $client;
        $this->setVisibleTo("Everyone");
        $this->customFields = array();
    }

}