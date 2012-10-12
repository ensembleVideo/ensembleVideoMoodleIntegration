<?php

// Repository to manage publish interface with Ensemble server
// Written by Liam Moran, March of 2012 <moran@illinois.edu>
// Updated by Liam Moran, Sept. of 2012
//
//    Copyright (C) 2012 Liam Moran
//
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


class repository_ensemble extends repository {
// Declare vars here
private $ensembleURL;
private $destinationID;
private $searchDestID;
private $defaultID;

/*****************
 * Here is all of the administrative stuff
 * *****************/

public static function get_instance_option_names() {
  // Lists what needs to be provided by admin for each course instance
  return array('destinationID');
}

public function instance_config_form($mform) {
  // Prints out the form for admin to give stuff from 
  // get_instance_option_names return val
  $strrequired = get_string('required');
  $mform->addElement('text','destinationID',get_string('destinationID', 'repository_ensemble'));
  $mform->addRule('destinationID',$strrequired,'required',null,'client');
}

public static function instance_form_validation($mform, $data, $errors) {
  // For validating the admin's input to the config form
  // For now, just checking that it's non-empty
  // But it should maybe make an API call down the road if we
  // ensure that every destination is pre-loaded with some video
  // checking for a non-empty response
  if (empty($data['destinationID'])) {
    $errors['destinationID'] = get_string('invalidDestinationID','repository_ensemble');
    }
  return $errors;
}

public static function get_type_option_names() {
  // This is where we set global repository variables, shared
  // by all course instances
  // Here it's a URL to the ensemble server's simpleAPI interface
  // and a deafault destinationID (probably to a set of moodle tutorial videos)
  return array('ensembleURL','defaultID');
}

public function type_config_form($mform) {
  // Prints out a form to collect type_options from admin
  $ensembleURL = get_config('ensembleURL');
  if (empty($ensembleURL)){
    $ensembleURL = '';
  }
  $defaultID = get_config('defaultID');
  if (empty($defaultID)){
    $defaultID = '';
  }
  $strrequired = get_string('required');
  $mform->addElement('static',null,'',get_string('ensembleURLHelp','repository_ensemble'));
  $mform->addElement('text','ensembleURL',get_string('ensembleURL', 'repository_ensemble'), array('value'=>$ensembleURL,'size' => '22'));
  $mform->addRule('ensembleURL',$strrequired,'required',null,'client');
  $mform->addElement('static',null,'',get_string('defaultIDHelp','repository_ensemble'));
  $mform->addElement('text','defaultID', get_string('defaultID','repository_Ensemble'), array('value'=>$defaultID,'size'=>'22'));
  $mform->addRule('defaultID', $strrequired, 'required', null, 'client');
}

public static function type_form_validation($mform, $data, $errors) {
  if (empty($data['ensembleURL'])) {
    $errors['ensembleURL'] = 'I really need to know where it is';
  } elseif (empty($data['defaultID'])) {
    $errors['defaultID'] = 'A default destination is required!';
 }
  return $errors;
}

public static function plugin_init() {
// Creates a default instance when the repo plugin is created by admin
  $id = repository::static_function('ensemble','create','ensemble',0,get_system_context(),array('name' => 'default instance','destinationID' => $defaultID),1);
  if (empty($id)) {
     return false;
  } else {
    return true;
  }
}

/***************
 * Now the repository code
 */

public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
  // Constructor needs to grab the parameters set by admin
  $this->ensembleURL = get_config('ensemble','ensembleURL');
  // I originally set a global search destID in hardcode
  // but better to search within the active repo
  $this->searchDestID = parent::get_option('destinationID');
  parent::__construct($repositoryid, $context,$options);
}

public function global_search() {
  return true;
}

public function get_listing($path='', $page='1') {

  $ret = array();
  $ret['nologin'] = true;
  $ret['path'] = array();
  $ret['page'] = (int)$page;
  if ($ret['page'] < 1) {
    $ret['page'] = 1;
  }
  $ret['norefresh'] = true;
  $ret['nosearch'] = false;
  $list = array();
  $pageSize = '20';
  $this->destinationID = parent::get_option('destinationID');
  $this->feed_url = $this->ensembleURL . '/video/list.xml/' . $this->destinationID . '?orderBy=videoDateProduced&pageSize=20&pageIndex=' . $page;
  $c = new curl(array('cache'=>'false','module_cache'=>'repository'));
  $content = $c->get($this->feed_url);
  $xml = simplexml_load_string($content);
  $ret['total'] = (integer)$xml->metaData->recordCount;
  $ret['pages'] = ceil((integer)$ret['total']/(integer)$pageSize);
  foreach ($xml->video as $videoEntry) {
    $title = $videoEntry->videoTitle;
    $thumbnail = $videoEntry->previewUrl;
    $movieDate = $videoEntry[''];
    $source = 'https://ensemble.illinois.edu/videoID/' . $videoEntry->videoID . '/Video: ' . (string)$title; // VERY UGLY HACK!
    $list[] = array('title'=>(string)$title,
                    'thumbnail'=>(string)$thumbnail,
                    'thumbnail_width'=>150,
                    'thumbnail_height'=>120,
                    'size'=>'',
                    'date'=>'',
                    'source'=>(string)$source);
  }
  $ret['list'] = $list;
  return $ret;
}

public function search($search_text) {
  $this->keyword = $search_text;
  $ret = array();
  $ret['nologin'] = true;
  $ret['list'] = $this->_searchEnsemble($search_text, $this->searchDestID);
  return $ret;
}

private function _searchEnsemble($keyword, $searchID) {
  $list = array();
  $this->feed_url = $this->ensembleURL . '/video/list.xml/' . $searchID . '?orderBy=videoDateProduced&searchString=' . $keyword;
  $c = new curl(array('cache'=>'false', 'module_cache'=>'repository'));
  $content = $c->get($this->feed_url);
  $xml = simplexml_load_string($content);
  foreach ($xml->video as $videoEntry){
    $title = $videoEntry->videoTitle;
    $thumbnail = $videoEntry->previewUrl;
    $movieDate = $videoEntry[''];
    $source = 'https://ensemble.illinois.edu/videoID/' . $videoEntry->videoID . '/Video: ' . (string)$title; // AGAIN, VERY UGLY HACK!
    $list[] = array('title'=>(string)$title,
                    'thumbnail'=>(string)$thumbnail,
                    'thumbnail_width'=>150,
                    'thumbnail_height'=>120,
                    'size'=>'',
                    'date'=>'',
                    'source'=>$source);
  }
  return $list;
}

public function supported_filetypes() {
  return array('web_video');
}

public function supported_returntypes() {
  return FILE_EXTERNAL;
} 

// End of class
}
?>

