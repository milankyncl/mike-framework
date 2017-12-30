<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike;

/**
 * Class Mike\Info
 */

class Info {

	/**
	 * Area where the version number is set. The format is as follows:
	 * ABBCCDE
	 *
	 * A - Major version
	 * B - Med version (two digits)
	 * C - Min version (two digits)
	 * D - Special release: 1 = Alpha, 2 = Beta, 3 = RC, 4 = Stable
	 * E - Special release version i.e. RC1, Beta2 etc.
	 */

	public static final function version() {

		return [0, 0, 1, 1, 0];

	}

}