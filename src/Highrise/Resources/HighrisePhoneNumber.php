<?php

namespace Highrise\Resources;

class HighrisePhoneNumber {

    public $id;
    public $number;
    public $location;

    public function __construct($id = null, $number = null, $location = null) {
        $this->setId($id);
        $this->setNumber($number);
        $this->setLocation($location);
    }

    public function addXMLIntoNode($xml_node)
    {
        $email_adress_node = $xml_node->addChild('phone-number');
        $email_adress_node->addChild('id', $this->getId())->addAttribute('type', 'integer');
        $email_adress_node->addChild('number', $this->getNumber());
        $email_adress_node->addChild('location', $this->getLocation());

        return $xml_node;
    }    

    public function setLocation($location) {
        $valid_locations = array("Work", "Mobile", "Fax", "Pager", "Home", "Skype", "Other");
        $location = ucwords(strtolower($location));
        if ($location != null && !in_array($location, $valid_locations))
            throw new \Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));

        $this->location = (string) $location;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setNumber($number) {
        $this->number = (string) $number;
    }

    public function getNumber() {
        return $this->number;
    }

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function getId() {
        return $this->id;
    }

    public function __toString() {
        return $this->number;
    }

}