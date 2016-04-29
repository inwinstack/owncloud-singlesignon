<?php

namespace OCA\SingleSignOn\Middleware;

use OCA\SingleSignOn\AuthInfo;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

/**
 * This middleware sets the correct CORS headers on a response if the
 * controller has the @SSOCORS annotation. This is needed for webapps that want
 * to access an API and dont run on the same domain, see
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
class SSOMiddleware extends Middleware {

	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @var ControllerMethodReflector
	 */
	private $reflector;

	/**
	 * @var IUserSession
	 */
	private $session;

	/**
	 * @param IRequest $request
	 * @param ControllerMethodReflector $reflector
	 * @param IUserSession $session
	 */
	public function __construct(IRequest $request,
								ControllerMethodReflector $reflector,
								IUserSession $session) {
		$this->request = $request;
		$this->reflector = $reflector;
		$this->session = $session;
	}

	/**
	 * This is being run in normal order before the controller is being
	 * called which allows several modifications and checks
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @throws SecurityException
	 * @since 6.0.0
	 */
	public function beforeController($controller, $methodName){
		// ensure that @SSOCORS annotated API routes are not used in conjunction
		// with session authentication since this enables CSRF attack vectors
        if ($this->reflector->hasAnnotation('SSOCORS') &&
			!$this->reflector->hasAnnotation('PublicPage')) {
            AuthInfo::init();
            $authInfo = AuthInfo::get();
            if(!\OC::$server->getSystemConfig()->getValue("sso_one_time_password")) {
                $tokenVaildator = \OCA\SingleSignOn\RequestManager::send(\OCA\SingleSignOn\ISingleSignOnRequest::VALIDTOKEN, $authInfo);
                if (!$tokenVaildator) { 
                    throw new SecurityException('Token expired!', Http::STATUS_UNAUTHORIZED);
                }
            }
            $userInfo = \OCA\SingleSignOn\RequestManager::getRequest(\OCA\SingleSignOn\ISingleSignOnRequest::INFO);

			$this->session->logout();
			if(!\OCA\SingleSignOn\Util::login($userInfo,$authInfo)) {
				throw new SecurityException('SSO CORS requires basic auth', Http::STATUS_UNAUTHORIZED);
			}
		}

	}

	/**
	 * This is being run after a successful controllermethod call and allows
	 * the manipulation of a Response object. The middleware is run in reverse order
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param Response $response the generated response from the controller
	 * @return Response a Response object
	 * @throws SecurityException
	 */
	public function afterController($controller, $methodName, Response $response){
		// only react if its a CORS request and if the request sends origin and

		if(isset($this->request->server['HTTP_ORIGIN']) &&
			$this->reflector->hasAnnotation('SSOCORS')) {

			// allow credentials headers must not be true or CSRF is possible
			// otherwise
			foreach($response->getHeaders() as $header => $value) {
				if(strtolower($header) === 'access-control-allow-credentials' &&
				   strtolower(trim($value)) === 'true') {
					$msg = 'Access-Control-Allow-Credentials must not be '.
						   'set to true in order to prevent CSRF';
					throw new SecurityException($msg);
				}
			}

			$origin = $this->request->server['HTTP_ORIGIN'];
			$response->addHeader('Access-Control-Allow-Origin', $origin);
		}
		return $response;
	}

	/**
	 * If an SecurityException is being caught return a JSON error response
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param \Exception $exception the thrown exception
	 * @throws \Exception the passed in exception if it cant handle it
	 * @return Response a Response object or null in case that the exception could not be handled
	 */
	public function afterException($controller, $methodName, \Exception $exception){
		if($exception instanceof SecurityException){
			$response =  new JSONResponse(['message' => $exception->getMessage()]);
			if($exception->getCode() !== 0) {
				$response->setStatus($exception->getCode());
			} else {
				$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			return $response;
		}

		throw $exception;
	}

}
