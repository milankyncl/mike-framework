<?php

namespace Mike;


class Autoloader {


	protected $_classes = [];

	protected $_namespaces = [];

	protected $_directories = [];

	protected $_files = [];

	protected $_registeredBefore = false;

	/**
	 * Register namespaces
	 *
	 * @param array $namespaces
	 * @param bool $merge
	 *
	 * @return self
	 */

	public function registerNamespaces(array $namespaces, $merge = false) {

		$parsedNamespaces = $this->parseNamespaces($namespaces);

		if($merge) {

			foreach($parsedNamespaces as $namespace => $paths) {

				if(!isset($this->_namespaces[$namespace])) {

					$this->_namespaces[$namespace] = [];

				}

				if(!is_array($paths))
					$paths = [ $paths ];

				$this->_namespaces[$namespace] = array_merge($this->_namespaces[$namespace], $paths);
			}

		} else {

			$this->_namespaces = $parsedNamespaces;
		}

		return $this;
	}

	/**
	 * Parse namespaces
	 *
	 * @param array $namespaces
	 *
	 * @return array
	 */

	protected function parseNamespaces(array $namespaces) {

		$parsed = [];

		foreach($namespaces as $namespace => $paths) {

			if(is_array($paths)) {

				$localPaths = [$paths];
			} else {

				$localPaths = $paths;
			}

			$parsed[$namespace] = $localPaths;
		}

		return $parsed;
	}

	/**
	 * Return registered namespaces
	 *
	 * @return array
	 */

	public function getNamespaces() {

		return $this->_namespaces;
	}

	/**
	 * Register directories
	 *
	 * @param array $directories
	 * @param bool $merge
	 *
	 * @return self
	 */

	public function registerDirectories(array $directories, $merge = false) {

		if($merge) {

			$this->_directories = array_merge($this->_directories, $directories);
		} else {

			$this->_directories = $directories;
		}

		return $this;
	}

	/**
	 * Returns the directories currently registered in the autoloader
	 *
	 * @return array
	 */

	public function getDirectories() {

		return $this->_directories;
	}

	/**
	 * Register single files
	 *
	 * @param array $files
	 * @param bool $merge
	 *
	 * @return self
	 */

	public function registerFiles(array $files, $merge = false) {

		if($merge) {

			$this->_files = array_merge($this->_files, $files);
		} else {

			$this->_files = $files;
		}

		return $this;
	}


	/**
	 * Return registered single files
	 *
	 * @return array
	 */

	public function getFiles() {

		return $this->_files;
	}

	/**
	 * Register classes and their locations
	 *
	 * @param array $classes
	 * @param bool $merge
	 *
	 * @return $this
	 */


	public function registerClasses(array $classes, $merge = false) {

		if($merge) {

			$this->_classes = array_merge($this->_classes, $classes);
		} else {

			$this->_classes = $classes;
		}

		return $this;
	}

	/**
	 * Get registered classes
	 *
	 * @return array
	 */

	public function getClasses() {

		return $this->_classes;
	}

	/**
	 * Register autoload method
	 *
	 * @param null $prepend
	 *
	 * @return $this
	 */

	public function register($prepend = null) {

		if(!$this->_registeredBefore) {

			/**
			 * Loads individual files added using Loader->registerFiles()
			 */

			$this->loadFiles();

			/**
			 * Registers directories & namespaces to PHP's autoload
			 */
			spl_autoload_register([ $this, 'autoload' ], true, $prepend);

			$this->_registered = true;
		}

		return $this;
	}

	/**
	 * Unregister autoload method
	 *
	 * @return mixed
	 */

	public function unregister() {

		if($this->_registeredBefore) {

			spl_autoload_unregister([ $this, 'autoload' ]);

			$this->_registeredBefore = false;

		}

		return $this;
	}

	/**
	 * Check for file existence, then require it
	 */

	public function loadFiles() {

		foreach($this->_files as $filePath) {

			if(is_file($filePath)) {

				require_once $filePath;
			}
		}
	}

	/**
	 * Autoloads the registered classes
	 *
	 * @return bool
	 */

	public function autoload($className) {

		/**
		 * First we check for static paths for classes
		 */

		$classes = $this->_classes;

		if(isset($classes[$className])) {

			$filePath = $classes[$className];

			require_once $filePath;

			return true;

		}

		$ds = DIRECTORY_SEPARATOR;
		$ns = '\\';

		/**
		 * Checking in namespaces
		 */

		$namespaces = $this->_namespaces;

		foreach($namespaces as $nsPrefix => $directories) {

			/**
			 * The class name must start with the current namespace
			 */

			if(!substr($className, 0, strlen($nsPrefix)) === $nsPrefix)
				continue;

			/**
			 * Append the namespace separator to the prefix
			 */

			$fileName = substr($className, strlen($nsPrefix . $ns));

			if(!$fileName)
				continue;

			$fileName = str_replace($ns, $ds, $fileName);

			foreach($directories as $directory) {

				/**
				 * Add a trailing directory separator if the user forgot to do that
				 */

				$fixedDirectory = rtrim($directory, $ds) . $ds;

				$filePath = $fixedDirectory . $fileName . '.php';

				/**
				 * This is probably a good path, let's check if the file exists
				 */

				if(is_file($filePath)) {

					/**
					 * Simulate a require
					 */

					require_once $filePath;

					/**
					 * Return true mean success
					 */

					return true;
				}
			}
		}

		/**
		 * Change the namespace separator by directory separator too
		 */

		$nsClassName = str_replace('\\', $ds, $className);

		/**
		 * Checking in directories
		 */

		$directories = $this->_directories;

		foreach($directories as $directory) {

			/**
			 * Add a trailing directory separator if the user forgot to do that
			 */

			$fixedDirectory = rtrim($directory, $ds) . $ds;

			/**
			 * Create a possible path for the file
			 */

			$filePath = $fixedDirectory . $nsClassName . '.php';

			/**
			 * Check in every directory if the class exists here
			 */

			if(is_file($filePath)) {

				/**
				 * Simulate a require
				 */

				require_once $filePath;

				/**
				 * Return true meaning success
				 */

				return true;
			}
		}

		/**
		 * Cannot find the class, return false
		 */

		return false;
	}

}