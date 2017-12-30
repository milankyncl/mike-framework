<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Http\Request;


class File {

	protected $_name;


	protected $_tmp;


	protected $_size;


	protected $_type;


	protected $_realType;

	/**
	 * @var string|null
	 */
	protected $_error;

	/**
	 * @var string|null
	 */
	protected $_key;

	/**
	 * @var string
	 */
	protected $_extension;


	/**
	 * @return string|null
	 */
	public function getError() {}

	/**
	 * @return string|null
	 */
	public function getKey() {}

	/**
	 * @return string
	 */
	public function getExtension() {}

	/**
	 * Mike\Http\Request\File constructor
	 *
	 * @param array $file
	 * @param mixed $key
	 */
	public function __construct(array $file, $key = null) {}

	/**
	 * Returns the file size of the uploaded file
	 *
	 * @return int
	 */
	public function getSize() {}

	/**
	 * Returns the real name of the uploaded file
	 *
	 * @return string
	 */
	public function getName() {}

	/**
	 * Returns the temporary name of the uploaded file
	 *
	 * @return string
	 */
	public function getTempName() {}

	/**
	 * Returns the mime type reported by the browser
	 * This mime type is not completely secure, use getRealType() instead
	 *
	 * @return string
	 */
	public function getType() {}

	/**
	 * Gets the real mime type of the upload file using finfo
	 *
	 * @return string
	 */
	public function getRealType() {}

	/**
	 * Checks whether the file has been uploaded via Post.
	 *
	 * @return bool
	 */
	public function isUploadedFile() {}

	/**
	 * Moves the temporary file to a destination within the application
	 *
	 * @param string $destination
	 * @return bool
	 */
	public function moveTo($destination) {}

}
