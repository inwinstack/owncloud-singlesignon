<?php
namespace OCA\SingleSignOn;

class Util {
    public static function login($username) {
        $user = \OC::$server->getUserManager()->get($username);
        \OC::$server->getUserSession()->setUser($user);

        return true;
    }

    public static function firstLogin($userInfo) {
        $password = RequestManager::getRequest(ISingleSignOnRequest::USERPASSWORDGENERATOR) ? RequestManager::send(ISingleSignOnRequest::USERPASSWORDGENERATOR) : $userInfo->getUserId();

        \OC_User::createUser($userInfo->getUserId(), $password);
        \OC_User::setDisplayName($userInfo->getUserId(), $userInfo->getDisplayName());
        \OC::$server->getConfig()->setUserValue($userInfo->getUserId(), "settings", "email", $userInfo->getEmail());
        \OC_User::login($userInfo->getUserId(), $password);
    }

    public static function webDavLogin($username, $password) {
        $processor = SingleSignOnPreFilter::getInstance(); 

        $data["userId"] = $username;
        $data["password"] = $password;

        // $token = $processor->ssos[ISingleSignOnRequest::GETTOKEN]->send($data);

        $token = "81d97be71dac58d7d56272d150551ec3";

        return self::login($username);
    }

    public static function redirect($url) {
        if($url === false) {
            \OC_Util::redirectToDefaultPage();
        }
        else {
            header("location: " . $url);
            exit();
        }
    }
}
