<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike;

use Mike\Http\Response;
use Mike\Application\Mvc\Router;
use Mike\Application\Mvc\Controller;
use Mike\DependencyContainer\Injectable;


class Application extends Injectable {

	/**
	 * @var $_dependencyContainer DependencyContainer
	 */

	protected $_dependencyContainer;

	/**
	 * @var string
	 */

	protected $_defaultModule;

	/**
	 * @var array
	 */

	protected $_modules = [];

	/**
	 * @var string $_rootPath
	 */

	private $_rootPath;

	/**
	 * Application constructor
	 */

	public function __construct(Bootstrap $bootstrap) {

		$this->_dependencyContainer = $bootstrap->getDependencyContainer();

		$this->_rootPath = $bootstrap->getRootPath();

	}

	/**
	 * Handling URL
	 * ----------------------------------
	 */

	private $_sendHeaders = false;

	private $_sendCookies = false;

	/**
	 * Handle URI in application
	 *
	 * @return Response
	 */

	public function handle($uri = null) {

		/** @var $router Router */

		$router = $this->_dependencyContainer->get('router');

		$router->handle($uri);
		$matchedRoute = $router->getMatchedRoute();

		if(is_object($matchedRoute)) {

			$match = $matchedRoute->getMatch();

			if(!is_null($match)) {

				$possibleResponse = call_user_func_array($match, $router->getParams());

				if(is_string($possibleResponse)) {

					/** @var Response $response */

					$response = $this->_dependencyContainer->get('response');
					$response->setContent($possibleResponse);

					return $response;
				}

				if(is_object($possibleResponse)) {

					if($possibleResponse instanceof Response) {

						$possibleResponse->sendHeaders();
						$possibleResponse->sendCookies();

						return $possibleResponse;
					}
				}
			}
		}

		$this->_moduleName = $router->getModuleName();
		$this->_controllerName = $router->getControllerName();
		$this->_actionName = $router->getActionName();
		$this->_params = $router->getParams();

		$view = $this->_dependencyContainer->get('view');

		$view->start();

		$controller = $this->resolveController();

		$possibleResponse = $this->getReturnedValue();

		if(is_bool($possibleResponse) && !$possibleResponse) {

			$response = $this->_dependencyContainer->get('response');

		} else {

			if(is_string($possibleResponse)) {

				$response = $this->_dependencyContainer->get('response');
				$response->setContent($possibleResponse);

			} else {

				$returnedResponse = ((is_object($possibleResponse)) && ($possibleResponse instanceof Response));

				if(!$returnedResponse) {

					if(is_object($controller)) {

						$view->render(
							$this->_controllerName,
							$this->_actionName
						);
					}
				}

				$view->finish();

				if($returnedResponse) {

					$response = $possibleResponse;

				} else {

					$response = $this->_dependencyContainer->get('response');

					$response->setContent($view->getContent());
				}
			}
		}

		if($this->_sendHeaders)
			$response->sendHeaders();

		if($this->_sendCookies)
			$response->sendCookies();


		return $response;
	}

	/**
	 * ====================================
	 */

	private $_moduleName;

	private $_controllerName;

	private $_actionName;

	private $_params;

	private $_resolved = false;

	private $_initialized;

	protected $_returnedValue = null;

	/**
	 * Dispatching requests, resolve controller class
	 *
	 * @return Controller
	 *
	 * @throws Exception
	 */

	public function resolveController() {

		while(!$this->_resolved) {

			$this->_initialized = false;

			$controllerClass = $this->getControllerClass();

			if(!class_exists($controllerClass))
				throw new Exception($controllerClass . ' controller class cannot be loaded.');

			$controller = new $controllerClass();

			if(!($controller instanceof Controller))
				throw new Exception($controllerClass . ' is not valid controller instance.');

			$controller->setDC($this->_dependencyContainer);

			$actionName = $this->_actionName . 'Action';
			$params = $this->_params;

			// Check if the params is an array
			if(!is_array($params))
				throw new Exception('Action parameters must be array.');

			// Check if the method exists in the handler
			if(!is_callable([$controller, $actionName]))
				throw new Exception('Action \'' . $actionName . '\' was not found on handler \'' . $actionName . '\'');

			// Call 'onInit' method
			if(method_exists($controller, 'onInit')) {

				try {

					$controller->onInit();

					$this->_initialized = true;

				} catch(Exception $e) {

					throw $e;
				}
			}

			// Call ActionMethod
			try {

				$this->_returnedValue = $this->callActionMethod($controller, $actionName, $params);

			} catch(Exception $e) {

				throw $e;
			}

			// Call 'afterExecute' method
			if(method_exists($controller, 'afterExecute')) {

				try {

					$controller->afterExecute();

				} catch (Exception $e) {

					throw $e;
				}
			}

			// TODO: Odstranit pro neukončený cyklus

			$this->_resolved = true;

		}

		return $controller;
	}

	/**
	 * Forwards the execution flow to another controller/action.
	 *
	 * @param array|string $direction
	 * @param array $params
	 *
	 * @throws \Mike\Exception
	 */

	protected function forward($direction, $params = []) {

		if(!$this->_initialized)
			throw new Exception('Controller must be initialized first before forwarding.');

		if(is_string($direction)) {

			/** @var \Mike\Application\Mvc\Url $url */

			$url = $this->_dependencyContainer->get('url');

			$destination = $url->resolveDestinationCode($direction);

		}

		if(!is_array($destination))
			throw new Exception('Forward direction must be array or string.');

		if(isset($direction['module']))
			$this->_controllerName = $direction['module'];

		if(isset($direction['controller']))
			$this->_controllerName = $direction['controller'];

		if(isset($direction['action']))
			$this->_actionName = $direction['action'];

		if(!empty($params))
			$this->_params = $params;


		$this->_resolved = false;
	}

	/**
	 * Possible class name that will be located to dispatch the request
	 */

	private function getControllerClass() {

		return '\\' . ucfirst($this->_moduleName) . '\\Controllers\\' . ucfirst($this->_controllerName) . 'Controller';

	}

	/**
	 * Helper for calling action method
	 *
	 * @param Controller $controller
	 * @param string $action
	 * @param array $params
	 *
	 * @return mixed
	 */

	public function callActionMethod(Controller $controller, $action, array $params = []) {

		return call_user_func_array([$controller, $action], $params);
	}

	/**
	 * Get returned value from action method
	 *
	 * @return mixed
	 */

	private function getReturnedValue() {

		return $this->_returnedValue;
	}

}