<?php

namespace Highrise\Resources;

class HighriseInstantMessenger {

    public $id;
    private $location;
    private $protocol;
    public $address;

    public function __construct($id = null, $protocol = null, $address = null, $location = null) {
        $this->setId($id);
        $this->setProtocol($protocol);
        $this->setAddress($address);
        $this->setLocation($location);
    }

    public function addXMLIntoNode($xml_node)
    {
        $email_adress_node = $xml_node->addChild('instant-messenger');
        $email_adress_node->addChild('id', $this->getId())->addAttribute('type', 'integer');
        $email_adress_node->addChild('protocol', $this->getProtocol());
        $email_adress_node->addChild('location', $this->getLocation());
        $email_adress_node->addChild('address', $this->getAddress());

        return $xml_node;
    }

    public function __toString() {
        return $this->getProtocol() . ":" . $this->getAddress();
    }

    public function setAddress($address) {
        $this->address = (string) $address;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setProtocol($protocol) {
        $valid_protocols = array("AIM", "MSN", "ICQ", "Jabber", "Yahoo", "Skype", "QQ", "Sametime", "Gadu-Gadu", "Google Talk", "Other");
        if ($protocol != null && !in_array($protocol, $valid_protocols))
            throw new \Exception("$protocol is not a valid protocol. Available protocols: " . implode(", ", $valid_protocols));

        $this->protocol = (string) $protocol;
    }

    public function getProtocol() {
        return $this->protocol;
    }

    public function setLocation($location) {
        $valid_locations = array("Work", "Personal", "Other");
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