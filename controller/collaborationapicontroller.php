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
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\ApiController;
use OCP\IRequest;

class CollaborationApiController extends ApiController {

    public function __construct($appName,IRequest $request) {
		parent::__construct($appName, $request);
	}

	const msg_idNotExist = 'This file is not exist';
    const msg_errorType = 'incorrect type $type of $file($id)';
    const msg_unreshareable = 'This file is not allowed to reshare';
    const msg_noRequireUnshareBeforeShare = 'This file is not allow unshare , because it hasn\'t be shared';
    
    private $fileTypePattern = '/(.*)(file)(.*)/';
    private $errorTypePattern = '/(.*)(\$type)(.*)(\$file.*)/';

    private $shareType = \OCP\Share::SHARE_TYPE_LINK;

    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
     * @SSOCORS
	 */
    public function getFileList($dir = null, $sortby = 'name', $sort = false){
        \OCP\JSON::checkLoggedIn();
        \OC::$server->getSession()->close();

        // Load the files
        $dir = $dir ? (string)$dir : '';
        $dir = \OC\Files\Filesystem::normalizePath($dir);

        try {
            $dirInfo = \OC\Files\Filesystem::getFileInfo($dir);
            if (!$dirInfo || !$dirInfo->getType() === 'dir') {
                header('HTTP/1.0 404 Not Found');
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
                        'message' => 'Storage not available'
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
                        'message' => 'Storage invalid'
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
                        'message' => 'Unknown error'
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
    public function upload($dir = '/') {
        \OC::$server->getSession()->close();
        
        // Firefox and Konqueror tries to download application/json for me.  --Arthur
        \OCP\JSON::setContentTypeHeader('text/plain');

        // If a directory token is sent along check if public upload is permitted.
        // If not, check the login.
        // If no token is sent along, rely on login only

        $allowedPermissions = \OCP\Constants::PERMISSION_ALL;
        $errorCode = null;
        
        if(\OC\Files\Filesystem::file_exists($dir) === false) {
            return new DataResponse(
                array(
                'data' => array_merge(array('message' => 'Invalid directory.')),
                'status' => 'error'
                )
            );
        }
        // get array with current storage stats (e.g. max file size)
        $storageStats = \OCA\Files\Helper::buildFileStorageStatistics($dir);
        
        $files = $this->request->getUploadedFile('files');

        if (!isset($files)) {
            return new DataResponse(
                array(
                    'data' => array_merge(array('message' => 'No file was uploaded. Unknown error'), $storageStats),
                    'status' => 'error'
                )
            );
        }

        foreach ($files['error'] as $error) {
            if ($error != 0) {
                $errors = array(
                    UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini: '
                    . ini_get('upload_max_filesize'),
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
                );
                $errorMessage = $errors[$error];
                \OC::$server->getLogger()->alert("Upload error: $error - $errorMessage", array('app' => 'files'));
                return new DataResponse(
                    array(
                        'data' => array_merge(array('message' => $errorMessage), $storageStats),
                        'status' => 'error'
                    )
                );
            }
        }

        $error = false;

        $maxUploadFileSize = $storageStats['uploadMaxFilesize'];
        $maxHumanFileSize = \OCP\Util::humanFileSize($maxUploadFileSize);

        $totalSize = 0;
        foreach ($files['size'] as $size) {
            $totalSize += $size;
        }
        if ($maxUploadFileSize >= 0 and $totalSize > $maxUploadFileSize) {
            return new DataResponse(
                array(
                    'data' => array('message' => 'Not enough storage available',
                    'uploadMaxFilesize' => $maxUploadFileSize,
                '   maxHumanFilesize' => $maxHumanFileSize),
                    'status' => 'error'
                )
            );
        }

        $result = array();
        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            // target directory for when uploading folders
            $relativePath = '';
                
            $target = \OC\Files\Filesystem::normalizePath($dir . $relativePath.'/'.$files['name'][$i]);

            // relative dir to return to the client
            if (isset($publicDirectory)) {
                // path relative to the public root
                $returnedDir = $publicDirectory . $relativePath;
            } else {
                // full path
                $returnedDir = $dir . $relativePath;
            }
            $returnedDir = \OC\Files\Filesystem::normalizePath($returnedDir);


            $exists = \OC\Files\Filesystem::file_exists($target);
            if ($exists) {
                $target = \OCP\Files::buildNotExistingFileName($dir . $relativePath, $files['name'][$i]);
            }
            try
            {
                if (is_uploaded_file($files['tmp_name'][$i]) and \OC\Files\Filesystem::fromTmpFile($files['tmp_name'][$i], $target)) {

                    // updated max file size after upload
                    $storageStats = \OCA\Files\Helper::buildFileStorageStatistics($dir);

                    $meta = \OC\Files\Filesystem::getFileInfo($target);
                    if ($meta === false) {
                        $error = 'The target folder has been moved or deleted.';
                        $errorCode = 'targetnotfound';
                    } else {
                        $data = \OCA\Files\Helper::formatFileInfo($meta);
                        $data['originalname'] = $files['name'][$i];
                        $data['uploadMaxFilesize'] = $maxUploadFileSize;
                        $data['maxHumanFilesize'] = $maxHumanFileSize;
                        $data['permissions'] = $meta['permissions'] & $allowedPermissions;
                        $data['directory'] = $returnedDir;
                        $result[] = $data;
                    }

                } else {
                    $error = 'Upload failed. Could not find uploaded file';
                }
            } catch(Exception $ex) {
                $error = $ex->getMessage();
            }

        }

        if ($error === false) {
            $result = \OCP\JSON::encode($result);
            return new DataResponse(
                array(
                    'data' => $result,
                    'status' => 'success'
                )
            );
        } else {
            return new DataResponse(
                array(
                    'data' => array_merge(array('message' => $error, 'code' => $errorCode), $storageStats),
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
            $permissions = 1;
            
            $path = \OC\Files\Filesystem::getPath($id);
            if($path === null){
                $shareLinkUrls[$i]['name'] = $name;
                $shareLinkUrls[$i]['url'] = null;
                $shareLinkUrls[$i]['id'] = $id;
                $shareLinkUrls[$i]['type'] = $type;
                if($type == 'file'){
                    $shareLinkUrls[$i]['message'] = self::msg_idNotExist;
                }
                else{
                    $replacement = '${1}folder${3}';
                    $msg_idNotExist = preg_replace($this->fileTypePattern, $replacement, self::msg_idNotExist);
                    $shareLinkUrls[$i]['message'] = $msg_idNotExist;
                }
                continue;
            }

            if (\OC\Files\Filesystem::filetype($path) !== $type) {
                $shareLinkUrls[$i]['name'] = $name;
                $shareLinkUrls[$i]['url'] = null;
                $shareLinkUrls[$i]['id'] = $id;
                $shareLinkUrls[$i]['type'] = $type;
                $replacement = '${1}\''. $type .'\'${3}'. $name . '(' . $id . ')';
                $msg_errorType = preg_replace($this->errorTypePattern, $replacement, self::msg_errorType);
                $shareLinkUrls[$i]['message'] = $msg_errorType;
                continue;
            }

            if(!\OC\Files\Filesystem::isSharable($path)){
                $shareLinkUrls[$i]['name'] = $name;
                $shareLinkUrls[$i]['url'] = null;
                $shareLinkUrls[$i]['id'] = $id;
                $shareLinkUrls[$i]['type'] = $type;
                if($type == 'file'){
                    $shareLinkUrls[$i]['message'] = self::msg_unreshareable;
                }
                else{
                    $replacement = '${1}folder${3}';
                    $msg_unreshareable = preg_replace($this->fileTypePattern, $replacement, self::msg_unreshareable);
                    $shareLinkUrls[$i]['message'] = $msg_unreshareable;
                }
                continue;
            }
            
            if($type == 'dir'){
                $type = 'folder';
            }

            $passwordChanged = $password !== null;
            $token = \OCP\Share::shareItem(
                $type,
                $id,
                $this->shareType,
                $password,
                $permissions,
                $name,
                (!empty($expiration) ? new \DateTime((string)$expiration) : null),
                $passwordChanged
            );
            if($type == 'folder') {
                $type = 'dir';
            }
            $url = self::generateShareLink($token);
            $shareLinkUrls[$i]['name'] = $name;
            $shareLinkUrls[$i]['url'] = $url;
            $shareLinkUrls[$i]['id'] = $id;
            $shareLinkUrls[$i]['type'] = $type;
        }
        json_encode($shareLinkUrls, JSON_PRETTY_PRINT);
        return new DataResponse(array('data' => $shareLinkUrls, 'status' => 'success'));
    }

    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
     * @SSOCORS
	 */
    public function unshare($id, $type) {
        $shareWith = null;
        $path = \OC\Files\Filesystem::getPath($id);
        $response = array('id' => $id);
        if($path === null){
            if($type == 'file'){
                $error_msg = self::msg_idNotExist;
            }
            else{
                $replacement = '${1}folder${3}';
                $error_msg = preg_replace($this->fileTypePattern, $replacement, self::msg_idNotExist);
            }
            return new DataResponse(array('data' => $response, 'status' => 'error', 'message' => $error_msg));
        }

        if (\OC\Files\Filesystem::filetype($path) !== $type) {
            $replacement = '${1}\''. $type .'\'${3}'. 'id: ' . $id;
            $error_msg = preg_replace($this->errorTypePattern, $replacement, self::msg_errorType);
            return new DataResponse(array('data' => $response, 'status' => 'error', 'message' => $error_msg));
        }

        if($type == 'dir'){
            $type = 'folder';
        }
        $unshare = \OCP\Share::unshare((string)$type,(string) $id, (int)$this->shareType, $shareWith);
        if($unshare){
            return new DataResponse(array('data' => $response, 'status' => 'success'));
        }
        else{
            $replacement = '${1}folder${3}';
            $error_msg = preg_replace($this->fileTypePattern, $replacement, self::msg_noRequireUnshareBeforeShare);
            return new DataResponse(array('data' => $response, 'status' => 'error', 'message' => $error_msg));
        }
    }

    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
     * @SSOCORS
	 */
    public function download($dir = null, $file = null) {
        \OC::$server->getSession()->close();
        
        $exists = \OC\Files\Filesystem::file_exists($dir."/".$file);
        if(!$exists) {
            return new NotFoundResponse();
        }
        $files_list = json_decode($file);
        // in case we get only a single file
        if (!is_array($files_list)) {
            $files_list = array($file);
        }
       
        \OC_Files::get($dir, $files_list, $_SERVER['REQUEST_METHOD'] == 'HEAD');
    }


    private static function generateShareLink($token) {
        $request = \OC::$server->getRequest();
        $protocol = $request->getServerProtocol();
        $host = $request->getServerHost();
        $webRoot = \OC::$server->getWebRoot();
        
        $shareLinkUrl = $protocol . '://' . $host . $webRoot . '/index.php' . '/s/' . $token;
        return $shareLinkUrl;
    }
}
