<?php

namespace Highrise\Resources;

class HighriseUser {

    public $id;
    public $name;
    public $email_address;
    public $token;
    public $dropbox;
    public $created_at;
    public $updated_at;

    public function loadFromXMLObject($xml_obj) {
        $this->setId($xml_obj->{'id'});
        $this->setName($xml_obj->{'name'});
        $this->setEmailAddress($xml_obj->{'email-address'});
        $this->setToken($xml_obj->{'token'});
        $this->setDropbox($xml_obj->{'dropbox'});
        $this->setCreatedAt($xml_obj->{'created-at'});
        $this->setUpdatedAt($xml_obj->{'updated-at'});

        return true;
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

    public function setDropbox($dropbox) {
        $this->dropbox = (string) $dropbox;
    }

    public function getDropbox() {
        return $this->dropbox;
    }

    public function setToken($token) {
        $this->token = (string) $token;
    }

    public function getToken() {
        return $this->token;
    }

    public function setEmailAddress($email_address) {
        $this->email_address = (string) $email_address;
    }

    public function getEmailAddress() {
        return $this->email_address;
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