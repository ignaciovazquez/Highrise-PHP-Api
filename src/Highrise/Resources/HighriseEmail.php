<?php

namespace Highrise\Resources;

class HighriseEmail extends HighriseNote {

    public $title;

    public function setTitle($title) {
        $this->title = (string) $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function __construct(HighriseAPI $highrise) {
        parent::__construct($highrise);
        $this->_note_type = "email";
        $this->_note_url = "/emails";
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
        $this->setTitle($xml_obj->{'title'});
        $this->setBody($xml_obj->{'body'});

        return true;
    }

}