<?php

	class PX {

		private static $manifest   = null;
		private static $cache_dir  = null;
		private static $valid_attr = "[a-zA-Z0-9_]+";   // regex for a valid attribute name

		/**
		* @desc	Given a tag name, attribute string, inner_html, and possible parentdata, processes a tag by the rules defined in the tags/ directory'
		*/
		static function tag($tag_name, $attributes, $inner_html = NULL, $parentData = NULL) {
			$manifest = self::get_manifest();
			if (isset($manifest[$tag_name])) {
				require_once(dirname(__FILE__).'/tags/'.$tag_name.'.php');

				$regexs = Array(
					'numeric'        => '/('.self::$valid_attr.')\s*\=\s*([0-9\.]+)/si',
					'encsinglequote' => '/('.self::$valid_attr.')\s*\=\s*&#39;(.*?[^\\\\]?)&#39;/si',
					'singlequote'    => '/('.self::$valid_attr.')\s*\=\s*\'(.*?[^\\\\]?)\'/si',
					'encdoublequote' => '/('.self::$valid_attr.')\s*\=\s*&quot;(.*?[^\\\\]?)&quot;/si',
					'doublequote'    => '/('.self::$valid_attr.')\s*\=\s*\"(.*?[^\\\\]?)\"/si',
					'jsonarray'      => '/('.self::$valid_attr.')\s*\=\s*(\[.*?[^\\\\]?\])/si',
					'jsonobject'     => '/('.self::$valid_attr.')\s*\=\s*(\{.*?[^\\\\]?\})/si',
					'bool'           => '/('.self::$valid_attr.')\s*\=\s*(true|false|null)/si',
					'const'          => '/('.self::$valid_attr.')\s*\=\s*([A-Z_][A-Z0-9_]+)/s',
					'var'            => '/('.self::$valid_attr.')\s*\=\s*\$([a-zA-Z_]'.self::$valid_attr.')/s',
				);

				// Find the defaults as defined by the manifest.
				$params = isset($manifest[$tag_name]['defaults'])?$manifest[$tag_name]['defaults']:Array();

				$bool_values = Array('true'=>true, 'false'=>false, 'null'=>null);

				foreach ($regexs as $type => $regex) {
					if (preg_match_all($regex, $attributes, $regex_attrs)) {
						foreach ($regex_attrs[0] as $index => $buffer) {
							$val = $regex_attrs[2][$index];
							switch($type) {
								case 'numeric':
									$val = (float)$val;
									break;
								case 'var':
									global $$val;
									$val = $$val;
									break;
								case 'jsonarray':
								case 'jsonobject':
									$val = json_decode(str_replace("&quot;", "\"", $val), true);
									break;
								case 'const':
									$val = constant($val);
									break;
								case 'bool':
									$val = $bool_vals[strtolower($val)];
									break;
								default:
									break;
							}

							$params[$regex_attrs[1][$index]] = $val;
						}
					}
				}

				$input = Array();

				if (isset($manifest[$tag_name]['params']['px_innerHTML']) && ! is_null($inner_html)) {
					$thisAndParentData = $parentData?$parentData:Array();
					array_unshift($thisAndParentData, $params);
					$params['px_innerHTML'] = self::run($inner_html, $thisAndParentData);
				} if (isset($manifest[$tag_name]['params']['px_parentData']) && ! is_null($parentData)) {
					$params['px_parentData'] = $parentData;
				}

				// Put the parameters in the correct order to match up with the tag functions.
				foreach ($manifest[$tag_name]['params'] as $param => $order) {
					$input[] = isset($params[$param])?$params[$param]:NULL;
				}

				$func = 'px_'.$tag_name;
				if(! function_exists($func)) {
					error_log('Function does not exist: '.$func);
				}

				$px_out = call_user_func_array('px_'.$tag_name, $input);
				return $px_out;
			} else {
				return '(px:'.$tag_name.' '.$attributes.')<div class="px_invalid">Invalid PX Tag: '.$tag_name.'</div>'.$inner_html.'(/px:'.$tag_name.')';
			}
		}


		/**
		*	@desc Parse the input HTML by processing tags in the px:namespace as defined by functions in the tags/ directory
		*/
		static function run($html, $parentData = NULL) {

			// Finds all opening / self_closing nodes in the px namespace;
			$html_preg = '/\(px\:('.self::$valid_attr.')\s?([^\)]*?)(\/?)\)/si';

			$limit = -1;//10; // Used for debugging purposes to ensure no infinite loops;

			$offset = 0;

			$output = '';

			while (preg_match($html_preg, substr($html, $offset), $matches, PREG_OFFSET_CAPTURE) && $limit) {

				$output .= $str = substr($html, $offset, $matches[0][1]);

				$offset += $matches[0][1] + strlen($matches[0][0]);

				$self_closes = (BOOL)$matches[3][0];
				$px_tag = $matches[1][0];
				$inner_html = NULL;

				// If we should be looking for a closing tag
				if (! $self_closes) {
					$close_preg = '/\(\/\s*px\:'.$px_tag.'\s*\)/si';

					if (preg_match($close_preg, substr($html, $offset), $close_matches, PREG_OFFSET_CAPTURE)) {
						$inner_html = substr($html, $offset, $close_matches[0][1]);
						$offset += $close_matches[0][1] + strlen($close_matches[0][0]);
					} else {
						// If we can't find one, treat it as though it was self closing.
						$self_closes = true;
#						error_log('Could not find closing tag for px:'.$px_tag.'; assuming self closing tag', E_NOTICE);
					}
				}

				// Process the tag and output the html;
				$output .= self::tag($px_tag, $matches[2][0], $inner_html, $parentData);

				$limit--;
			}
			$output .= substr($html, $offset);
			return $output;
		}

		/**
		*	@desc Gets data about the known tags. If the manifest file does not exist, generates a new file.
		*/
		static function get_manifest() {
			$___PX_MANIFEST = self::$manifest;

			if (! $___PX_MANIFEST) {
				// Gets the name of the manifest- respective to the current state of the tags directory
				$filename = self::get_manifest_name();
				$px_dir = dirname(__FILE__);
				$fullpath = ((self::$cache_dir != null) ? self::$cache_dir : $px_dir.'/cache/') . $filename;

				if (file_exists($fullpath)) {
					// If the manifest file exists, return its contents (json_decoded)
					$___PX_MANIFEST = json_decode(file_get_contents($fullpath), true);
					if (is_null($___PX_MANIFEST)) {
						error_log('Could not decode cached manifest file');
					}
				} else {
					// If it doesn't, build a new maniefest file.
					$___PX_MANIFEST = Array();
					$handle = fopen($fullpath, 'w');

					$strs = Array('false'=>false, 'true'=>true, 'null'=>NULL);

					$tags = scandir($px_dir.'/tags/');
					foreach ($tags as $tag_file) {
						$tag = str_replace('.php', '', $tag_file);
						$tag_path = $px_dir.'/tags/'.$tag_file;
						if (! is_dir($tag_path)) {
							// This gets all parameters in the function declaration
							$preg = '/function\s+px\_'.$tag.'\s*\((.*?)\)\s*\{/si';

							$file_contents = file_get_contents($tag_path);
							if (preg_match($preg, $file_contents, $function_match)) {

								// These get the parameters and defaults in the function declaration, allowing for json, string, true, false, null, or numerical values
								$regexs = Array(
									'numeric'        => '/\&?\$('.self::$valid_attr.')\s*\=\s*([0-9\.]*)/si',
									'singlequote'    => '/\&?\$('.self::$valid_attr.')\s*\=\s*\'(.*?[^\\\\]?)\'/si',
									'encsinglequote' => '/\&?\$('.self::$valid_attr.')\s*\=\s*&#39;(.*?[^\\\\]?)&#39;/si',
									'doublequote'    => '/\&?\$('.self::$valid_attr.')\s*\=\s*\"(.*?[^\\\\]?)\"/si',
									'encdoublequote' => '/\&?\$('.self::$valid_attr.')\s*\=\s*&quot\;(.*?[^\\\\]?)&quot;/si',
									'jsonarray'      => '/\&?\$('.self::$valid_attr.')\s*\=\s*\[(.*?[^\\\\]?)\]/si',
									'jsonobject'     => '/\&?\$('.self::$valid_attr.')\s*\=\s*\{(.*?[^\\\\]?)\}/si',
									'bool'           => '/\&?\$('.self::$valid_attr.')\s*\=\s*(true|false|null)/si',
									'const'          => '/\&?\$('.self::$valid_attr.')\s*\=\s*([A-Z_][A-Z0-9_]+)/s',
									'var'            => '/\&?\$('.self::$valid_attr.')\s*\=\s*\$([a-zA-Z_]'.self::$valid_attr.')/s',
								);

								if (preg_match_all('/\$('.self::$valid_attr.')/si', $function_match[1], $param_match)) {
									$___PX_MANIFEST[$tag]['params'] = array_flip($param_match[1]);

									foreach ($regexs as $type => $regex) {
										if (preg_match_all($regex, $function_match[1], $regex_params)) {

											foreach ($regex_params[1] as $index => $key) {
												$val = $regex_params[2][$index];
												switch($type) {
													case 'bool':
														$val = $strs[strtolower($val)];
														break;
													case 'numeric':
														$val = (float)$val;
														break;
													case 'jsonarray':
													case 'jsonobject':
														$val = json_decode($val);
														break;
													case 'var':
														$val = $$val;
														break;
													case 'const':
														$val = constant($val);
														break;
													case 'singlequote':
													case 'doublequote':
													case 'encdoublequote':
													default:
														break;
												}
												$___PX_MANIFEST[$tag]['defaults'][$key] = $val;
											}
										}
									}
								}
							}
						}
					}

					fwrite($handle, json_encode($___PX_MANIFEST));
					fclose($handle);
				}
			}

			self::$manifest = $___PX_MANIFEST;
			return $___PX_MANIFEST;
		}

		/**
		* 	@desc: Generates a unique manifest name-
		*	@TODO: regenerate once an inode has changed, rather than by filename
		*/

		static function get_manifest_name() {
			return 'px_manifest_'.md5(join('', scandir(dirname(__FILE__).'/tags/'))).'.json';
		}

		/**
		*	@desc: runs over a provided template file.
		*/
		static function template($filename) {
			$file = DIR_WS_INCLUDES.'templates/'.$filename;
			if (! file_exists($file)) {
				error_log('Could not find templated file "'.$filename.'"');
			}
			$html = file_get_contents($file);
			$html = self::run($html);
			return $html;
		}

		/**
		* @desc set cache directory if said directory is writeable
		* @returns bool   true if cache gets set
		*/
		static function set_cache_dir($directory) {
			if ( substr($directory, -1) != '/' ) { $directory .= '/'; }

			$is_dir_is_writable = (is_dir($directory) && is_writable($directory));
			if ( $is_dir_is_writable ) self::$cache_dir = $directory;

			return $is_dir_is_writable;
		}
	}
