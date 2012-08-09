<?php

namespace Highrise\Resources;

use Highrise\HighriseAPI;
use Highrise\Resources\HighriseUser;

class HighriseTask extends HighriseAPI {

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

    public function complete() {
        $task_xml = $this->toXML();
        $new_task_xml = $this->postDataWithVerb("/tasks/" . $this->getId() . "/complete.xml", "", "POST");
        $this->checkForErrors("Task", 200);
        $this->loadFromXMLObject(simplexml_load_string($new_task_xml));
        return true;
    }

    public function save() {
        if ($this->getFrame() == null)
            throw new Exception("You need to specify a valid time frame to save a task");

        if ($this->id == null) { // Create
            $task_xml = $this->toXML();
            $new_task_xml = $this->postDataWithVerb("/tasks.xml", $task_xml, "POST");
            $this->checkForErrors("Task", 201);
            $this->loadFromXMLObject(simplexml_load_string($new_task_xml));
            return true;
        } else {
            $task_xml = $this->toXML();
            $new_task_xml = $this->postDataWithVerb("/tasks/" . $this->getId() . ".xml", $task_xml, "PUT");
            $this->checkForErrors("Task", 200);
            return true;
        }
    }

    public function delete() {
        $this->postDataWithVerb("/tasks/" . $this->getId() . ".xml", "", "DELETE");
        $this->checkForErrors("Task", 200);
        $this->deleted = true;
    }

    public function assignToUser(HighriseUser $user) {
        $this->setOwnerId($user->getId());
    }

    public function setOwnerId($owner_id) {
        $this->owner_id = (string) $owner_id;
    }

    public function getOwnerId() {
        return $this->owner_id;
    }

    public function setNotify($notify) {
        if ($notify == "true" || $notify == true || $notify == 1)
            $notify = true;
        else
            $notify = false;

        $this->notify = (string) $notify;
    }

    public function getNotify() {
        return $this->notify;
    }

    public function setRecordingId($recording_id) {
        $this->recording_id = (string) $recording_id;
    }

    public function getRecordingId() {
        return $this->recording_id;
    }

    public function setPublic($public) {
        $this->public = (string) $public;
    }

    public function getPublic() {
        return $this->public;
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

    public function setAlertAt($alert_at) {
        $this->alert_at = (string) $alert_at;
    }

    public function getAlertAt() {
        return $this->alert_at;
    }

    public function setDueAt($due_at) {
        $this->due_at = (string) $due_at;
    }

    public function getDueAt() {
        return $this->due_at;
    }

    public function setFrame($subject_type) {
        $valid_frames = array("today", "tomorrow", "this_week", "next_week", "later", "overdue");
        $frame = str_replace(" ", "_", strtolower($subject_type));

        if ($frame != null && !in_array($frame, $valid_frames))
            throw new Exception("$subject_type is not a valid frame. Available frames: " . implode(", ", $valid_frames));

        $this->frame = (string) $frame;
    }

    public function getFrame() {
        return $this->frame;
    }

    public function setBody($body) {
        $this->body = (string) $body;
    }

    public function getBody() {
        return $this->body;
    }

    public function setCategoryId($category_id) {
        $this->category_id = (string) $category_id;
    }

    public function getCategoryId() {
        return $this->category_id;
    }

    public function setSubjectName($subject_name) {
        $this->subject_name = (string) $subject_name;
    }

    public function getSubjectName() {
        return $this->subject_name;
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

    public function setAuthorId($author_id) {
        $this->author_id = (string) $author_id;
    }

    public function getAuthorId() {
        return $this->author_id;
    }

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function getId() {
        return $this->id;
    }

    public function toXML() {
        $xml = "<task>\n";
        if ($this->getId() != null)
            $xml .= '<id type="integer">' . $this->getId() . "</id>\n";

        if ($this->getRecordingId() != null) {
            $xml .= '<recording-id>' . $this->getSubjectId() . "</subject-id>\n";
        }

        if ($this->getSubjectId() != null) {
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

    public function loadFromXMLObject($xml_obj) {

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

    public function __construct(HighriseAPI $highrise) {
        $this->account = $highrise->account;
        $this->token = $highrise->token;
        $this->debug = $highrise->debug;
        $this->curl = curl_init();
    }

}