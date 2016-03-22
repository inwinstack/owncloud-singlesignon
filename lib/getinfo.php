<?php
namespace OCA\SingleSignOn;

class GetInfo implements IUserInfoRequest {
    private $soapClient;
    private $userId;
    private $email;
    private $groups = array();
    private $userGroup;
    private $displayName;
    private $errorMsg;

    public function __construct($soapClient){
        $this->soapClient = $soapClient;
    }

    public function name() {
        return ISingleSignOnRequest::INFO;
    }

    public function send($data = null) {
        $result = $this->soapClient->__soapCall("getToken2", array(array("TokenId" => $data["token"], "UserIP" => $data["userIp"])));

        if($result->return->ActXML->StatusCode != 200) {
            $this->errorMsg = $result->return->ActXML->Message;
            return false;
        }

        $info = $result->return->ActXML->RsInfo->User;

        $this->userId = $info->UserAccount;
        $this->email = $info->UserEmail;
        $this->displayName = $info->CName;
        $this->userSid = $info->UserSid;
        $this->userGroup = $info->UserGroup;

        return true;
    }

    public function getErrorMsg() {
        return $this->errorMsg;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getGroups() {
        return $this->groups;
    }

    public function getDisplayName() {
        return $this->displayName;
    }


    /**
     * Getter for userSid
     *
     * @return string userSid
     */
    public function getRegion() {
        return (int)substr($this->userSid,0,2);
    }

    /**
     * Check user have permassion to use the service or not
     *
     * @return bool
     */
    public function hasPermission(){
        if ($this->userGroup == "T" || $this->userGroup == "S") {
            return true;
        }

        return false;
    }
}
