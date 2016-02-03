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
$application = new Application();
$container = $app->getContainer();

$container->registerService("L10N", function($c) {
    return $c->getServerContainer()->getL10N("singlesignon");
});

$processor = new \OCA\SingleSignOn\SingleSignOnProcessor();
$processor->run();

\OCP\Util::addScript("singlesignon", "script");
