<?php
namespace OCA\SingleSignOn;

class RedirectRegion implements IRedirectRegion{
    public static function getRegionUrl($region) {
        $params = array();

        $request = \OC::$server->getRequest();
        $requestUri = $request->getRequestUri();
        $config = \OC::$server->getSystemConfig();
        $regions = $config->getValue("sso_regions");

        $regionNum = $regions[$region] == "north" ? "1" : "2";

        preg_match("/(.*\/ocs\/.*)|(.*\/webdav.*)/", $requestUri, $matches);

        if(count($matches)){
            $url = $request->getServerProtocol() . "://" . $config->getValue("sso_owncloud_url")[$regions[$region]] . $requestUri;
        }
        else {
            $params["srv"] = $regionNum;
            $params["path"] = $requestUri;

            $url = $config->getValue("sso_login_url") . "?" . http_build_query($params);
        }

        return $url;
    }
}
