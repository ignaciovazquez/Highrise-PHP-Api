<?php

namespace Highrise\Resources;

class HighriseTag {

    public $id;
    public $name;

    public function __construct($id = null, $name = null) {
        $this->setId($id);
        $this->setName($name);
    }

    public function addXMLIntoNode($xml_node)
    {
        $email_adress_node = $xml_node->addChild('tag');
        $email_adress_node->addChild('id', $this->getId())->addAttribute('type', 'integer');
        $email_adress_node->addChild('name', $this->getName());

        return $xml_node;
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