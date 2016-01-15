<?php
namespace OCA\SingleSignOn;

class Util {
    public static function login($username, $password) {
        $manager = \OC::$server->getUserManager();
        $manager->emit('\OC\User', 'preLogin', array($username, $password));

        $user = $manager->get($username);
        \OC::$server->getUserSession()->setUser($user);

        $manager->emit('\OC\User', 'postLogin', array($user, $password));


        return true;
    }

    public static function firstLogin($userInfo) {
        $password = RequestManager::getRequest(ISingleSignOnRequest::USERPASSWORDGENERATOR) ? RequestManager::send(ISingleSignOnRequest::USERPASSWORDGENERATOR) : $userInfo->getUserId();

        \OC_User::createUser($userInfo->getUserId(), $password);
        \OC_User::setDisplayName($userInfo->getUserId(), $userInfo->getDisplayName());
        \OC::$server->getConfig()->setUserValue($userInfo->getUserId(), "settings", "email", $userInfo->getEmail());
        return \OC_User::login($userInfo->getUserId(), $password);
    }

    public static function webDavLogin($username, $password) {
        $data["userId"] = $username;
        $data["password"] = $password;
        $data["userIp"] = \OC::$server->getRequest()->getRemoteAddress();

        $ssoconfig = \OC::$server->getSystemConfig()->getValue("SSOCONFIG");
        RequestManager::init("soap", $ssoconfig["singleSignOnServer"], $ssoconfig["requests"]);

        $token = RequestManager::send(ISingleSignOnRequest::GETTOKEN, $data);

        $userInfo = RequestManager::getRequest(ISingleSignOnRequest::INFO);

        if(!$userInfo->send(array("token" => $token, "userIp" => $data["userIp"]))) {
            return ;
        }
        
        if(!\OC_User::userExists($userInfo->getUserId())) {
            return self::firstLogin($userInfo);
        }

        if($token){
            return self::login($username, $token);
        }

        return false;
    }

    public static function redirect($url) {
        if(!$url) {
            \OC_Util::redirectToDefaultPage();
        }
        else {
            header("location: " . $url);
            exit();
        }
    }
}
