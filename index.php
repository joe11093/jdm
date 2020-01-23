<?php
require_once("paginate.php");
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
Global $sort;
$sort = "weight";
Global $useless_rt;
$useless_rt = [12,18,19,29,33,36,45,46,47,48,66,118,128,200,444,555,1000,1001,1002,2001];

$search_term = $_GET['term'];

if (isset($_GET['sort']))
 	$sort = $_GET['sort'];

serveFile($search_term);

function serveFile($search_term){
	global $obj;
	global $term;

	$start = microtime(true);

	if(cacheExistsAndValid($search_term)){
		//serve from cache
		$path = "cache/jsonCache/".$search_term.".json";
		$json = json_decode(file_get_contents($path));
		$json = json_encode(getInitialPages($json));
	}
	else{
		$html_page = download_term($search_term);
		$html_page = utf8_encode($html_page);

		if (!termExists($html_page)){
			echo termExistsError($search_term);
			return;
		}

		$extract = removeTags($html_page);
		$extract = removeEmptyLines($extract);
		extractData($extract);
		separateRelationsByType();
		saveToFile(json_encode($obj), "cache/jsonCache/".$term['name'].".json");
		$json = json_encode(getInitialPages(json_decode(json_encode($obj))));
	}

	echo $json;
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

function isDefinition($line){
	return preg_match("/^\d+\..+(?:<br(\s)?(\/)?>)?/", $line);
}

function extractDefinition($line){
	$matches = [];
	preg_match("/^(\d+)\.(.+)(?:<br(?:\s)?(?:\/)?>)?/", $line, $matches);
	return $matches;
}

function extractData($text){
	global $obj;
	global $rel;
	global $rt;
	global $ent;
	global $def;
	global $term;
	global $sort;
	global $useless_rt;

	$separator = "\r\n";
	$line = strtok($text, $separator);
	$isFirst = true;

	while ($line !== false) {

		if (isDefinition($line)){
			$definition = extractDefinition($line);
			$arr_d = ['number' => $definition[1], 'content' => trim($definition[2])];
			array_push($def, $arr_d);
		}

		else if(startsWith($line, "e;")){
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
		}

		else if(startsWith($line, "rt;")){
			$exploded = explode(';', $line);

			if(!in_array($exploded[1], $useless_rt)){
				$arr_rt = array('lt' => $exploded[0], 'rtid' => $exploded[1], 'trname' => $exploded[2], 'trgpname' => $exploded[3], 'rthelp' => $exploded[4]);
				array_push($rt, $arr_rt);
			}
		}

		else if(startsWith($line, "r;")){
			$exploded = explode(';',$line);
			$w = $exploded[5];

			if ($w < 0) {
				settype($w, "int");
				$wt = "n";
				$w *= -1;
				$w = strval($w);
			}
			else
				$wt = "p";

			if($exploded[2]==$term['eid'] && !in_array($exploded[4], $useless_rt)){
				$arr_r = array('lt' => $exploded[0], 'rid' => $exploded[1], 'node1' => $exploded[2], 'node2' => $exploded[3], 'type' => $exploded[4], 'w' => $w, 'wt' => $wt);
				array_push($rel, $arr_r);
			}
		}
		$line = strtok($separator);
	}

	$obj->defs = new stdClass();
	$obj->defs->count = count($def);
	$obj->defs->definitions = $def;

	for($i = 0; $i < count($rel); $i++) {
		$rel[$i]['node1'] = $ent[$rel[$i]['node1']]['name'];
		$rel[$i]['node2'] = $ent[$rel[$i]['node2']]['name'];
	}

	if ($sort == "weight")
		weightSortRelations();
	else
		alphaSortRelations();

	$obj->rts = $rt;
}

function separateRelationsByType(){
	global $rt;
	global $rel;
	global $obj;

	foreach ($rt as $relType){
		$relations = array_values(array_filter($rel, function($var) use ($relType){
			return ($var['type'] == $relType['rtid']);
		}));

		$obj->{"rt_".$relType['rtid']} = new stdClass();
		$obj->{"rt_".$relType['rtid']}->count = count($relations);
		$obj->{"rt_".$relType['rtid']}->relations = $relations;
	}
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

function termExists($html_page){
	return preg_match("/<([\s\/])?CODE>/i", $html_page);
}

function termExistsError($term){
	$obj = new stdClass();
	$obj->error = 0;
	$obj->message = "the term $term doesn't exist";

	return json_encode($obj);
}

function weightSortRelations(){
	global $rel;

	usort($rel, function ($item1, $item2) {
		return $item2['w'] <=> $item1['w'];
	});
}

function alphaSortRelations(){
	global $rel;

	$previousLocale = setlocale(LC_ALL, 0);
	if (setlocale(LC_ALL, "fr_FR.utf8") !== false) {
		usort($rel, function ($item1, $item2) {
			global $term;
			if (strtolower($item1['node1']) == strtolower($term['name'])) // exiting relations
				return strcoll(strtolower($item1['node2']), strtolower($item2['node2']));
			else // entering relations
				return strcoll(strtolower($item1['node1']), strtolower($item2['node1']));
		});

		setlocale(LC_ALL, $previousLocale);
	}
	else
	  echo "error: failed to sort alphabetically <br/>";
}
?>
