<?php

namespace Highrise\Resources;

use Highrise\HighriseAPI;

class HighriseNote extends HighriseAPI {

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

    public function save() {
        if ($this->subject_type == null || $this->subject_id == null) {
            throw new Exception("Subject Type and Subject ID must be set in order to create a new " . $this->_note_type);
        }

        if ($this->id == null) { // Create
            $note_xml = $this->toXML();
            $new_xml = $this->postDataWithVerb($this->_note_url . ".xml", $note_xml, "POST");
            $this->checkForErrors(ucwords($this->_note_type), 201);
            $this->loadFromXMLObject(simplexml_load_string($new_xml));
            return true;
        } else { // Update
            $note_xml = $this->toXML();
            $new_xml = $this->postDataWithVerb($this->_note_url . "/" . $this->getId() . ".xml", $note_xml, "PUT");
            $this->checkForErrors(ucwords($this->_note_type), 200);
            return true;
        }
    }

    public function delete() {
        $this->postDataWithVerb($this->_note_url . "/" . $this->getId() . ".xml", "", "DELETE");
        $this->checkForErrors(ucwords($this->_note_type), 200);
        $this->deleted = true;
    }

    public function loadFromXMLObject($xml_obj) {
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

    public function setSubjectName($subject_name) {
        $this->subject_name = (string) $subject_name;
    }

    public function getSubjectName() {
        return $this->subject_name;
    }

    public function setVisibleTo($visible_to) {
        $this->visible_to = (string) $visible_to;
    }

    public function getVisibleTo() {
        return $this->visible_to;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = (string) $updated_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    public function setSubjectType($subject_type) {
        $valid_types = array("Party", "Company", "Deal", "Kase");
        $subject_type = ucwords(strtolower($subject_type));
        if ($subject_type != null && !in_array($subject_type, $valid_types))
            throw new Exception("$subject_type is not a valid subject type. Available subject types: " . implode(", ", $valid_types));

        $this->subject_type = (string) $subject_type;
    }

    public function getSubjectType() {
        return $this->subject_type;
    }

    public function setSubjectId($subject_id) {
        $this->subject_id = (string) $subject_id;
    }

    public function getSubjectId() {
        return $this->subject_id;
    }

    public function setOwnerId($owner_id) {
        $this->owner_id = (string) $owner_id;
    }

    public function getOwnerId() {
        return $this->owner_id;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = (string) $created_at;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setBody($body) {
        $this->body = (string) $body;
    }

    public function getBody() {
        return $this->body;
    }

    public function setAuthorId($author_id) {
        $this->author_id = (string) $author_id;
    }

    public function getAuthorId() {
        return $this->author_id;
    }

    public function __construct(HighriseAPI $highrise) {
        $this->account = $highrise->account;
        $this->token = $highrise->token;
        $this->setVisibleTo("Everyone");
        $this->debug = $highrise->debug;
        $this->curl = curl_init();

        $this->_note_type = "note";
        $this->_note_url = "/notes";
    }

    public function toXML() {
        $xml = "<" . $this->_note_type . ">\n";
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

    public function __toString() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function getId() {
        return $this->id;
    }
}