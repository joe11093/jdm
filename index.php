<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: text/html;charset=ISO-8859-1");

ini_set("pcre.backtrack_limit", "50000000");
init_cache();

Global $obj;
$obj = new stdClass();
Global $rel;
$rel = array();
Global $rt;
$rt = array();
Global $ent;
$ent = array();
Global $def;
$def = array();
Global $term;
$term = "";
Global $useless_rt;
$useless_rt = [12,18,19,29,33,36,45,46,47,48,66,118,128,200,444,555,1000,1001,1002,2001];

//echo urlencode("https://ide.geeksforgeeks.org/");
$search_term = $_GET['term'];

//echo "search term: ".$search_term;
//echo $search_term;
//echo 'Plain    : ', urlencode( iconv("UTF-8", "ISO-8859-1", $search_term)), PHP_EOL;

serveFile($search_term);

function serveFile($search_term){
	global $obj;
	global $term;

	$start = microtime(true);

	if(cacheExistsAndValid($search_term)){
		//serve from cache
		$path = "cache/jsonCache/".$search_term.".json";
		//echo $path;
		$str_json = file_get_contents($path);
		//echo "Fetching from Cache.";
		echo $str_json;
	}
	else{
		$html_page = download_term($search_term);
		$html_page = utf8_encode($html_page);
		//echo $html_page;
		//$extract = getFileRelevantContent($html_page);
		//$extract = removeCommentsFromContent($extract);
		$extract = removeTags($html_page);
		$extract = removeEmptyLines($extract);
		extractData($extract);
		separateRelationsByType();
		//echo "Fetching from JDM.";
		echo json_encode($obj);
		saveToFile(json_encode($obj), "cache/jsonCache/".$term['name'].".json");
	}
	$time_elapsed_secs = microtime(true) - $start;
	//echo "time elapsed: ".$time_elapsed_secs;
	return;
}
function saveToFile($toSave, $path){

	file_put_contents($path,$toSave);
}

function download_term($term){
	$cURL = curl_init();
	//echo "Link: http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=". urlencode( iconv("UTF-8", "ISO-8859-1", $term))."&rel=";
	$setopt_array = array(CURLOPT_URL => "http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=".urlencode( iconv("UTF-8", "ISO-8859-1", $term))."&rel=",    CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array()); 
	curl_setopt_array($cURL, $setopt_array);
	$response_data = curl_exec($cURL);
	//echo($json_response_data);
	curl_close($cURL);
	return $response_data;
}

function init_cache(){
	if(!is_dir("cache")){
		//echo "creating cache";
		mkdir("cache");
		mkdir("cache/jsonCache");
	}
	else{
		//echo "Cache already exists";
	}
}

function getFileRelevantContent($html){
	preg_match('/<CODE>(.*?)<\/CODE>/s', $html, $matches);
	//print_r($matches[0]);
	return $matches[0];
}

function removeCommentsFromContent($text){
	$expr = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\)\/\/.*))/';
	return preg_replace($expr, "", $text);
}

function removeEmptyLines($text){
	return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
}

function removeTags($text){
	$expr = '/(<([^>]+)>)/';
	return preg_replace($expr, "", $text);
}

function startsWith($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function extractData($text){
	global $obj;
	global $rel;
	global $rt;
	global $ent;
	global $def;
	global $term;
	global $useless_rt;

	$separator = "\r\n";
	$line = strtok($text, $separator);


	$isFirst = true;

	while ($line !== false) {

		if(startsWith($line, "e;")){
			//echo $line;
			/*if($isFirst){
				$term = $line;
				$obj->term = $term;
				$isFirst = false;
			}*/
			$exploded = explode(';',$line);
			if(count($exploded)==5){
				//echo $exploded[2];
				$arr_e = array('lt' => $exploded[0], 'eid' => $exploded[1], 'name' => trim($exploded[2], "'"), 'type' => $exploded[3], 'w' => $exploded[4]);
				if($isFirst){
					$isFirst = false;
					$term = $arr_e;
					$obj->term = $arr_e;
				}
				$ent[$exploded[1]]=$arr_e;
			}
			else if(count($exploded)==6){
				$arr_e = array('lt' => $exploded[0], 'eid' => $exploded[1], 'name' => trim($exploded[5], "'"), 'type' => $exploded[3], 'w' => $exploded[4]);
				if($isFirst){
					$isFirst = false;
					$term = $arr_e;
					$obj->term = $arr_e;
				}
				$ent[$exploded[1]]=$arr_e;
			}
			//print_r($arr);
			//echo json_encode($arr, JSON_UNESCAPED_UNICODE)."\n";


		}
		else if(startsWith($line, "rt;")){
			//echo $line;
			
			$exploded = explode(';',$line);
			//echo $exploded[2];
			if(!in_array($exploded[1], $useless_rt)){
				$arr_rt = array('lt' => $exploded[0], 'rtid' => $exploded[1], 'trname' => $exploded[2], 'trgpname' => $exploded[3], 'rthelp' => $exploded[4]);
				array_push($rt, $arr_rt);
				//print_r($arr_rt);
				//echo json_encode($arr, JSON_UNESCAPED_UNICODE)."\n";
			}
		}
		else if(startsWith($line, "r;")){
			//echo $line;
			
			$exploded = explode(';',$line);

			if($exploded[2]==$term['eid'] && !in_array($exploded[4], $useless_rt)){
				$arr_r = array('lt' => $exploded[0], 'rid' => $exploded[1], 'node1' => $exploded[2], 'node2' => $exploded[3], 'type' => $exploded[4], 'w' => $exploded[5]);
				array_push($rel, $arr_r);
				//print_r($arr);
				//echo json_encode($arr, JSON_UNESCAPED_UNICODE)."\n";
		}
	}
		$line = strtok( $separator );
	}

	for($i=0;$i<count($rel);$i++){
		$rel[$i]['node1'] = $ent[$rel[$i]['node1']]['name'];
		$rel[$i]['node2'] = $ent[$rel[$i]['node2']]['name'];
	}
	usort($rel, function ($item1, $item2) {
		return $item2['w'] <=> $item1['w'];
	});

	$obj->rts = $rt;
	//print_r($rel);
	//echo json_encode($obj);
	//print_r($term);
	//print_r($obj->term);

}

function separateRelationsByType(){
	global $rt;
	global $rel;
	global $obj;

	foreach ($rt as $relType){
		$obj->{"rt_".$relType['rtid']} = array_values(array_filter($rel, function($var) use ($relType){
			return ($var['type'] == $relType['rtid']);
		}));
	}	
	
	//echo json_encode($obj);
}
function separateRelationsByDirection($rel){
	//create 2 arrays side the rel array
	//one for entrante and the other for sortants
}

function cacheExistsAndValid($term){
	$file_path = "cache/jsonCache/".$term.".json";
	if(file_exists($file_path)){
		$file_timestamp = filemtime($file_path);
		$file_date = date("F d Y H:i:s.", $file_timestamp);
		if($file_timestamp < strtotime('- 30 days')){
			//echo "File is old";
			return false;
		}
		else{
			//echo "File is not old";
			return true;
		}
	}
	else{
		//echo "File does not exist";
		return false;
	}
}

function alphaSortRelations($rel){

}
?>