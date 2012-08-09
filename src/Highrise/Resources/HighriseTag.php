<?php

namespace Highrise\Resources;

class HighriseTag {

    public $id;
    public $name;

    public function __construct($id = null, $name = null) {
        $this->setId($id);
        $this->setName($name);
    }

    public function toXML() {
        $xml = "<tag>\n";
        if ($this->getId() != null)
            $xml .= '<id type="integer">' . $this->getId() . "</id>\n";
        $xml .= '<name>' . $this->getName() . "</name>\n";
        $xml .= "</tag>\n";
        return $xml;
    }

    public function __toString() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = (string) $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function getId() {
        return $this->id;
    }

}