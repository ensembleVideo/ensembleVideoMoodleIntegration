<?php // $id$
//////////////////////////////////////////////////////////////
//  Ensemble Video filtering
// 
//  Converts silly repo urls to stock ensemble code
//  
//  Copyright (C) 2012 Liam Moran, Nathan Baxley
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
$search = '#<a href="https://ensemble.illinois.edu/videoID/([^/]{22})[^<]*<\/a>#is';		    
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
	// We should pull this from $CFG-> by complicating filtersettings.php
	// instead of hard coding it
	$ensembleURL = 'https://ensemble.illinois.edu';
	$ensemble_query_URL = $ensembleURL . '/app/simpleAPI/video/show.xml/' . $matches[1];
	$c = new curl();
	$response = $c->get($ensemble_query_URL);
	$xml = simplexml_load_string($response);
	$params = '&useIframe=true&embed=true&displayTitle=false&startTime=0&autoplay=false&showcaptions=false';
	// We stream some audio-only content that I want to behave sanely
	if ($xml->videoEncodings->videoFormat == 'audio/mp3')
	{
		$height = '30';
		$width = '480';
		$params = $params . '&hideControls=false';
	} else {
		$dimensions = $xml->videoEncodings->dimensions;
		$dimensions = explode('x',$dimensions);
		$width = (string)$dimensions[0];
		$height = (string)((integer)$dimensions[1] + '30');
		$params = $params . '&hideControls=true';
	}
	// We could do other stuff, if we wanted.
	return '<div id="ensembleEmbeddedContent_' . $matches[1] . '" class="ensembleEmbeddedContent" style="width: ' . $width . 'px; height: ' . $height . 'px"><script type="text/javascript" src="'. $ensembleUrl .'/app/plugin/plugin.aspx?contentID=' . $matches[1] . $params '"></script></div>';
	    }


}

?>
