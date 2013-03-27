<?php // $id$
//////////////////////////////////////////////////////////////
//  Ensemble Video filtering
// 
//  Converts repo-generated urls or stock ensemble code to ATLAS player code
//  
//  Copyright (C) 2013 Liam Moran, Nathan Baxley
//  University of Illinois
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
//
//////////////////////////////////////////////////////////////

defined('MOODLE_INTERNAL') || die();
 
 
/// This is the filtering function itself.
class filter_ensemble extends moodle_text_filter {


	public function filter($text, array $options = array()) {
		global $CFG;
	
 	    if (!isset($CFG->filter_ensemble_enable)) {
	        set_config( 'filter_ensemble_enable','' );
	    }
	    
	    $newtext = $text; // fullclone is slow and not needed here
// $1 will be the videoID here.	    
	    if ($CFG->filter_ensemble_enable) {
//		    $ensembleURL = $CFG->filter_ensemble_url;
	    $search = array('#<a href="https://ensemble.illinois.edu/app/atlasplayer/embed.aspx\?videoid=([^"]*)".*?</a>#is', '#<div((?:(?!id=).)*?) id="ensembleEmbeddedContent_([^"]*)"[^>]*>.*?<\/div>#is');		    
		$newtext = preg_replace_callback($search, array('filter_ensemble','callback'), $newtext);
		
		}
		
	    if (is_null($newtext) or $newtext === $text) {
	        // error or not filtered
	        return $text;
		}
	
	    return $newtext;
}

private function callback($matches) {
	// This callback function should first perform an ensemble API call
	// so we can get correct dimensions for the video
	//
	global $CFG;
	$ensembleURL = $CFG->filter_ensemble_url;//'https://ensemble.illinois.edu';
        // Let's get other potential parameters, eh?
	$embedInfo = explode('&',$matches[count($matches) - 1]);
	$videoID = $embedInfo[0];
	$otherParams = '';
	$width = 0;
	$height = 0;
	if (sizeof($embedInfo) > 1)
	{
	  for ($i=1; $i<sizeof($embedInfo); $i++)
	  {
	    $atvalpair = explode('=',$embedInfo[$i]);
	    // get rid of amp; at start of attribute string
	    if (substr($atvalpair[0],0,4) == 'amp;')
	    {
		$atvalpair[0] = substr($atvalpair[0],4);
	    }
	    if ($atvalpair[0] == 'height' || $atvalpair[0] == 'videoHeight')
	    {
	      $height = $atvalpair[1];
	    } else if ($atvalpair[0] == 'width' || $atvalpair[0] == 'videoWidth')
	    {
	      $width = $atvalpair[1];
            } else
            {
	      $otherParams = $otherParams . ' data-' . $atvalpair[0] . '="' . $atvalpair[1] . '"';
	    }
          }
	}
	$ensemble_query_URL = $ensembleURL . '/app/simpleAPI/video/show.xml/' . $videoID;
	$c = new curl();
	$response = $c->get($ensemble_query_URL);
	$xml = simplexml_load_string($response);
	if (!$width) // We want to allow people to set 0 height
	{
		$dimensions = $xml->videoEncodings->dimensions;
		if ($dimensions == 'x')
		{
			$dimarray = array('480','0');
		} else {
			$dimarray = explode('x',(string)$dimensions);
		}
		$width = (string)$dimarray[0];
		$height = (string)$dimarray[1];
		// Let's make high def stuff sanely sized in the page
		if ((int)$width > 640)
		{
			$width = '640';
			$height = (string)floor((float)$width/((float)$dimarray[0]/(float)$height));
		}
		// Cleanup forced audio sizing...
		if ($height == '26') {
			$height = '0';
		}
	}
	return '<div webkitallowFullScreen="true" mozallowFullScreen="true" msallowFullScreen="true" data-videoId="' . $videoID . '" data-autoplay="false" data-captions="false" data-videoWidth="' . $width . '" data-videoHeight="' . $height . '" ' . $otherParams . ' ><script type="text/javascript" src="' . $ensembleURL .'/app/atlasplayer/atlasplayer.js" defer="defer"></script></div>' ;
	// end callback
	}
}

?>
