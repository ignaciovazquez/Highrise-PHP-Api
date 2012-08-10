<?php

namespace Highrise\Resources;

class HighriseTwitterAccount {

    public $id;
    public $location;
    public $username;

    public function __construct($id = null, $username = null, $location = null) {
        $this->setId($id);
        $this->setUsername($username);
        $this->setLocation($location);
    }

    public function addXMLIntoNode($xml_node)
    {
        $email_adress_node = $xml_node->addChild('twitter-account');
        $email_adress_node->addChild('id', $this->getId())->addAttribute('type', 'integer');
        $email_adress_node->addChild('username', $this->getUsername());
        $email_adress_node->addChild('location', $this->getLocation());

        return $xml_node;
    }

    public function setUrl($url) {
        throw new \Exception("Cannot set URLs, change Username instead");
    }

    public function getUrl() {
        return "http://twitter.com/" . $this->getUsername();
    }

    public function setUsername($username) {
        $this->username = (string) $username;
        $this->url = $this->getUrl();
    }

    public function getUsername() {
        return $this->username;
    }

    public function setLocation($location) {
        $valid_locations = array("Business", "Personal", "Other");
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