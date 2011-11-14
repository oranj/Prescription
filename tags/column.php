<?php

function px_column($name = "", $span=12, $push = 0, $px_innerHTML = '',$pos="", $px_parentData = NULL) {

#	dump(func_get_args());

	$full_width = $px_parentData[0]['width'];
	$total_cols = $px_parentData[0]['cols'];
	$gutter = $px_parentData[0]['gutter'];

	$col_width = $full_width / $total_cols;

	$push_width = $col_width * $push;

	$total_width = $col_width * $span;



	return '<div class="px_grid_gutter" '.($name?'id="'.$name.'"':'').' style="margin-left:'.$push_width.'px;width:'.$total_width.'px; float:left;"><div class="px_grid_column px_grid_'.$span.'" style="margin:'.$gutter.'px;">'.$px_innerHTML.'</div></div>'.($pos=='omega'?'<div style="clear:both"></div>':'');//dump($px_parentData, true);
}