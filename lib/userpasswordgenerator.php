<?php

namespace OCA\SingleSignOn;

class UserPasswordGenerator implements ISingleSignOnRequest{
    private $processor;
    
    public function __construct($processor){
        $this->processor = $processor;
    }

    function name() {
        return ISingleSignOnRequest::USERPASSWORDGENERATOR;
    }

    function send($data = null) {
    }

    public function getErrorMsg() {}
}
