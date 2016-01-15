<?php
/**
 * ownCloud - singlesignon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author dauba <dauba.k@inwinstack.com>
 * @copyright dauba 2015
 */

namespace OCA\SingleSignOn\AppInfo;

use OCP\AppFramework\App;

$app = new App('singlesignon');

$container = $app->getContainer();

$container->registerService("L10N", function($c) {
    return $c->getServerContainer()->getL10N("singlesignon");
});

$pathInfo = $_SERVER['PATH_INFO'];
preg_match('/(.+webdav.+)|(.*cloud.*)/', $pathInfo, $matches);
if(isset($pathInfo) && (count($matches))){
    return;
}
else {
    $processor = new \OCA\SingleSignOn\SingleSignOnProcessor();
    $processor->run();
}

\OCP\Util::addScript("singlesignon", "script");
