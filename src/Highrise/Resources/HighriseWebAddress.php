<?php

namespace Highrise\Resources;

class HighriseWebAddress {

    public $id;
    public $location;
    public $url;

    public function addXMLIntoNode($xml_node)
    {
        $email_adress_node = $xml_node->addChild('web-address');
        $email_adress_node->addChild('id', $this->getId())->addAttribute('type', 'integer');
        $email_adress_node->addChild('location', $this->getLocation());
        $email_adress_node->addChild('url', $this->getUrl());

        return $xml_node;
    }

    public function __construct($id = null, $url = null, $location = null) {
        $this->setId($id);
        $this->setUrl($url);
        $this->setLocation($location);
    }

    public function setUrl($url) {
        $this->url = (string) $url;
    }

    public function getUrl() {
        return $this->url;
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