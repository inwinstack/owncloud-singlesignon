<?php

namespace OCA\SingleSignOn;

class ValidTokenAuthority implements ISingleSignOnRequest {
    private $processor;
    
    public function __construct($processor){
        $this->processor = $processor;
    }

    function name() {
        return ISingleSignOnRequest::AUTH;
    }

    function send($data = null) {

    }

    public function getErrorMsg() {}
}
