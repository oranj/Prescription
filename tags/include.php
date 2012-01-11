<?php

function px_include($file, $dir = '', $px_innerHTML = NULL, $px_parentData = NULL) {
	$dir = DIR_FS_CATALOG. $dir;

	if (! file_exists($dir.$file)) {
		error_log('File "'.$dir.$file.'" does not exist');
	}

	extract($GLOBALS);

	ob_start();
	require_once($dir.$file);
	$text = ob_get_clean();

	return $text;
}