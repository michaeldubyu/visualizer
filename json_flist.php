<?php
//see if profile info exists already, if it doesn't, get it, then get friends list (can't cache this)
//foreach friend, check if steamid:name exists in db - else get their profile information - store into db mapping steamid:name

if (isset($_GET['userid']) && $_GET['userid']!=null){

	include_once("functions.php");
	include_once("lib/dbconfig.php");

	$id = $_GET['userid'];
	
	//user may have inputted their community id - need steamid
	$profile_path = "{$PROFILE_PATH}{$id}_profile.xml"; //absolute path to profiles
	$profile_xml; 

	$friends_path  = "{$FRIENDS_PATH}{$id}_friends.xml"; //absolute path to friends list
	$friends_xml;
	
	//get their profile info
	if (is_numeric($id) && file_exists($profile_path) && file_exists($friends_path)){
		//no savings on input being non-numeric because then a request is required
		$profile_xml = simplexml_load_file($profile_path); 	
		$friends_xml = simplexml_load_file($friends_path);
	}else{
		$profile_xml = get_steam_profile_xml($id);
		$numeric_id = $profile_xml->steamID64;
		$id = $numeric_id;
		$friends_xml = get_steam_friends_xml($id);
		
		$profile_path = "{$PROFILE_PATH}{$id}_profile.xml";
		$friends_path = "{$FRIENDS_PATH}{$id}_friends.xml";

		$profile_xml->asXML($profile_path);
		$friends_xml->asXML($friends_path);
	}

	var_dump($friends_xml);
	
		
	

}

?>
