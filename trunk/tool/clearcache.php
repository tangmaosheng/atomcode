<?php
function deleteFiles($path, $del_dir = FALSE, $level = 0) {
	// Trim the trailing slash
	$path = rtrim($path, '\\/');
	
	if (!$current_dir = @opendir($path)) {
		return FALSE;
	}
	
	while (FALSE !== ($filename = @readdir($current_dir))) {
		if ($filename != "." and $filename != "..") {
			if (is_dir($path . DIRECTORY_SEPARATOR . $filename)) {
				// modify: delete all file(s) and folder(s) including hidden one(s).
				self::deleteFiles($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $level + 1);
			} else {
				unlink($path . DIRECTORY_SEPARATOR . $filename);
			}
		}
	}
	
	@closedir($current_dir);
	
	if ($del_dir == TRUE and $level > 0) {
		return @rmdir($path);
	}
	
	return TRUE;
}

deleteFiles('../application/cache');