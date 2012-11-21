<?php

function get_profile_or_friends_list($userid, $ret){
	//user may have inputted their community id - need steamid
	$profile_path = "{$PROFILE_PATH}{$userid}_profile.xml"; //absolute path to profiles
	$profile_xml; 

	$friends_path  = "{$FRIENDS_PATH}{$userid}_friends.xml"; //absolute path to friends list
	$friends_xml;
	
	//get their profile info
	if (is_numeric($userid) && file_exists($profile_path) && file_exists($friends_path)){
		//no savings on input being non-numeric because then a request is required
		$profile_xml = simplexml_load_file($profile_path); 	
		$friends_xml = simplexml_load_file($friends_path);
	}else{
		$profile_xml = get_steam_profile_xml($userid);
		$numeric_id = $profile_xml->steamID64;
		$userid = $numeric_id;
		$friends_xml = get_steam_friends_xml($userid);
		
		$profile_path = "{$PROFILE_PATH}{$userid}_profile.xml";
		$friends_path = "{$FRIENDS_PATH}{$userid}_friends.xml";

		$profile_xml->asXML($profile_path);
		$friends_xml->asXML($friends_path);
	}
	if ($ret=='friends') return $friends_xml;
	else return $profile_xml;
}


/*
*see if profile info exists already, if it doesn't, get it, then get friends list (can't cache this)
*foreach friend, check if steamid:name exists in db - else get their profile information - store into db mapping steamid:name
*/

if (isset($_GET['userid']) && $_GET['userid']!=null){

	include_once("functions.php");
	include_once("lib/dbconfig.php");

	$id = $_GET['userid'];
	
	$friends_xml = get_profile_or_friends_list($id, "friends"); 
	$friends_list = array();	
	$friends_since = array();

	foreach ($friends_xml->friends->friend as $friend){
		$friends_list[] = (string)$friend->steamid;		
		//not in use at the moment $friends_since[(string)$friend->steamid] = (string)$friend->friend_since; 
	}	 
	
	/*
	 *friends list now contains an array of steamids
	 *need to check db to see if there is a name, steamid, friends_since mapping for each one that exists
	 */

	foreach($friends_list as $steamid){
		$mysqli = mysqli_connect($host,$username,$password,$db);
		if(mysqli_connect_errno()) echo mysqli_connect_error();

		$q = "SELECT * FROM usernames WHERE steamid=$steamid";
		$rows = mysqli_num_rows(mysqli_query($mysqli,$q));
		if ($rows==0){
			$profile_xml = get_profile_or_friends_list($steamid);
			var_dump($profile_xml);	
			$q = "INSERT INTO usernames (`steamid`,`display_name`) VALUES (
		}
	}
}

?>
