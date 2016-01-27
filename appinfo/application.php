<?php
/**
 * ownCloud - singlesignon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Dino Peng <dino.p@inwinstack.com>
 * @copyright Dino Peng 2016
 */

namespace OCA\SingleSignOn\AppInfo;

use OCP\AppFramework\App;
use OCA\SingleSignOn\Middleware\SSOMiddleware; 

class Application extends App {
    /**
     * Define your dependencies in here
     */
    public function __construct(array $urlParams=array()){
        parent::__construct('singlesignon', $urlParams);

        $container = $this->getContainer();

        /**
         * Middleware
         */
        $container->registerService('SSOMiddleware', function($c) {
			return new SSOMiddleware(
				$c['Request'],
				$c['ControllerMethodReflector'],
				$c['OCP\IUserSession']
			);
		});
        // executed in the order that it is registered
        $container->registerMiddleware('SSOMiddleware');
    }
}
