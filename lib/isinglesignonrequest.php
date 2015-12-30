<?php

namespace OCA\SingleSignOn;

interface ISingleSignOnRequest {
    const VALIDTOKEN = "validtoken";
    const INFO = "info";
    const AUTH = "auth";
    const INVALIDTOKEN = "invalidtoken";
    const USERPASSWORDGENERATOR = "userpasswordgenerator";
    const GETTOKEN = "gettoken";

    public function name();
    public function send($data);
    public function getErrorMsg();
}

