<?php
// The User object.
class User {

    const DISCONNECTED = 0;
    const CONNECTED    = 1;

    public $group      = 'customer';
    public $points     = 42;
    protected $_status = 1;

    public function getStatus ( ) {

        return $this->_status;
    }
}