<?php
/**
 * ownCloud - testmiddleware
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Dino Peng <dino.p@inwinstack.com>
 * @copyright Dino Peng 2016
 */

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\TestMiddleWare\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        [
			'name'         => 'collaboration_api#preflighted_cors', // Valid for all API end points
			'url'          => '/api/{path}',
			'verb'         => 'OPTIONS',
			'requirements' => ['path' => '.+']
		],
	   ['name' => 'collaboration_api#getFileList', 'url' => '/api/filelist', 'verb' => 'GET'],       
	   ['name' => 'collaboration_api#shareLinks', 'url' => '/api/share', 'verb' => 'POST'],
	   ['name' => 'collaboration_api#unshare', 'url' => '/api/unshare', 'verb' => 'POST'],
	   ['name' => 'collaboration_api#upload', 'url' => '/api/upload', 'verb' => 'POST'],
	   ['name' => 'collaboration_api#download', 'url' => '/api/download', 'verb' => 'GET'],
    ]
];
