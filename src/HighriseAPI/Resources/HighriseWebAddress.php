<?php

namespace Highrise\Resources;

class HighriseWebAddress {

    public $id;
    public $location;
    public $url;

    public function toXML() {
        $xml = "<web-address>\n";
        if ($this->getId() != null)
            $xml .= '<id type="integer">' . $this->getId() . "</id>\n";
        $xml .= '<location>' . $this->getLocation() . "</location>\n";
        $xml .= '<url>' . $this->getUrl() . "</url>\n";
        $xml .= "</web-address>\n";
        return $xml;
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
            throw new Exception("$location is not a valid location. Available locations: " . implode(", ", $valid_locations));

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