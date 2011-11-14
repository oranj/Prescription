<?php

function px_css($src, $media = "screen") {
	return '<link rel="stylesheet" type="text/css" href="css/'.$src.'" media="'.$media.'" />';
}
