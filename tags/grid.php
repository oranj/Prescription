<?php

function px_grid($cols = 12, $gutter = 10, $width=960, $px_innerHTML = '') {
	return '<div class="px_grid" style="width:'.$width.'px;margin:0px auto;">'.$px_innerHTML.'</div>';
}