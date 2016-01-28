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

namespace OCA\SingleSignOn\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\ApiController;
use OCP\IRequest;

class CollaborationApiController extends ApiController {

    public function __construct($appName,IRequest $request) {
		parent::__construct($appName, $request);
	}

	
    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
     * @SSOCORS
	 */
    public function getFileList($dir = null, $sortby = "name", $sort = false){
        \OCP\JSON::checkLoggedIn();
        \OC::$server->getSession()->close();
        $l = \OC::$server->getL10N('files');

        // Load the files
        $dir = $dir ? (string)$dir : '';
        $dir = \OC\Files\Filesystem::normalizePath($dir);

        try {
            $dirInfo = \OC\Files\Filesystem::getFileInfo($dir);
            if (!$dirInfo || !$dirInfo->getType() === 'dir') {
                header("HTTP/1.0 404 Not Found");
                exit();
            }

            $data = array();
            $baseUrl = \OCP\Util::linkTo('files', 'index.php') . '?dir=';

            $permissions = $dirInfo->getPermissions();

            $sortDirection = $sort === 'desc';
            $mimetypeFilters = '';

            $files = [];
            if (is_array($mimetypeFilters) && count($mimetypeFilters)) {
                $mimetypeFilters = array_unique($mimetypeFilters);

                if (!in_array('httpd/unix-directory', $mimetypeFilters)) {
                    $mimetypeFilters[] = 'httpd/unix-directory';
                }

                foreach ($mimetypeFilters as $mimetypeFilter) {
                    $files = array_merge($files, \OCA\Files\Helper::getFiles($dir, $sortby, $sortDirection, $mimetypeFilter));
                }

                $files = \OCA\Files\Helper::sortFiles($files, $sortby, $sortDirection);
            } else {
                $files = \OCA\Files\Helper::getFiles($dir, $sortby, $sortDirection);
            }

            $files = \OCA\Files\Helper::populateTags($files);
            $data['directory'] = $dir;
            $data['files'] = \OCA\Files\Helper::formatFileInfos($files);
            $data['permissions'] = $permissions;
            return new DataResponse(array('data' => $data, 'status' => 'success'));
        } catch (\OCP\Files\StorageNotAvailableException $e) {
            \OCP\Util::logException('files', $e);
            return new DataResponse(
                array(
                    'data' => array(
                        'exception' => '\OCP\Files\StorageNotAvailableException',
                        'message' => $l->t('Storage not available')
                    ),
                    'status' => 'error'
                )
            );
        } catch (\OCP\Files\StorageInvalidException $e) {
            \OCP\Util::logException('files', $e);
            return new DataResponse(
                array(
                    'data' => array(
                        'exception' => '\OCP\Files\StorageInvalidException',
                        'message' => $l->t('Storage invalid')
                    ),
                    'status' => 'error'
                )
            );
        } catch (\Exception $e) {
            \OCP\Util::logException('files', $e);
            return new DataResponse(
                array(
                    'data' => array(
                        'exception' => '\Exception',
                        'message' => $l->t('Unknown error')
                    ),
                    'status' => 'error'
                )
            );
        }
    }

    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
     * @SSOCORS
	 */
    public function shareLinks($files, $password = null, $expiration = null){

        $shareLinkUrls = array();
        for($i = 0; $i < sizeof($files); $i++){
            $type = $files[$i]['type'];
            $id = $files[$i]['id'];
            $name = $files[$i]['name'];
            $permissions = $files[$i]['permissions'];
        
            $shareType = \OCP\Share::SHARE_TYPE_LINK;
            
            $passwordChanged = $password !== null;
            $token = \OCP\Share::shareItem(
                $type,
                $id,
                $shareType,
                $password,
                $permissions,
                $name,
                (!empty($expiration) ? new \DateTime((string)$expiration) : null),
                $passwordChanged
            );
            $url = self::generateShareLink($token);
            $shareLinkUrls[$i]['name'] = $name;
            $shareLinkUrls[$i]['url'] = $url;
        }
        json_encode($shareLinkUrls, JSON_PRETTY_PRINT);
        return new DataResponse(array("data" => $shareLinkUrls, "status" => 'success'));
    } 

    private static function generateShareLink($token){
        $request = \OC::$server->getRequest();
        $protocol = $request->getServerProtocol();
        $host = $request->getServerHost();
        $webRoot = \OC::$server->getWebRoot();
        
        $shareLinkUrl = $protocol . "://" . $host . $webRoot . "/index.php" . "/s/" . $token;
        return $shareLinkUrl;
    }

}
