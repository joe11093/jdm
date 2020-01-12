<?php

function getInitialPages($obj){
	$json = new stdClass();
	$json->term = $obj->term;
	$json->rts = $obj->rts;
	$json->defs = new stdClass();
	$json->defs->count = $obj->defs->count;
	$json->defs->definitions = getDefinitionsForPage($obj, 1, 5);

	foreach ($obj->rts as $rt){
		$json->{"rt_".$rt->rtid} = new stdClass();
		$json->{"rt_".$rt->rtid}->count = $obj->{"rt_".$rt->rtid}->count;
		$json->{"rt_".$rt->rtid}->relations = getRelationsForPage($obj, $rt->rtid, 1, 5);
	}

	return $json;
}

function getRelationsForPage($obj, $type, $page, $per_page){
	$offset = ($page - 1) * $per_page;

	$upper = min($per_page + $offset, count($obj->{"rt_".$type}->relations));

	$relations = [];
	for ($i = $offset; $i < $upper; $i++)
		array_push($relations, $obj->{"rt_".$type}->relations[$i]);

	return $relations;
}

function getDefinitionsForPage($obj, $page, $per_page){
	$offset = ($page - 1) * $per_page;

	$upper = min($per_page + $offset, count($obj->defs->definitions));

	$definitions = [];
	for ($i = $offset; $i < $upper; $i++)
		array_push($definitions, $obj->defs->definitions[$i]);

	return $definitions;
}

if(isset($_GET['term']) && isset($_GET['page']) && isset($_GET['per_page']) && isset($_GET['criterion'])){
	$search_term = $_GET['term'];
	$page = $_GET['page'];
	$per_page = $_GET['per_page'];
	$criterion = $_GET['criterion'];

	$path = "cache/jsonCache/".$search_term.".json";
	$json = json_decode(file_get_contents($path));

	if($criterion == "definition"){
		$definitions = json_encode(getDefinitionsForPage($json, $page, $per_page));
		echo $definitions;
	}

	else if ($criterion == "relation"){
		if(isset($_GET['type'])){
			$type = $_GET['type'];
			$relations = json_encode(getRelationsForPage($json, $type, $page, $per_page));
			echo $relations;
		}
	}
}
