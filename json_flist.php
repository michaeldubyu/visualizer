<?php

include("functions.php");
include("lib/dbconfig.php");

function get_profile_or_friends_list($userid, $update, $ret){
	//user may have inputted their community id - need steamid
	
	global $PROFILE_PATH,$FRIENDS_PATH;	

	$profile_path = "{$PROFILE_PATH}{$userid}_profile.xml"; //absolute path to profiles
	$profile_xml; 

	$friends_path  = "{$FRIENDS_PATH}{$userid}_friends.xml"; //absolute path to friends list
	$friends_xml;
	
	/*
	* $update is what will be loaded, or a new copy will be retrieved if there is no  
	* existing copy - if none is specified, both are updated
	*/
	switch($update){
		case "profile":
			if (is_numeric($userid) && file_exists($profile_path)){
				$profile_xml = simplexml_load_file($profile_path);
			}else{
				$profile_xml = get_steam_profile_xml($userid);
				if ($profile_xml!=null)	$profile_xml->asXML($profile_path);
			}
			break;
		case "friends"://must pass in numeric $userid!
			if (file_exists($friends_path)){
				$friends_xml = simplexml_load_file($friends_path);
			}else{	
				$friends_xml = get_steam_friends_xml($userid);
				if ($friends_xml!=null)	$friends_xml->asXML($friends_path);
			}
			break;
		default:
			$profile_xml = get_steam_profile_xml($userid);
			$userid = $profile_xml->steamID64;
			$friends_xml = get_steam_friends_xml($userid);	
		
			if ($profile_xml!=null)	$profile_xml->asXML($profile_path);
			if ($friends_xml!=null)	$friends_xml->asXML($friends_path);
			break;
	}

	if ($ret=='friends' && $friends_xml!=null) return $friends_xml;
	elseif ($ret=='profile' && $profile_xml!=null) return $profile_xml;
}


/*
*see if profile info exists already, if it doesn't, get it, then get friends list (can't cache this)
*foreach friend, check if steamid:name exists in db - else get their profile information - store into db mapping steamid:name
*/

if (isset($_GET['userid']) && $_GET['userid']!=null && isset($_GET['jobid']) && $_GET['jobid']!=null){

	$id = $_GET['userid'];
	$jobid = $_GET['jobid'];

	$mysqli = mysqli_connect($host,$username,$password,$db);
	if(mysqli_connect_errno()) echo mysqli_connect_error();
	mysqli_set_charset($mysqli,'utf8');
	mysqli_query($mysqli,"SET NAMES 'utf8'");
	
	$profile_xml = get_profile_or_friends_list($id,"profile","profile");
	$numerical_id = $profile_xml->steamID64;
	if (isset($profile_xml->steamID) && $profile_xml->privacyState=="public"){
		$self_name = simplexml_load_string($profile_xml->steamID->asXML(), null, LIBXML_NOCDATA);
		$friends_xml = get_profile_or_friends_list($numerical_id,"friends","friends"); 
		if (isset($friends_xml->friends)){
			$friends_list = array();	
			$friends_since = array();
			
			foreach ($friends_xml->friends->friend as $friend){
				$friends_list[] = (string)$friend->steamid;		
			}	 
			
			/*
			 *friends list now contains an array of steamids
			 *need to check db to see if there is a name, steamid mapping
			 */

			$friend_data = array();
			$self_data = array("steamid"=>(string)$numerical_id, "display_name"=>(string)$self_name);
			$friend_data[] = $self_data;

			/*
			*Insert job into db alerting that we're beginning to check friends.
			*/

			$start_time = time();
			$num_friends = count($friends_list);
			$progress = "INSERT INTO jobs (`id`,`progressed`,`total`,`start_time`,`complete_time`,`state`) VALUES ('$jobid','0','$num_friends','$start_time','0','0')";
			mysqli_query($mysqli,$progress);

			/*
			*Iterate over each friend and get their name from profile if it doesn't 
			*exist in the db.
			*/
			$i = 1;
			foreach($friends_list as $steamid){
				$q = "SELECT * FROM usernames WHERE steamid=$steamid";
				$re = mysqli_query($mysqli,$q);
				$rows = mysqli_num_rows($re);
			
				if ($rows==0){
					try{
						$profile_xml = get_profile_or_friends_list($steamid,"profile","profile");
					}catch (Exception $e){ $e->getMessage();}
					if ($profile_xml->status!='15' && $profile_xml!=null){
						$display_name = simplexml_load_string($profile_xml->steamID->asXML(), null, LIBXML_NOCDATA);
						$ins = "INSERT INTO usernames (`steamid`,`display_name`) VALUES ('$steamid','$display_name')";
						mysqli_query($mysqli,$ins);
						$new_data = array("steamid"=>(string)$steamid,"display_name"=>(string)$display_name);
						$friend_data[] = $new_data;
					}	
				}else{
					//does exist in db
					$friend_data[] = mysqli_fetch_assoc($re);	
				}
				$update_progress = "UPDATE jobs SET `progressed`='$i' WHERE `id`='$jobid'";
				mysqli_query($mysqli,$update_progress);
				$i++;
			}
			echo json_encode($friend_data);
		}else{
			//bad response from servers
			$bad_resp = array("steamid"=>"no_friend", "display_name"=>"no_friend");
			echo json_encode($bad_resp);
		}
	}else{
		//bad response from servers
		$bad_resp = array("steamid"=>"null", "display_name"=>"null");
		echo json_encode($bad_resp);
	}
}

?>
