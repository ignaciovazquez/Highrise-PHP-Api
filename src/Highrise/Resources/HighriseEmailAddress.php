<?php

namespace Highrise\Resources;

class HighriseEmailAddress {

    public $id;
    public $address;
    public $location;

    public function __construct($id = null, $address = null, $location = null) {
        $this->setId($id);
        $this->setAddress($address);
        $this->setLocation($location);
    }

    public function addXMLIntoNode($xml_node)
    {
        $email_adress_node = $xml_node->addChild('email-address');
        $email_adress_node->addChild('id', $this->getId())->addAttribute('type', 'integer');
        $email_adress_node->addChild('address', $this->getAddress());
        $email_adress_node->addChild('location', $this->getLocation());

        return $xml_node;
    }

    public function __toString() {
        return $this->getAddress();
    }

    public function setAddress($address) {
        $this->address = (string) $address;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setLocation($location) {
        $valid_locations = array("Work", "Home", "Other");
        $location = ucwords(strtolower($location));
        if ($location != null && !in_array($location, $valid_locations))
            throw new \Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));

        $this->location = (string) $location;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function getId() {
        return $this->id;
    }

}