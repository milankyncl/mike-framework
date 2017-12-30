<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Application;

use Mike\Bootstrap;
use Mike\Application;
use Mike\Application\Mvc\Router;
use Mike\Http\Response;
use Mike\Http\Response\Cookies;
use Mike\Http\Request;


class Full extends Application {


	public function __construct( Bootstrap $bootstrap ) {

		$dependencyContainer = $bootstrap->getDependencyContainer();


		$dependencyContainer->set('router', new Router());

		$dependencyContainer->set('response', new Response());

		$dependencyContainer->set('cookies', new Cookies());

		$dependencyContainer->set('request', new Request());

		/*

		$dependencyContainer->set('dispatcher', new Dispatcher());

		$dependencyContainer->set('view', new View());

		$dependencyContainer->set('url', new Url());

		$dependencyContainer->set('modelsManager', new ModelsManager());

		$dependencyContainer->set('filter', new Filter());

		$dependencyContainer->set('escaper', new Escaper());

		$dependencyContainer->set('security', new Security());

		$dependencyContainer->set('crypt', new Crypt());

		$dependencyContainer->set('flash', new FlashSession());

		$dependencyContainer->set('tah', new Tag());

		$dependencyContainer->set('session', new SessionAdapter());

		$dependencyContainer->set('assets', new AssetsManager());

		*/

		$bootstrap->setDependencyContainer($dependencyContainer);

		parent::__construct($bootstrap);
	}

}