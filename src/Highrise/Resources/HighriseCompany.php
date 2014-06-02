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

class HighriseCompany
{
    public $id;
    public $name;
    public $background;
    public $created_at;
    public $updated_at;
    public $visible_to;
    public $owner_id;
    public $group_id;
    public $author_id;
    public $contact_details;
    public $email_addresses;
    public $phone_numbers;
    public $addresses;
    public $web_addresses;
    public $instant_messengers;
    public $twitter_accounts;
    public $tags;
    public $notes;
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

    /**
     *
     * @param HighriseAPI $client
     */
    public function __construct(HighriseAPI $client)
    {
        $this->client = $client;
        $this->setVisibleTo("Everyone");
        $this->customFields = array();
    }

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
        $xml = $this->client->getURL("/companies/" . $this->id . "/emails.xml");
        $xml_obj = simplexml_load_string($xml);

        if ($this->client->debug == true) {
            print_r($xml_obj);
        }

        if (isset($xml_obj->email) && count($xml_obj->email) > 0) {
            foreach ($xml_obj->email as $xml_email) {
                $email = new HighriseEmail($this->client);
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
        $xml = $this->client->getURL("/companies/" . $this->id . "/notes.xml");
        $xml_obj = simplexml_load_string($xml);

        if ($this->client->debug == true) {
            print_r($xml_obj);
        }

        if (isset($xml_obj->note) && count($xml_obj->note) > 0) {
            foreach ($xml_obj->note as $xml_note) {
                $note = new HighriseNote($this->client);
                $note->loadFromXMLObject($xml_note);
                $this->addNote($note);
            }
        }

        return $this->notes;
    }

    public function delete()
    {
        $this->client->postDataWithVerb("/companies/" . $this->getId() . ".xml", "", "DELETE");
        $this->client->checkForErrors("Company", 200);
    }

    public function save()
    {
        $person_xml = $this->toXML(false);
        if ($this->getId() != null) {
            $new_xml = $this->client->postDataWithVerb("/companies/" . $this->getId() . ".xml?reload=true", $person_xml, "PUT");
            $this->client->checkForErrors("Company");
        } else {
            $new_xml = $this->client->postDataWithVerb("/companies.xml", $person_xml, "POST");
            $this->client->checkForErrors("Company", 201);
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
        if (is_array($this->tags)) {
            foreach ($this->tags as $tag_name => $tag) {
                if ($tag->getId() == null) { // New Tag
                    if ($this->client->debug)
                        print "Adding Tag: " . $tag->getName() . "\n";

                    $new_tag_data = $this->client->postDataWithVerb("/companies/" . $this->getId() . "/tags.xml", "<name>" . $tag->getName() . "</name>", "POST");
                    $this->client->checkForErrors("Company (add tag)", array(200, 201));
                    $new_tag_data = simplexml_load_string($new_tag_data);
                    $this->tags[$tag_name]->setId($new_tag_data->id);
                    unset($this->original_tags[$tag->getId()]);
                } else { // Remove Tag from deletion list
                    unset($this->original_tags[$tag->getId()]);
                }
            }

            if (is_array($this->original_tags)) {
                foreach ($this->original_tags as $tag_id => $v) {
                    if ($this->client->debug)
                        print "REMOVE TAG: " . $tag_id;
                    $new_tag_data = $this->client->postDataWithVerb("/companies/" . $this->getId() . "/tags/" . $tag_id . ".xml", "", "DELETE");
                    $this->client->checkForErrors("Company (delete tag)", 200);
                }
            }

            foreach ($this->tags as $tag_name => $tag) {
                $this->original_tags[$tag->getId()] = 1;
            }
        }
    }

    public function addTag($v)
    {
        if ($v instanceof HighriseTag && !isset($this->tags[$v->getName()])) {
            $this->tags[$v->getName()] = $v;
            $this->original_tags[$v->getId()] = 1;
        } elseif (!isset($this->tags[$v])) {
            $tag = new HighriseTag();
            $tag->name = $v;
            $this->tags[$v] = $tag;
        }
    }

    /**
     *
     * @param string $tag You can either pass a tag name or a Tag instance
     */
    public function removeTag($tag)
    {
        if ($tag instanceof HighriseTag) {
            $name = $tag->getName();
        } else {
            foreach ($this->tags as $name => $obj) {
                if ($tag == $name) {
                    break;
                }
            }

            // Tag not found
            if ($tag != $name) {
                return;
            }
        }

        unset($this->tags[$name]);
    }

    public function toXML()
    {
        $sxe = new \SimpleXMLElement('<company></company>');
        $sxe->addChild('id', $this->getId())->addAttribute('type', 'integer');

        $fields = array("name", "background", "visible_to", "owner_id", "group_id");
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

    public function loadFromXMLObject($xml_obj)
    {
        if ($this->client->debug) {
            print_r($xml_obj);
        }

        $this->setId($xml_obj->id);
        $this->setName($xml_obj->{'name'});
        $this->setBackground($xml_obj->{'background'});
        $this->setOwnerId($xml_obj->{'owner-id'});
        $this->setGroupId($xml_obj->{'group-id'});
        $this->setAuthorId($xml_obj->{'author-id'});
        $this->setVisibleTo($xml_obj->{'visible-to'});
        $this->setCreatedAt($xml_obj->{'created-at'});
        $this->setUpdatedAt($xml_obj->{'updated-at'});

        if(!is_null($xml_obj->subject_datas->subject_data)){
            foreach($xml_obj->subject_datas->subject_data as $custom_field){
                $this->setCustomField((string) $custom_field->subject_field_label, (string) $custom_field->value); 
            } 
        }

        $this->loadContactDataFromXMLObject($xml_obj->{'contact-data'});
        $this->loadTagsFromXMLObject($xml_obj->{'tags'});
    }

    public function loadTagsFromXMLObject($xml_obj)
    {
        $this->original_tags = array();
        $this->tags = array();

        if (count($xml_obj->{'tag'}) > 0) {
            foreach ($xml_obj->{'tag'} as $value) {
                $tag = new HighriseTag($value->{'id'}, $value->{'name'});
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

    public function removeEmailAddress($address)
    {
        if ($this->email_addresses) {
            foreach ($this->email_addresses as $email) {
                if ($email->address == $address) {
                    $email->id = '-' . $email->id;
                }
            }
        }
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

    public function setBackground($background)
    {
        $this->background = (string) $background;
    }

    public function getBackground()
    {
        return $this->background;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setOwnerId($owner_id)
    {
        $this->owner_id = (string) $owner_id;
    }

    public function getOwnerId()
    {
        return $this->owner_id;
    }

    public function setGroupId($group_id)
    {
        $this->group_id = (string) $group_id;
    }

    public function getGroupId()
    {
        return $this->group_id;
    }

    public function setAuthorId($author_id)
    {
        $this->author_id = (string) $author_id;
    }

    public function getAuthorId()
    {
        return $this->author_id;
    }

    public function setVisibleTo($visible_to)
    {
        $valid_permissions = array("Everyone", "Owner");
        $visible_to = ucwords(strtolower($visible_to));
        if ($visible_to != null && !in_array($visible_to, $valid_permissions)) {
            throw new \Exception("$visible_to is not a valid visibility permission. Available visibility permissions: " . implode(", ", $valid_permissions));
        }

        $this->visible_to = (string) $visible_to;
    }

    public function getVisibleTo()
    {
        return $this->visible_to;
    }

    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = (string) $updated_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setCreatedAt($created_at)
    {
        $this->created_at = (string) $created_at;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setId($id)
    {
        $this->id = (string) $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCustomField($subject_field_id, $value)
    {
        $this->customFields[$subject_field_id] = $value;
    }

    public function getCustomFields()
    {
        return $this->customFields;
    }
}
