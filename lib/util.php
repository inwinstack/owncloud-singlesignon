<?php
namespace OCA\SingleSignOn;

class Util {
    public static function login($username, $token) {
        $manager = \OC::$server->getUserManager();
        $manager->emit('\OC\User', 'preLogin', array($username, $token));

        $user = $manager->get($username);
        \OC::$server->getUserSession()->setUser($user);
        \OC::$server->getUserSession()->setLoginName($user);
        \OC::$server->getSession()->set("sso_token", $token);

        \OC_Util::setupFS($username);
        \OC::$server->getUserFolder($username);

        $manager->emit('\OC\User', 'postLogin', array($user, $token));


        return true;
    }

    public static function firstLogin($userInfo, $token) {
        $password = RequestManager::getRequest(ISingleSignOnRequest::USERPASSWORDGENERATOR) ? RequestManager::send(ISingleSignOnRequest::USERPASSWORDGENERATOR) : $userInfo->getUserAccount();

        \OC_User::createUser($userInfo->getUserAccount(), $password);
        \OC_User::setDisplayName($userInfo->getUserAccount(), $userInfo->getDisplayName());
        \OC::$server->getConfig()->setUserValue($userInfo->getUserAccount(), "settings", "email", $userInfo->getEmail());
        \OC::$server->getSession()->set("sso_token", $token);
        return \OC_User::login($userInfo->getUserAccount(), $password);
    }

    public static function webDavLogin($username, $password) {
        $data["userId"] = $username;
        $data["password"] = $password;
        $data["userIp"] = \OC::$server->getRequest()->getRemoteAddress();

        $config = \OC::$server->getSystemConfig();
        RequestManager::init($config->getValue("sso_portal_url"), $config->getValue("sso_requests"));

        $token = RequestManager::send(ISingleSignOnRequest::GETTOKEN, $data);

        $userInfo = RequestManager::getRequest(ISingleSignOnRequest::INFO);

        if(!$userInfo->send(array("token" => $token, "userIp" => $data["userIp"]))) {
            return ;
        }
        
        if(!\OC_User::userExists($userInfo->getUserAccount())) {
            return self::firstLogin($userInfo, $token);
        }

        if($token){
            return self::login($userInfo->getUserAccount(), $token);
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

    /**
     * Check user region and redirect to correct region.
     *
     * @return void
     */
    public static function redirectRegion($userInfo, $regions, $serverUrls, $token) {
        $region = $userInfo->getRegion();
        $request = \OC::$server->getRequest();

        preg_match("/.*(\?.*)$/", $request->getRequestUri(), $param);

        $redirectUrl = $serverUrls[$regions[$region]] . $param[1];

        preg_match("/https*:\/\/(.*)\//", $redirectUrl, $url);

        if($request->getServerHost() === $url[1]) {
            return ;
        }

        self::redirect($redirectUrl);
    }
}
