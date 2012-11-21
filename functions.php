<?php 

$key = "E952EF69C4972394EF63800AC3F40C07";


function download_page($path)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$path);
    curl_setopt($ch, CURLOPT_FAILONERROR,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $retValue = curl_exec($ch);                      
    curl_close($ch);
    return $retValue;
}

function get_steam_profile_xml($id)
{
    $pageNumeric = "http://steamcommunity.com/profiles/$id/?xml=1&l=en";
    $pageAlpha = "http://steamcommunity.com/id/$id/?xml=1&l=en";

    $id = trim($id);
    
    if (is_numeric($id)) $sXML = download_page($pageNumeric);
    else $sXML = download_page($pageAlpha);
    
    if ($sXML==false) return null;

    $oXML = new SimpleXMLElement($sXML);
  
    if ($oXML!=null)return $oXML;
    else return null;  
}

//returns the friends list of a user
function get_steam_friends_xml($id)
{
    global $key;
    
    $tempid = trim($id);
    $friendsReq = "http://api.steampowered.com/ISteamUser/GetFriendList/v0001/?key=$key&steamid=$id&relationship=friend&format=xml";
    
    $friends_xml = download_page($friendsReq);
    if ($friends_xml == false) return null;
    
    $friends_list = new SimpleXMLElement($friends_xml);
    
    if ($friends_list!=null)return $friends_list;
    else return null;            
}

function get_player_status($id)
{
    $tempid = trim($id);
    $oXML = get_steam_profile_xml($tempid);

	if ($oXML->steamID64 != null || $oXML->steamID64 != '') return $oXML->stateMessage;
    else return null;
}

function get_steam_id_64($id) //get steam64id from communityid, calls get_steam_profile_xml() to get profile first
{
    $tempid = trim($id);
    $oXML = get_steam_profile_xml($tempid);
    
    if ($oXML->steamID64 != null || $oXML->steamID64 != '') return $oXML->steamID64;
    else return null;
}

function get_tf2_backpack_xml($id)
{
    global $key;
    
    $tempid = trim($id);
	$backpackURL = "http://api.steampowered.com/IEconItems_440/GetPlayerItems/v0001/?key=$key&steamid=$tempid&format=xml&language=en";
	$sXML = download_page($backpackURL);
    
    if ($sXML == null) return null;
    $oXML = new SimpleXMLElement($sXML);

    return $oXML;    
}

function get_tf2_schema_xml($id)
{
    global $key;
    
    $tempid = trim($id);
	$schemaURL = "http://api.steampowered.com/IEconItems_440/GetSchema/v0001/?key=$key&format=xml&language=en";

	$sXML = download_page($schemaURL);
    $oXML = new SimpleXMLElement($sXML);

    return $oXML;  
}

function get_tf2_allitem_node($xml,$node)
{ //returns array of all the desired nodes values by the specified $node and xml from $xml
	$nodes = array();
    if ($xml->items->count()!=0)
    {
        foreach ($xml->items->item as $target)
        {
            $nodes[] = (string)$target->$node;
        }
        return $nodes;	
    }
    else return null;
}

function map_tf2_allitem_node($xml,$identifiername,$keyidentifiers,$value)
{ //returns array of all the desired nodes values by the specified $node and xml from $xml
	$nodes = array();

	foreach ($xml->items->item as $target)
	{
		foreach ($keyidentifiers as $keys)
		{
			if ($keys==(string)$target->$identifiername) 
			{
				$nodes[$keys] = (string)$target->$value;
				break(1);
			}
		}
	}
	return $nodes;	
}

function tf2_get_quality($quality)
{
	if ($quality=='0') return "stock";
	elseif ($quality=='1') return "genuine";
	elseif ($quality=='2') return ""; //unused
	elseif ($quality=='3') return "vintage";
	elseif ($quality=='4') return ""; //unused
	elseif ($quality=='5') return "unusual";
	elseif ($quality=='6') return "unique";
	elseif ($quality=='7') return "community";
	elseif ($quality=='8') return "valve";
	elseif ($quality=='9') return "self-made";
	elseif ($quality=='10') return ""; //unused
	elseif ($quality=='11') return "strange";
	elseif ($quality=='12') return ""; //unused
	elseif ($quality=='13') return "haunted";
	else return "";
}

function tf2_get_strange_kill_rank($kill_count)
{
	if ($kill_count>='0' && $kill_count<'10') return "strange";
	elseif ($kill_count>='10' && $kill_count<'25') return "unremarkable";
	elseif ($kill_count>='25' && $kill_count<'45') return "scarcely lethal"; 
	elseif ($kill_count>='45' && $kill_count<'70') return "mildly menacing";
	elseif ($kill_count>='70' && $kill_count<'100') return "somewhat threatening"; 
	elseif ($kill_count>='100' && $kill_count<'135') return "uncharitable";
	elseif ($kill_count>='135' && $kill_count<'175') return "notably dangerous";
	elseif ($kill_count>='175' && $kill_count<'225') return "sufficiently lethal";
	elseif ($kill_count>='225' && $kill_count<'275') return "truly feared";
	elseif ($kill_count>='275' && $kill_count<'350' ) return "spectacularly lethal";
	elseif ($kill_count>='350' && $kill_count<'500') return "gore-spattered"; 
	elseif ($kill_count>='500' && $kill_count<'750') return "wicked nasty";
	elseif ($kill_count>='750' && $kill_count<'999') return "positively inhumane"; 
	elseif ($kill_count==999) return "totally ordinary";
	elseif ($kill_count>='1000' && $kill_count<'1500') return "face melting";     
	elseif ($kill_count>='1500' && $kill_count<'2500') return "rage-inducing"; 
	elseif ($kill_count>='2500' && $kill_count<'5000') return "server-clearing";
	elseif ($kill_count>='5000' && $kill_count<'7500') return "epic"; 
	elseif ($kill_count>='7500' && $kill_count<'7616') return "legendary";
    elseif ($kill_count>='7616' && $kill_count<'8500') return "austrailian";
	elseif ($kill_count>= '8500') return "hale's own"; 
	else return "";    
}

function tf2_get_hex_to_paint_name($paint)
{
	if ($paint=="#2F4F4F") return "A Color Similar to Slate	";
	elseif ($paint=="#7D4071") return "A Deep Commitment to Purple";
	elseif ($paint=="#141414") return "A Distinctive Lack of Hue";
	elseif ($paint=="#BCDDB3") return "A Mann's Mint";
	elseif ($paint=="#2D2D24") return "After Eight";
	elseif ($paint=="#7E7E7E") return "Aged Moustache Grey";
	elseif ($paint=="#E6E6E6") return "An Extraordinary Abundance of Tinge";
	elseif ($paint=="#E7B53B") return "Australium Gold";
	elseif ($paint=="#D8BED8") return "Color No. 216-190-216";
	elseif ($paint=="#E9967A") return "Dark Salmon Injustice";
	elseif ($paint=="#808000") return "Drably Olive";
	elseif ($paint=="#729E42") return "Indubitably Green";
	elseif ($paint=="#CF7336") return "Mann Co. Orange";
	elseif ($paint=="#A57545") return "Muskelmannbraun";
	elseif ($paint=="#FF69B4") return "Pink as Hell";
	elseif ($paint=="#51384A") return "Noble Hatter's Violet";
	elseif ($paint=="#C5AF91") return "Peculiarly Drab Tincture";
	elseif ($paint=="#694D3A") return "Radigan Conagher Brown";
	elseif ($paint=="#32CD32") return "The Bitter Taste of Defeat and Lime";
	elseif ($paint=="#F0E68C") return "The Color of a Gentlemann's Business Pants";
	elseif ($paint=="#7C6C57") return "Ye Olde Rustic Colour";
	elseif ($paint=="#424F3B") return "Zepheniah's Greed";
	elseif ($paint=="#654740" || $paint=="#28394D") return "An Air of Debonair";
	elseif ($paint=="#3B1F23" || $paint=="#18233D") return "Balaclavas are Forever";
	elseif ($paint=="#C36C2D" || $paint=="#B88035") return "Cream Spirit";
	elseif ($paint=="#483838" || $paint=="#384248") return "An Air of Debonair";
	elseif ($paint=="#B8383B" || $paint=="#5885A2") return "Operator's Overalls";
	elseif ($paint=="#803020" || $paint=="#256D8D") return "The Value of Teamwork";
	elseif ($paint=="#A89A8C" || $paint=="#839FA3") return "Waterlogged Lab Coat";
}

function itemmap_filter_defindex_and_node($xml,$identifiername,$identifierarray,$target)
{//pass in xml doc, create an array mapping of $key($identifiername) and $target using $identifierarray to build this
	$itemmap = array();
	foreach ($identifierarray as $indexes)
	{//go through all items, look up corresponding xml attribute for all defindexes
		foreach ($xml->items->item as $element)
		{
			if ($element->$identifiername == (string)$indexes)
			{
				$itemmap[$indexes] = (string)$element->$target;	
				break(1);
			}
		}
	}
	return $itemmap;
}

function attrmap_get_particle_attribute($schema,$target)
{
	foreach ($schema->attribute_controlled_attached_particles->particle as $particles)
	{
		if ($particles->id == $target) 
		{
			$sys_name =(string) $particles->system;
			$sys_name = str_replace('_',' ',$sys_name);
			$sys_name = str_replace('super','super ',$sys_name);
			return $sys_name;
		}
	}
}

function attrmap_filter_defindex_and_node($xml,$identifierarray,$defindex,$target)
{// look through xml, find item $id, look at attribute, find attribute $defindex, retrieve $target
	$itemmap = array();
    
    foreach ($identifierarray as $id)
    {
        foreach($xml->items->item as $item)
        {
            if ($item->id==$id && isset($item->attributes))
            {
                foreach ($item->attributes->attribute as $attr)
                {
                    if ($attr->defindex == $defindex)
                    {
                        $itemmap[$id] = (string)$attr->$target;
                        break(2);
                    }
                }
            }

        }
        
    }

	return $itemmap;
}

function get_single_id_detail($xml,$identifier,$id,$target)
{//$identifier can be id or defindex
	foreach ($xml->items->item as $element)
	{
		if ($element->$identifier == $id) return $element->$target;
	}
}

function get_single_attr($xml,$id,$defindex,$target)
{
	foreach ($xml->items->item as $item)
	{
		if ($item->id==$id && isset ($item->attributes))
		{
			foreach ($item->attributes->attribute as $attr)
			{
				if ($attr->defindex ==$defindex) return (string) $attr->$target;
			}
		}
	}
}

function save_xml($xml,$filepath) //filepath is relative to root, includes file name
{
	$filepath = "{$_SERVER['DOCUMENT_ROOT']}{$filepath}";
	if (file_exists($filepath))
	{
		$current = md5(simplexml_load_file($filepath));
		if (md5($xml) != $current && $xml!=null) //if different and not null
		{
			$xml->asXML($filepath);
		}
	}
	else //create file
	{
        	if ($xml!=null) $xml->asXML($filepath);
	}
}

function get_offline_xml($filepath)
{
    $filepath = "{$_SERVER['DOCUMENT_ROOT']}{$filepath}";
    if (file_exists($filepath)) return $filepath;
    else return null;
}

//Kim Christensen @ php.net
function num_files($dir, $recursive=false, $counter=0) {
    static $counter;
    if(is_dir($dir)) {
      if($dh = opendir($dir)) {
        while(($file = readdir($dh)) !== false) {
          if($file != "." && $file != "..") {
              $counter = (is_dir($dir."/".$file)) ? num_files($dir."/".$file, $recursive, $counter) : $counter+1;
          }
        }
        closedir($dh);
      }
    }
    return $counter;
  }




?>
