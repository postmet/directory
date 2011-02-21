<?php

function directory_configpageload() {
	global $currentcomponent,$display;
	if ($display == 'directory' && (isset($_REQUEST['action']) && $_REQUEST['action']=='add'|| isset($_REQUEST['id']) && $_REQUEST['id']!='')) { 
		$currentcomponent->addguielem('_top', new gui_pageheading('title', _('Directory')), 0);
					
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {
			$deet = array('dirname', 'description', 'repeat_loops', 'announcement',
						'repeat_recording', 'invalid_recording', 
						'callid_prefix', 'alert_info', 'invalid_destination', 'retivr',
						'say_extension', 'id');
      
			foreach ($deet as $d) {
				switch ($d){
					case 'repeat_loops';
						$dir[$d] = 2;
						break;
					case 'announcement':
					case 'repeat_recording':
					case 'invalid_recording':
						$dir[$d] = 0;
						break;
					default:
					$dir[$d] = '';
						break;
				}
			}
		} else {
			$dir				= directory_get_dir_details($_REQUEST['id']);
			//display usage
			$usage_list			= framework_display_destination_usage(directory_getdest($dir['id']));
			$usage_list_text	= isset($usage_list['text']) ? $usage_list['text'] : '';
			$usage_list_tooltip	= isset($usage_list['tooltip']) ? $usage_list['tooltip'] : '';
			$currentcomponent->addguielem('_top', new gui_link_label('usage', $usage_list_text, $usage_list_tooltip), 0);
			//display delete link
			$label 				= sprintf(_("Delete Directory %s"), $dir['dirname'] ? $dir['dirname'] : $dir['id']);
			$label 				= '<span><img width="16" height="16" border="0" title="' 
								. $label . '" alt="" src="images/core_delete.png"/>&nbsp;' . $label . '</span>';
			$currentcomponent->addguielem('_top', new gui_link('del', $label, $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '&action=delete', true, false), 0);
		}
		//delete link, dont show if we dont have an id (i.e. directory wasnt created yet)
		$gen_section = _('Directory General Options');
		$currentcomponent->addguielem($gen_section, new gui_textbox('dirname', $dir['dirname'], _('Directory Name'), _('Name of this directory.')));
		$currentcomponent->addguielem($gen_section, new gui_textbox('description', $dir['description'], _('Directory Description'), _('Description of this directory.')));
		$currentcomponent->addguielem($gen_section, new gui_textbox('callid_prefix', $dir['callid_prefix'], _('CallerID Name Prefix'), _('Prefix to be appended to current CallerID Name.')));
		$currentcomponent->addguielem($gen_section, new gui_textbox('alert_info', $dir['alert_info'], _('Alert Info'), _('ALERT_INFO to be sent when called from this Directory. Can be used for ditinctive ring for SIP devices.')));
		
		$section = _('Directory Options (DTMF)');
		
		//build recordings select list
		$currentcomponent->addoptlistitem('recordings', 0, _('Default'));
		foreach(recordings_list() as $r){
			$currentcomponent->addoptlistitem('recordings', $r['id'], $r['displayname']);
		}
	    $currentcomponent->setoptlistopts('recordings', 'sort', false);
		//build repeat_loops select list and defualt it to 3
		for($i=0; $i <11; $i++){
			$currentcomponent->addoptlistitem('repeat_loops', $i, $i);
		}
		
		//generate page
		$currentcomponent->addguielem($section, new gui_selectbox('announcement', $currentcomponent->getoptlist('recordings'), $dir['announcement'], _('Announcement'), _('Greeting to be played on entry to the directory.'), false));
		$currentcomponent->addguielem($section, new gui_selectbox('repeat_loops', $currentcomponent->getoptlist('repeat_loops'), $dir['repeat_loops'], _('Invalid Retries'), _('Number of times to retry when receiving an invalid/unmatched response from the caller'), false));
		$currentcomponent->addguielem($section, new gui_selectbox('repeat_recording', $currentcomponent->getoptlist('recordings'), $dir['repeat_recording'], _('Invalid Retry Recording'), _('Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again'), false));
		$currentcomponent->addguielem($section, new gui_selectbox('invalid_recording', $currentcomponent->getoptlist('recordings'), $dir['invalid_recording'], _('Invalid Recording'), _('Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries)'), false));
		$currentcomponent->addguielem($section, new gui_drawselects('invalid_destination', 0, $dir['invalid_destination'], _('Invalid Destination'), _('Destination to send the call to after Invalid Recording is played.'), false));
		$currentcomponent->addguielem($section, new gui_checkbox('retivr', $dir['retivr'], _('Return to IVR'), _('When selected, if the call passed through an IVR that had "Return to IVR" selected, the call will be returned there instead of the Invalid destination.'),true));
		$currentcomponent->addguielem($section, new gui_checkbox('say_extension', $dir['say_extension'], _('Announce Extension'), _('When checked, the extension number being transfered to will be announced prior to the transfer'),true));
		$currentcomponent->addguielem($section, new gui_checkbox('default_directory', $dir['default_directory'], _('Default Directory'), _('When checked, this becomes the default directory and replaces any other directory as the default directory. This has the effect of exposing entries for this directory into the Extension/User page'),true));
		$currentcomponent->addguielem($section, new gui_hidden('id', $dir['id']));
		$currentcomponent->addguielem($section, new gui_hidden('action', 'edit'));
		
    //TODO: the &nbsp; needs to be here instead of a space, guielements freaks for some reason with this specific section name
		$section = _('Directory&nbsp;Entries');
		//draw the entries part of the table. A bit hacky perhaps, but hey - it works!
		$currentcomponent->addguielem($section, new guielement('rawhtml', directory_draw_entries($dir['id']), ''));
	}
}

function directory_configpageinit($pagename) {
	global $currentcomponent;
	if($pagename == 'directory'){
		$currentcomponent->addprocessfunc('directory_configprocess');
		$currentcomponent->addguifunc('directory_configpageload');
    return true;
	}

	// We only want to hook 'users' or 'extensions' pages.
	if ($pagename != 'users' && $pagename != 'extensions') {
		return true;
	}

	$action			= isset($_REQUEST['action'])		? $_REQUEST['action']			: null;
	$extdisplay		= isset($_REQUEST['extdisplay'])	? $_REQUEST['extdisplay']		: null;
	$extension		= isset($_REQUEST['extension'])		? $_REQUEST['extension']		: null;
	$tech_hardware	= isset($_REQUEST['tech_hardware'])	? $_REQUEST['tech_hardware']	: null;

	if ($tech_hardware != null || $pagename == 'users') {
		directory_applyhooks();
		$currentcomponent->addprocessfunc('directory_configprocess_exten', 8);
	} elseif ($action == "add") {
		// We don't need to display anything on an 'add', but we do need to handle returned data.
		$currentcomponent->addprocessfunc('directory_configprocess_exten', 8);
	} elseif ($extdisplay != '') {
		// We're now viewing an extension, so we need to display _and_ process.
		directory_applyhooks();
		$currentcomponent->addprocessfunc('directory_configprocess_exten', 8);
	}
}

//prosses received arguments
function directory_configprocess(){
	if($_REQUEST['display'] == 'directory'){
		global $db,$amp_conf;
		//get variables for directory_details
		$requestvars = array('id','dirname','description','announcement',
							'callid_prefix','alert_info','repeat_loops',
							'repeat_recording','invalid_recording',
							'invalid_destination','retivr','say_extension');
		foreach($requestvars as $var){
			$vars[$var] = isset($_REQUEST[$var]) 	? $_REQUEST[$var]		: '';
		}

		$action		= isset($_REQUEST['action'])	? $_REQUEST['action']	: '';
		$entries	= isset($_REQUEST['entries'])	? $_REQUEST['entries']	: '';
		//$entries=(($entries)?array_values($entries):'');//reset keys

		switch($action){
			case 'edit':
				//get real dest
				$vars['invalid_destination'] = $_REQUEST[$_REQUEST[$_REQUEST['invalid_destination']].str_replace('goto','',$_REQUEST['invalid_destination'])];
				$vars['id'] = directory_save_dir_details($vars);
				directory_save_dir_entries($vars['id'],$entries);
				needreload();
				redirect_standard_continue('id');
			break;
			case 'delete':
				directory_delete($vars['id']);
				needreload();
				redirect_standard_continue();
			break;
		}
	}
}

function directory_get_config($engine) {
	global $ext,$db;
	switch ($engine) {
		case 'asterisk':
			$sql = 'SELECT id,dirname,say_extension FROM directory_details ORDER BY dirname';
			$results=sql($sql,'getAll',DB_FETCHMODE_ASSOC);
			if($results){
				$c = 'directory';
				// Note create a dial-id label for each directory to allow other modules to hook on a per
				// directory basis. (Otherwise we could have consolidated this into a call extension)
				foreach ($results as $row) {
					$ext->add($c, $row['id'], '', new ext_answer(''));
					$ext->add($c, $row['id'], '', new ext_wait('1'));
					$ext->add($c, $row['id'], '', new ext_agi('directory.agi,dir=' . $row['id'] . ',keypress=${keypress}'));
					if ($row['say_extension']) {
						$ext->add($c, $row['id'], '', new ext_playback('pls-hold-while-try&to-extension'));
						$ext->add($c, $row['id'], '', new ext_saydigits('${DIR_DIAL}'));
					}
					$ext->add($c, $row['id'], 'dial-'.$row['id'], new ext_ringing());
					$ext->add($c, $row['id'], '', new ext_goto('1','${DIR_DIAL}','from-internal'));
				}
				$ext->add($c, 'invalid', 'invalid', new ext_playback('${DIR_INVALID_RECORDING}'));
				$ext->add($c, 'invalid', '', new ext_ringing());
				$ext->add($c, 'invalid', '', new ext_goto('${DIR_INVALID_PRI}','${DIR_INVALID_EXTEN}','${DIR_INVALID_CONTEXT}'));
				$ext->add($c, 'retivr', 'retivr', new ext_playback('${DIR_INVALID_RECORDING}'));
				$ext->add($c, 'retivr', '', new ext_goto('1','return','${IVR_CONTEXT}'));
				$ext->add($c, 'h', '', new ext_macro('hangupcall'));
			}
			break;
	}
}

function directory_list() {
	$sql='SELECT id,dirname FROM directory_details ORDER BY dirname';
	$results=sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	return $results;
}

function directory_get_dir_entries($id){
	global $db;
	if ($id == '') {
		return array();
	}
	$id = $db->escapeSimple($id);
	$sql = "SELECT a.name, a.type, a.audio, a.dial, a.foreign_id, a.e_id, b.name foreign_name, IF(a.name != \"\",a.name,b.name) realname 
		FROM directory_entries a LEFT JOIN users b ON a.foreign_id = b.extension WHERE id = $id ORDER BY realname";
	$results = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	return $results;
}

function directory_get_dir_details($id){
	global $db;
	$clean_id					= $db->escapeSimple($id);
	$sql						= "SELECT * FROM directory_details WHERE ID = $clean_id";
	$row						= sql($sql,'getRow',DB_FETCHMODE_ASSOC);
	return $row;
}

function directory_delete($id){
	global $db;
	$id = $db->escapeSimple($id);
	sql("DELETE FROM directory_details WHERE id = $id");
	sql("DELETE FROM directory_entries WHERE id = $id");
	if (directory_get_default_dir() == $id) {
		directory_save_default_dir('');
	}
}

function directory_destinations(){
	global $db;
	$sql		= 'SELECT id,dirname FROM directory_details ORDER BY dirname';
	$results	= sql($sql,'getAll',DB_FETCHMODE_ASSOC);

	foreach($results as $row){
		$row['dirname'] = ($row['dirname'])?$row['dirname']:'Directory '.$row['id'] ;
		$extens[] = array('destination' => 'directory,' . $row['id'] . ',1', 'description' => $row['dirname'], 'category' => _('Directory'));
	}
	return isset($extens)?$extens:null;
}

function directory_draw_entries_table_header_directory() {
	return  array(_('Name'), _('Name Announcement'), _('Dial'));
}

function directory_draw_entries($id){
	$sql='SELECT id,name FROM directory_entries ORDER BY name';
	$results	= sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	$html		= '';
	$html		.= '<table id="dir_entries_tbl">';
	$headers	= mod_func_iterator('draw_entries_table_header_directory');

	$html 		.= '<thead><tr>';
	foreach ($headers as $mod => $header) {
		foreach ($header as $h) {
			if(is_array($h)) {
				$html .= '<th ' . $h['attr']  . '/>';
				$html .= $h['val'];
				$html .= '</th>';
			} else {
				$html .= '<th>' . $h . '</th>';
				
			}
		}

	}
	$html .= '</tr></thead>';

	$newuser = '<select id="addusersel">';
	$newuser .= '<option value="none" selected> == '._('Choose One').' == </option>';
	$newuser .= '<option value="all">'._('All Users').'</option>';
	$newuser .= '<option value="|">'._('Custom').'</option>';

  //TODO: could this cause a problem with the '|' separator if a name has a '|' in it? (probably not check for comment where parsed
  //
	foreach(core_users_list() as $user){
		$newuser .= '<option value="'.$user[0].'|'.$user[1].'">('.$user[0].') '.$user[1]."</option>\n";
	}
	$newuser	.= '</select>';
	$html		.= '<tfoot><tr><td id="addbut"><a href="#" class="info"><img src="images/core_add.png" name="image" style="border:none;cursor:pointer;" /><span>'._('Add new entry.').'</span></a></td><td colspan="3"id="addrow">'.$newuser.'</td></tr></tfoot>';
	$html		.= '<tbody>';
	$entries	= directory_get_dir_entries($id);
	foreach($entries as $e){
		$realid = $e['type'] == 'custom' ? 'custom' : $e['foreign_id'];
		$foreign_name = $e['foreign_name'] == '' ? 'Custom Entry' : $e['foreign_name'];
		$html .= directory_draw_entries_tr($id, $realid, $e['name'], $foreign_name, $e['audio'], $e['dial'], $e['e_id']);
	}
	$html .= '</tbody></table>';
	return $html;
}

//used to add row's the entry table
function directory_draw_entries_tr($id, $realid, $name = '',$foreign_name, $audio = '',$num = '',$e_id = '', $reuse_audio = false){
	global $amp_conf,  $directory_draw_recordings_list, $audio_select;
	if (!$directory_draw_recordings_list) {
		$directory_draw_recordings_list = recordings_list();
	}
	$e_id = $e_id ? $e_id : directory_get_next_id($realid);
	if (!$audio_select || !$reuse_audio) {
 		unset($audio_select);
		$audio_select = '<select name="entries['.$e_id.'][audio]">';
		$audio_select .= '<option value="vm" '.(($audio=='vm')?'SELECTED':'').'>'._('Voicemail Greeting').'</option>';
		$audio_select .= '<option value="tts" '.(($audio=='tts')?'SELECTED':'').'>'._('Text to Speech').'</option>';
		$audio_select .= '<option value="spell" '.(($audio=='spell')?'SELECTED':'').'>'._('Spell Name').'</option>';
		$audio_select .= '<optgroup label="'._('System Recordings:').'">';
		foreach($directory_draw_recordings_list as $r){
			$audio_select .= '<option value="' . $r['id'] . '" ' . (($audio == $r['id']) ? 'SELECTED' : '') . '>' . $r['displayname'] . '</option>';
		}
		$audio_select .= '</select>';
	}

	if ($realid != 'custom') {
		$user_type	=  (isset($amp_conf['AMPEXTENSION']) && $amp_conf['AMPEXTENSION']) == 'deviceanduser' ? 'user' : 'extension';
		$tlabel		=  sprintf(_("Edit %s: %s"), $user_type ,$realid);
		$label		= '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/user_edit.png"/>&nbsp;</span>';
		$user		= ' <a href="/admin/config.php?type=setup&display='.$user_type.'s&skip=0&extdisplay='.$realid.'">'.$label.'</a> ';
	} else {
		$user		= '';
	}
	$delete			= '<img src="images/trash.png" style="cursor:pointer;" alt="'._('remove').'" title="'._('Click here to remove this entry').'" class="trash-tr">';
	$t1_class 		= $name == '' ? ' class = "dpt-title" ' : '';
	$t2_class 		= $realid == 'custom' ? ' title="Custom Dialstring" ' : ' title="' . $realid . '" ';
	if (trim($num)  == '') {
		$t2_class 	.= '" class = "dpt-title" ';
	}
	
	$td[] = '<input type="hidden" readonly="readonly" name="entries['.$e_id.'][foreign_id]" value="'.$realid.'" /><input type="text" name="entries['.$e_id.'][name]" title="'.$foreign_name.'"'.$t1_class.' value="'.$name.'" />';
	$td[] = $audio_select;
	$td[] = '<input type="text" name="entries['.$e_id.'][num]" '.$t2_class.' value="'.$num.'" />';
	$opts = array('id' => $id, 'e_id' => $e_id, 'realid' => $realid, 'name' => $name, 'audio' => $audio, 'num' => $num);
	
	$more_td = mod_func_iterator('draw_entries_tr_directory', $opts);
	foreach ($more_td as $mod) {
		foreach ($mod as $m){
			$td[] = $m;
		}
	}
	
	$td[] = $delete.$user;
	
	//build html
	$html = '<tr class="entrie'.$e_id.'">';
	foreach ($td as $t) {
		if (is_array($t)) {
			$html .= '<td ' . $t['attr'] . '/>';
			$html .= $t['val'];
			$html .= '</td>';
		} else {
			$html .= '<td>' . $t . '</td>';
		}
	}
	$html .= '</tr>';
	return $html;
}

//used to add ALL USERS to the entry table
function directory_draw_entries_all_users($id){
	$html='';
	foreach(core_users_list() as $user){
    	$html .= directory_draw_entries_tr($id, $user[0], '', $user[1], 'vm', '',$id++, true);
	}
	return $html;
}


function directory_save_default_dir($default_directory) {
	global $db;
	
	if ($default_directory) {
		sql("REPLACE INTO `admin` (`variable`, value) VALUES ('default_directory', '$default_directory')");
	} else {
		sql("DELETE FROM `admin` WHERE `variable` = 'default_directory'");
	}
}

function directory_get_default_dir() {
	global $db;

	$ret = sql("SELECT value FROM `admin` WHERE `variable` = 'default_directory'", 'getOne');
	return $ret ? $ret : '';

}

// TODO: clean this up passing in $vals with expected positions for insert is very error prone!
//
function directory_save_dir_details($vals){
	global $db, $amp_conf;

	foreach($vals as $key => $value) {
		$vals[$key] = $db->escapeSimple($value);
	}

	if ($vals['id']) {
		$sql = 'REPLACE INTO directory_details (id,dirname,description,announcement,
				callid_prefix,alert_info,repeat_loops,repeat_recording,
				invalid_recording,invalid_destination,retivr,say_extension)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$foo = $db->query($sql,$vals);
		if(DB::IsError($foo)) {
			die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
		}
	} else {
		unset($vals['id']);
		$sql = 'INSERT INTO directory_details (dirname,description,announcement,
				callid_prefix,alert_info,repeat_loops,repeat_recording,
				invalid_recording,invalid_destination,retivr,say_extension)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$foo = $db->query($sql,$vals);
		if(DB::IsError($foo)) {
			die_freepbx(print_r($vals,true).' '.$foo->getDebugInfo());
		}
		$sql = ( ($amp_conf["AMPDBENGINE"]=="sqlite3") ? 'SELECT last_insert_rowid()' : 'SELECT LAST_INSERT_ID()');
		$vals['id'] = $db->getOne($sql);
		if (DB::IsError($foo)){
			die_freepbx($foo->getDebugInfo());
		}
	}

	return $vals['id'];
}

function directory_save_dir_entries($id,$entries){
	global $db;
	$id = $db->escapeSimple($id);
	sql("DELETE FROM directory_entries WHERE id = $id");

	//TODO = prepare the data:
	//       if 'dial' is the same as type_id, then delete the 'dial,' leave as default
	//       if 'name' is same as default_name, then delete the 'name,' leave as default
	if($entries){
		$insert='';
		// TODO: should we change to perpare/execute ?
		foreach($entries as $idx => $row){
			if($row['foreign_id'] == 'custom' && trim($row['name']) == '' || $row['foreign_id']==''){
				continue;//dont insert a blank row
			}
			if ($row['foreign_id'] == 'custom') {
				$type = 'custom';
				$foreign_id = '';
			} else {
				$type = 'user';
				$foreign_id = $db->escapeSimple($row['foreign_id']);
			}
			$audio = $row['audio'] != '' ? $db->escapeSimple($row['audio']) : ($row['foreign_id'] == 'custom' ? 'tts' : 'vm');
			if (!empty($insert)) {
				$insert .= ',';
			}
			$insert.='("'.$id.'","'.$idx.'","'.$db->escapeSimple(trim($row['name'])).'","'.$type.'","'.$foreign_id.'","'.$audio.'","'.$db->escapeSimple(trim($row['num'])).'")';
		}		
		sql('INSERT INTO directory_entries (id, e_id, name,type,foreign_id,audio,dial) VALUES '.$insert);
	}
}

//----------------------------------------------------------------------------
// Deal with default company directory hook

function directory_check_default($extension) {
	$def_dir = directory_get_default_dir();
	$sql = "SELECT foreign_id FROM directory_entries WHERE foreign_id = '$extension' AND id = '$def_dir' LIMIT 1";
	$results = sql($sql,"getAll");
	return count($results);
}

function directory_set_default($extension, $value) {
	$default_directory_id = directory_get_default_dir();
	if ($default_directory_id == '') {
		return false;
	}
	if ($value) {
		sql("REPLACE INTO directory_entries (id, foreign_id) VALUES ($default_directory_id, '$extension')");
	} else {
		sql("DELETE FROM directory_entries WHERE id = $default_directory_id AND foreign_id = '$extension'");
	}
}

function directory_applyhooks() {
	global $currentcomponent;

	// Add the 'process' function - this gets called when the page is loaded, to hook into 
	// displaying stuff on the page.
	$currentcomponent->addoptlistitem('directory_group', '0', dgettext('directory',_("Exclude")));
	$currentcomponent->addoptlistitem('directory_group', '1', dgettext('directory',_("Include")));
	$currentcomponent->setoptlistopts('directory_group', 'sort', false);

	$currentcomponent->addguifunc('directory_configpageload_exten');
}

// This is called before the page is actually displayed, so we can use addguielem().
function directory_configpageload_exten() {
	global $currentcomponent;

	// Init vars from $_REQUEST[]
	$action = isset($_REQUEST['action']) ? $_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay']) ? $_REQUEST['extdisplay']:null;
	
	// Don't display this stuff it it's on a 'This xtn has been deleted' page.
	if ($action != 'del') {

		$default_directory_id = directory_get_default_dir();
		$section = _("Default Group Inclusion");
		if ($default_directory_id != "") {
			$in_default_directory = directory_check_default($extdisplay);
			$currentcomponent->addguielem($section, new gui_selectbox('in_default_directory', $currentcomponent->getoptlist('directory_group'), $in_default_directory, _('Default Directory'), _('You can include or exclude this extension/user from being part of the default directory when creating or editing.'), false));
		} 
	}
}

function directory_configprocess_exten() {
	global $db;

	//create vars from the request
	//
	$action					= isset($_REQUEST['action'])				? $_REQUEST['action']				: null;
	$ext					= isset($_REQUEST['extdisplay'])			? $_REQUEST['extdisplay']			: null;
	$extn 					= isset($_REQUEST['extension'])				? $_REQUEST['extension']			: null;
	$in_default_directory	= isset($_REQUEST['in_default_directory'])	? $_REQUEST['in_default_directory']	: false;

	$extdisplay 			= ($ext === '') ? $extn : $ext;
	
	if (($action == "add" || $action == "edit")) {
		if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
			if ($in_default_directory !== false) {
				directory_set_default($extdisplay, $in_default_directory);
			}
		}
	} elseif ($extdisplay != '' && $action == "del") {
		$sql = "DELETE FROM directory_entries WHERE foreign_id = '$extdisplay'";
		sql($sql);
	}
}

//----------------------------------------------------------------------------
// Dynamic Destination Registry and Recordings Registry Functions

function directory_check_destinations($dest=true) {
	global $active_modules;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	$sql = "SELECT id, dirname, invalid_destination FROM directory_details ";
	if ($dest !== true) {
		$sql .= "WHERE invalid_destination in ('".implode("','",$dest)."')";
	}
	$results = sql($sql, "getAll", DB_FETCHMODE_ASSOC);

	foreach ($results as $result) {
		$thisdest	= $result['invalid_destination'];
		$thisid 	= $result['id'];
		$destlist[]	= array(
			'dest' => $thisdest,
			'description' => sprintf(_("Directory: %s "), ($result['dirname'] ? $result['dirname'] : $result['id'])),
			'edit_url' => 'config.php?display=directory&id=' . urlencode($result['id']),
		);
	}
	return $destlist;
}

function directory_change_destination($old_dest, $new_dest) {
	$sql = 'UPDATE directory_details SET invalid_destination = "' . $new_dest . '" WHERE invalid_destination = "' . $old_dest . '"';
	sql($sql, "query");
}

function directory_getdest($id) {
	return array("directory,$id,1");
}

function directory_getdestinfo($dest) {
	if (substr(trim($dest),0,10) == 'directory,') {
		$grp = explode(',',$dest);
		$id = $grp[1];
		$thisdir = directory_get_dir_details($id);

		if (empty($thisdir)) {
			return array();
		} else {
			return array('description' => sprintf(_("Directory %s: "), ($thisdir['dirname'] ? $thisdir['dirname'] : $id)),
			             'edit_url' => 'config.php?display=directory&id=' . urlencode($id),
					);
		}
	} else {
		return false;
	}
}

function directory_get_next_id($realid) {
	global $db;
	$res = sql('SELECT MAX(e_id) FROM directory_entries WHERE id = "' . $realid . '"', 'getOne');
	return $res ? $res : 1;
}

function directory_recordings_usage($recording_id) {
	global $active_modules;

	$results = sql("SELECT `id`, `dirname` FROM `directory_details` 
					WHERE	`announcement` = '$recording_id' 
					OR `repeat_recording` = '$recording_id' 
					OR `invalid_recording` = '$recording_id'",
					"getAll",DB_FETCHMODE_ASSOC);
	if (empty($results)) {
		return array();
	} else {
		//$type = isset($active_modules['ivr']['type'])?$active_modules['ivr']['type']:'setup';
		foreach ($results as $result) {
			$usage_arr[] = array(
				'url_query' => 'config.php?display=directory&id=' . urlencode($result['id']),
				'description' => sprintf(_("Directory: %s"), ($result['dirname'] ? $result['dirname'] : $result['id'])),
			);
		}
		return $usage_arr;
	}
}
?>
