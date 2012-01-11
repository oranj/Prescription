<?php


function px_rssreader($url = 'http://rss.cnn.com/rss/cnn_topstories.rss', $posts=5, $words=25, $skipfirst=false, $endwith='') {
	if (! $posts) {
		$posts = 5;
	}
	if (! $words){
		$words = 5;
	}
	
	 $feed_number = $posts;
	 $wordsize = $words;


	 $cache_dir = dirname(__FILE__).'/../cache/';
	 $cache_name = 'rss_'.md5(serialize(func_get_args()).date('YmdH')).'.xml';

	 if (! file_exists($cache_dir.$cache_name)) {

	 	$string = file_get_contents($url);
		$library = simplexml_load_string($string);


		 $i = 1;

		 foreach($library->channel->item as $item) {
		 	if($i <= $feed_number) {

				$find = array("\n \n","\r \r","\n\n","\r\r","\n","\r","  ");
				$replace = array("\n","\r","\n","\r",' ',' ',' ');

				$words = explode(" ",str_replace($find,$replace,$item->description));
				$words = array_slice($words,$skipfirst);
				array_splice($words,$wordsize);

				if(!isset($params['style'])) {
					$return .= '<a href="'.$item->link.'" target="_blank" style="font-weight: strong;">'.$item->title.'</a><br />';
					$return .= implode(" ",$words).$endwith.'<br /><br />';
				} else {
					$key = array('%link%','%title','%description%');
					$value = array($item->link, $item->title, implode(" ",$words).$endwith);
					$return .= str_replace($key,$value,$params['style']);
				}

				$i++;
			} else {
				break;
			}
		}

		$file = fopen($cache_dir.$cache_name, "w");
		fwrite($file, $return);
		fclose($file);
	 } else {
	 	$return = file_get_contents($cache_dir.$cache_name);
	 }


	 return $return;
}
