<?php
/** Error reporting */
#error_reporting(E_ALL);
#ini_set('display_errors', TRUE);
#ini_set('display_startup_errors', TRUE);
#date_default_timezone_set('Europe/Berlin');

include(__DIR__ . '/../libs/zip.lib.php');
require_once __DIR__ . '/../libs/PHPExcel.php';
require_once __DIR__ . '/../libs/PHPExcel/Cell/AdvancedValueBinder.php';

class Center extends Controller {

	var $db;
	var $admin;
	var $superadmin;
	var $sapwd;
	var $adminpw;
	var $limit;
	var $fc;
	var $conf;
	
	function Center(){
		session_start();
		$this->db = new dbfunctions();
		$this->fc = new frontcontroller();
		$this->util = new util();
		$this->db->dbOn();

		$this->admin = ADMIN;
		$this->adminpw = md5(ADMINPW);	
		
// Development only	
		#$_SESSION['xcms']['login']['admin'] = false;
		$this->limit = '6';
		$this->conf = $this->grabConfig(); 		
	}
		
	function index() {
		#unset($_SESSION['xcms']);
		
		#$this->debug($_POST);
		
		$data = array();
		
		if($_POST['submit'] == '')
			$data['pagination']['page'] = '1';
		else 
			$data['pagination']['page'] = $_POST['submit'];
			
		$data['pagination']['maxcount'] = $this->db->dbCount('news', "online = '1'");
		$data['pagination']['maxpage'] = ceil($data['pagination']['maxcount']/ $this->limit);
		$start = (($data['pagination']['page']-1)*$this->limit);
		if ($start < '0') $start = '0';
		
		$data['news'] = $this->db->dbCatchAll('news', '*', "online = '1'", 'date DESC', $start.", ".$this->limit);
		return($data);
	}
	
	function logmein () {
		$this->setLayout('admin');
		$data = array();
		return $data;
	}

	function getData(){
		$data = array();
		$data['member'] = $this->db->dbCatchAll('member', '*', "id = '".$_POST['id']."'");
		$this->debug($data['member']);
		die();
	}
	
	function anmeldungen(){
		$data = array();
		$data['controls']['backendmode'] = ('true' == $_REQUEST['backendmode']);
		$data['config'] = $this->conf;
		$data['c']['sc'] = $this->db->dbCount('member', "rang = 'sc' and bezahlt = 1 and deleted != 1");
		$data['c']['nsc'] = $this->db->dbCount('member', "rang = 'nsc' and bezahlt = 1 and deleted != 1");
		$data['c']['sc_reserviert'] = $this->db->dbCount('member', "rang = 'sc' and deleted != 1");
		$data['c']['nsc_reserviert'] = $this->db->dbCount('member', "rang = 'nsc' and deleted != 1");
		return ($data);
	
	}
		
	function grabConfig(){
		$data = array();
		$data = $this->db->dbCatchAll('config', '*');
		return($data);
	}
	
	function config(){
		$this->setLayout('admin');		
		$data = array();
		$data['c'] = $this->conf;
		return($data);
	}	

	function editConfig(){	
		$id = $_POST['id'];
		unset($_POST['id']);
		$c=0;
		foreach($_POST as $key => $value){
			$sql .= ($c != 0) ? ', ' : '';
			$sql .= $key."= '".addslashes($value)."'";
			$c++;
		}

		$this->util->_debug($sql);
		$count = $this->db->dbCount('config');
		if ($count == '0') {
			$this->db->dbInsert('config', $sql);
			echo "<script>self.location.href='".$_SERVER['PHP_SELF']."?action=config';</script>";
		} else {
			$this->db->dbUpdate('config', $sql, "id = '".$id."'");
			echo "<script>self.location.href='".$_SERVER['PHP_SELF']."?action=config';</script>";
		}
	}
	
	function admin(){
		$this->setLayout('admin');
		$data = array();

		$data['sc'] = $this->db->dbCount('member', "rang='sc' AND warteliste='0' AND deleted != '1'");
		$data['nsc'] = $this->db->dbCount('member', "rang='nsc' AND warteliste='0' AND deleted != '1'");
		$data['sc_wl'] = $this->db->dbCount('member', "rang='sc' AND warteliste='1' AND deleted != '1'");
		$data['nsc_wl'] = $this->db->dbCount('member', "rang='nsc' AND warteliste='1' AND deleted != '1'");
		$data['deleted'] = $this->db->dbCount('member', "deleted = '1'");
		
		return($data);
	}

	function suche(){
		$this->setLayout('admin');		
		$data = array();
		$data['search'] = false;
		if ($_POST['search'] == true) {		
			$data['search'] = true;
			
			if ($_POST['string'] != ''){
				$search = $this->db->dbCatchAll('member', 'id, vorname, nachname, rang, bezahlt, warteliste', "(nachname LIKE '%".$_POST['string']."%' OR vorname LIKE '%".$_POST['string']."%')", "nachname");
			}
			if(count($search) == '0'){
				$data['nohit'] = true;
				$data['nohit_msg'] = 'Es wurde kein Teilnehmer mit dem Nachnamen <b>'.$_POST['string'].'</b> gefunden.';
			} else {
				$data['count'] = count($search);
				$data['string'] = $_POST['string'];
				foreach ($search as $key => $value) {
					$data['result'][$key] = $value;
				}
			}
			
		}
				
		return ($data);
	}
	
	function export(){
		$this->setLayout('admin');				
		$data = array();
		$data['menu'] = false;
		$data['archiv'] = false;

		if ($_REQUEST['cat'] == ''){
			$data['menu'] = true;
		} 
		else if ($_REQUEST['cat'] == 'archiv'){
			if ($handle = opendir('exports/')) {
				$data['archiv'] = true;
			    while (false !== ($file = readdir($handle))) {
			    	if ($file != '.' && $file != '..' && $file != '.htaccess' && $file != '.gitkeep')
				    	$files[] = $file;   	
			    }
			    sort($files);
			    $data['exports'] = $files;
				closedir($handle);
			}
		} else {	
			if ($_REQUEST['cat'] == 'all'){
				$sc_sql = "SELECT *, DATE_FORMAT(FROM_UNIXTIME(datum), '%d.%m.%Y %T') as datum, 
					IF (deleted_date != '', DATE_FORMAT(FROM_UNIXTIME(deleted_date), '%d.%m.%Y %T'), '') as deleted_date
					FROM j11_member order by nachname ASC";
				$res = $this->db->query($sc_sql);
				$file = "Id|Vorname|Nachname|Strasse|Plz|Ort|Land|Telefon|Email|Geb_datum|Vegetarier|Aufbau|Abbau|Erfahrung|Erfahrung_Tage|Sanitaeter|Krankheiten|Krankheiten_welche|Durchschlafen|Zimmer|Bemerkung|Datum|Bezahlt|Sichtbar|Warteliste|Rang|Deleted|Deleted_date|Orga_message";
				$filename = "jetland_11_export_all_".date('d-m-Y_His', time()).".xlsx";
			} else if ($_REQUEST['cat'] == 'sc'){
				$sc_sql = "SELECT member.*, DATE_FORMAT(FROM_UNIXTIME(member.datum), '%d.%m.%Y %T') as datum, 
					IF (member.deleted_date != '', DATE_FORMAT(FROM_UNIXTIME(member.deleted_date), '%d.%m.%Y %T'), '') as deleted_date, 
					sc.* FROM j11_member member left join j11_sc sc on (sc.uid = member.id) WHERE member.rang = 'sc' order by member.nachname ASC";
				$res = $this->db->query($sc_sql);
				$filename = "jetland_11_export_sc_".date('d-m-Y_His', time()).".xlsx";
				$file ="Id|Vorname|Nachname|Strasse|Plz|Ort|Land|Telefon|Email|Geb_datum|Vegetarier|Aufbau|Abbau|Erfahrung|Erfahrung_Tage|Sanitaeter|Krankheiten|Krankheiten_welche|Durchschlafen|Zimmer|Bemerkung|Datum|Bezahlt|Sichtbar|Warteliste|Rang|Deleted|Deleted_date|Orga_message|Charname|Rasse|Klasse|Herkunft|Zauber|Contage";				
			} else if ($_REQUEST['cat'] == 'nsc'){
				$nsc_sql = "SELECT member.*, DATE_FORMAT(FROM_UNIXTIME(member.datum), '%d.%m.%Y %T') as datum, 
							IF (member.deleted_date != '', DATE_FORMAT(FROM_UNIXTIME(member.deleted_date), '%d.%m.%Y %T'), '') as deleted_date, 
							nsc.* FROM j11_member member left join j11_nsc nsc on (nsc.uid = member.id) WHERE member.rang = 'nsc' order by member.nachname ASC";
				$res = $this->db->query($nsc_sql);
				$file = "Id|Vorname|Nachname|Strasse|Plz|Ort|Land|Telefon|Email|Geb_datum|Vegetarier|Aufbau|Abbau|Erfahrung|Erfahrung_Tage|Sanitaeter|Krankheiten|Krankheiten_welche|Durchschlafen|Zimmer|Bemerkung|Datum|Bezahlt|Sichtbar|Warteliste|Rang|Deleted|Deleted_date|Orga_message|Festrolle_plot|Festrolle_ambiente|Springer|Traeume|Schminken|Kaempfen|Zaubern";
				$filename = "jetland_11_export_nsc_".date('d-m-Y_His', time()).".xlsx";				
			}
			
			PHPExcel_Settings::setLocale('de_de');
			PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setCreator("Jetland Anmeldetool")
										 ->setTitle("Jetland 11 Teilnehmerliste");

			$objPHPExcel->setActiveSheetIndex(0);



			$headers = explode("|",$file);

			$cellindex = 0;
			$rowindex = 1;
			foreach($headers as $header) {
				$column = $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($cellindex, $rowindex, ucfirst($header), true)->getColumn();
				$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
				$cellindex++;
			}

			foreach ($res as $t) {
				$rowindex++;
				$cellindex = 0;
				foreach($t as $key => $value) {				
					if ($key == 'uid') { continue;}
					$value = ($value == '01.01.1970') ? ' ' : $value;
					$value = (!empty($value)) ? $value : ' ';					
					if ($key != 'contage' || $key != 'erfahrung_tage') {									
						if ($value == '0') {
							$value = 'Nein';
						} else if ($value == '1') {
							$value = 'Ja';
						}
					}					
					$txt = $value;
					$txt = preg_replace("#(\r|\n)#", ' ', $txt);
					$objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicitByColumnAndRow($cellindex, $rowindex, $txt, PHPExcel_Cell_DataType::TYPE_STRING);
					$cellindex++;
				}
			}

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save(__DIR__.'/../../../exports/'.$filename);

			if(true) {
				$data['show_export'] = true;
				$data['status'] = '1';
		 		$data['msg'] = 'Der Export war erfolgreich.<br /><br />';
		 		$data['filename'] = $filename;
		 	} else {
		 		$data['show_export'] = true;			
		 		$data['status'] = '0';
		 		$data['msg'] = 'Beim Export ist ein Fehler aufgetreten.<br />';
			}
		}
		
		return($data);
	}

	function edit(){
		$this->setLayout('admin');				
		$id = $_GET['id'];
		$data = array();
			
		$data['data']['data'] = $this->db->dbCatchAll('member', '*', "id='".$id."'");
		if ($data['data']['data'][0]['rang'] == 'sc') {
			$data['data']['sc'] = $this->db->dbCatchAll('sc', '*', "uid='".$id."'");
		} else {
			$data['data']['nsc'] = $this->db->dbCatchAll('nsc', '*', "uid='".$id."'");
		}
		return($data);
	}		
		
	function teilnehmer(){
		$data['data'] = $this->db->dbCatchAll('member', '*', "sichtbar = '1' AND deleted != '1'");
		$data['config'] = $this->conf;
		$data['c']['sc'] = $this->db->dbCount('member', "rang = 'sc' and bezahlt = 1 and deleted != 1");
		$data['c']['nsc'] = $this->db->dbCount('member', "rang = 'nsc' and bezahlt = 1 and deleted != 1");
		$data['c']['sc_reserviert'] = $this->db->dbCount('member', "rang = 'sc' and deleted != 1");
		$data['c']['nsc_reserviert'] = $this->db->dbCount('member', "rang = 'nsc' and deleted != 1");
		return($data);		
	}

	function change(){
		$id = $_POST['id'];
		$this->db->dbUpdate('member', "bezahlt = IF(bezahlt = '1', '0', '1')", "id = '".$id."'");
			$ret = $this->db->dbCatchAll('member', 'bezahlt', "id = '".$id."'");
			$status_bez = ($ret[0]['bezahlt'] == '0') ?  'glyphicon glyphicon-remove text-danger' : 'glyphicon glyphicon-ok text-success';
			echo "<span class='".$status_bez."' aria-hidden='false'>";

		die();
	}
	function changeListe(){
		$id = $_POST['id'];
		$this->db->dbUpdate('member', "warteliste = IF(warteliste = '1', '0', '1'), warteliste_rang = IF(warteliste = '1', warteliste_rang, NULL)", "id = '".$id."'");
		$ret = $this->db->dbCatchAll('member', 'warteliste', "id = '".$id."'");
		$status_wl = ($ret[0]['warteliste'] == '0') ?  'glyphicon glyphicon-remove text-danger' : 'glyphicon glyphicon-ok text-success';
		echo "<span class='".$status_wl."' aria-hidden='false'>";
		die();
	}	

	function delete(){
		$data = array();
		$id = $_REQUEST['id'];

		$data = array(
				'deleted' => '1',
				'deleted_date' => time(),
			);

		#die('here');
		$this->db->dbUpdate('member', $data, "id = '".$id."'");

		#$this->util->_debug($rank);
		
			
		$this->setLayout('success');
		$_SESSION['msg'] = "Der Teilnehmer wurde gel&ouml;scht.";
		$_SESSION['loc'] = 'overview';
		return($data);
	}
	
	function overview(){
		$this->setLayout('admin');		
		$data = array();
	
		
		$data['data']['sc'] = $this->db->dbCatchAll('member', '*', "rang='sc' and deleted != '1'");
		$data['data']['sc_count'] = count($data['data']['sc']);
		$data['data']['nsc'] = $this->db->dbCatchAll('member', '*', "rang='nsc' and deleted != '1'");		
		$data['data']['nsc_count'] = count($data['data']['nsc']);
		$data['data']['deleted'] = $this->db->dbCatchAll('member', '*', "deleted = '1'");
		$data['data']['deleted_count'] = count($data['data']['deleted']);

		#$this->util->_debug($data);

		return($data);
	}
	
	function anmelden(){


		/*
			rang 0 = crew
			rang 1 = sc
			rang 2 = nsc
		*/
		
		$backendmode = $_POST['backendmode'];
		$post = $this->util->_toData($_POST['member']);
		$sc = $this->util->_toData($_POST['sc']);
		$nsc = $this->util->_toData($_POST['nsc']);
		$post['datum'] = time();
		
		$data = array();
	
		if ($post['krankheiten'] == '0') {
			unset($post['krankheiten_welche']);
		} 
		if ($post['erfahrung'] == '0') {
			unset($post['erfahrung_tage']);
		}	
		
		$preCheck = $this->db->dbCatchAll('member', "id", "vorname = '".$post['vorname']."' AND nachname = '".$post['nachname']."'");	

		if (count($preCheck) == '0'){
			if($lastid = $this->db->dbInsert('member', $post)){
				if ($post['rang'] == 'sc') {
					$sc['uid'] = $lastid;
					$this->db->dbInsert('sc', $sc);
				} else {
					$nsc['uid'] = $lastid;
					$this->db->dbInsert('nsc', $nsc);					
				}

				if(!$backendmode) {
					$this->adminmail($post, $sc, $nsc);
					$this->usermail($post, $sc, $nsc);
					echo "<script>self.location.href='".$_SERVER['PHP_SELF']."?action=confirmation'</script>";
				}
				else {
					echo "<script>self.location.href='".$_SERVER['PHP_SELF']."?action=backend_confirmation'</script>";
				}
				
			} else {
				$this->setLayout('error');
				$_SESSION['msg'] = "Ein Fehler ist aufgetreten: ".mysql_error();
				return($data);			
			} 
		}
		else {
			$this->setLayout('error');
			$_SESSION['msg'] = "Es ist bereits ein Teilnehmer mit dem Namen ".$post['vorname']." ".$post['nachname']." angemeldet.";
			return($data);		
		}
		die();
	}

	function confirmation () {
		$data = array();
		return($data);
	}

	function impressum() {
		$data = array();
		return($data);
	}

	function editUser(){
		$check=0;	
		$data = array();
		$member = $this->util->_toData($_POST['member']);
		$sc = $this->util->_toData($_POST['sc']);
		$nsc = $this->util->_toData($_POST['nsc']);
		$tmp = explode('|', $member['rang']);
		$id = $member['id'];
		unset($member['id']);
		unset($member['old_rang']);

		if($member['deleted'] == '0') {
			$member['deleted_date'] = '';
		}
		else {
			$member['deleted_date'] = time();
		}

	/* add functions nsc*/	
		if ($member['rang'] == 'nsc') {
			$nsc['uid'] = $id;
			$nsc['festrolle_plot'] = ($nsc['festrolle_plot'] == '1') ? '1' : '0';
			$nsc['festrolle_ambiente'] = ($nsc['festrolle_ambiente'] == '1') ? '1' : '0';
			$nsc['springer'] = ($nsc['springer'] == '1') ? '1' : '0';
			$nsc['traeume'] = ($nsc['traeume'] == '1') ? '1' : '0';
		} else {
	/* add functions sc*/
			$sc['uid'] = $id;
		}

	/* add functions pure member*/
		if ($member['krankheiten'] == '0') {
			$member['krankheiten_welche'] = '';
		} 
		if ($member['erfahrung'] == '0') {
			$member['erfahrung_tage'] = '';
		}	
		$member['aufbau'] = ($member['aufbau'] == '1') ? '1' : '0';
		$member['abbau'] = ($member['abbau'] == '1') ? '1' : '0';
		$member['sichtbar'] = ($member['sichtbar'] == '1') ? '1' : '0';
/*
		$this->util->_debug($member);
		$this->util->_debug($sc);
		$this->util->_debug($nsc);		
		#die();
*/			
				
		if($this->db->dbUpdate('member', $member, "id = '".$id."'")){
			$this->db->dbDelete('sc', "uid = '".$id."'") ;
			$this->db->dbDelete('nsc', "uid = '".$id."'");			
			if ($member['rang'] == 'sc') {
				$this->db->dbInsert('sc', $sc);
			} else {
				$this->db->dbInsert('nsc', $nsc);
			}

			$this->setLayout('success');
			$_SESSION['msg'] = "User <b>".$member['vorname']." ".$member['nachname']."</b> wurde editiert.";
			$_SESSION['loc'] = 'overview';
			$_SESSION['back'] = true;
			return($data);	
		} else {
			$this->setLayout('error');
			$_SESSION['msg'] = "Beim editieren des Users ist ein Fehler aufgetreten.";
			return ($data);				
		}
		
	}

	
	function checkEdit(){
		$check = $this->db->dbCount('member', "id = '".$_POST['id']."' AND ".$_POST['key']." = '".$_POST['value']."'");
		echo ($check == '0') ? 'Das Feld wurde ge&auml;ndert.' : '';	
		die();
	}
	
	function userMail($data, $sc, $nsc){
		$text = "<html><head><style type='text/css'>body{font-family:Arial,Helvetica,sans-serif;}a{color:#124481;font-weight:bold;text-decoration:underline;}</style></head><body>";
		$text .= "Hallo ".$data[vorname].",<br />\n<br />\nvielen Dank f&uuml;r deine Anmeldung zum ".strtoupper($this->conf[0]['conname']).". Wir freuen uns auf Dich! <br />\n<br />";
		$text .= "Bitte überweise nun Deinen Teilnahmebeitrag auf das folgende Konto.<br />\n<br />";
		$text .= "<b>Inhaber:</b> Christoph Platt\n<br />";
		$text .= "<b>Institut:</b> Sparkasse München\n<br />";
		$text .= "<b>IBAN:</b> DE26 7015 0000 1000 5296 75\n<br />";
		$text .= "<b>BIC:</b> SSKMDEMMXXX\n<br />";
		$text .= "<b>Verwendungszweck:</b> &lt;SC oder NSC&gt;, J11, &lt;Realname&gt;\n<br />";
		$text .= "<br />\n<br />Beachte bitte außerdem, dass wir Deine Anmeldung erst dann weiter bearbeiten, wenn Dein Beitrag bei uns eingegangen ist.<br />\n<br />";
		$text .= "- Deine Jetland Orga\n<br />\n<br />\n<br />";

		$text .= "<b>Hier sind Deine Angaben aus der Anmeldung als Referenz:</b><br />";		
		foreach ($data as $key => $value) {

			if ($key == 'sanitaeter' || $key == 'vegetarier' || $key == 'aufbau' || $key == 'abbau' || $key == 'durchschlafen')
				$value = ($value == '1') ? 'Ja' : 'Nein';

			if ($key == 'krankheiten') {
				$value = ($value == '1') ? 'Ja' : 'Nein';
			}

			if ($key == 'rang') {
				$value = strtoupper($value);
			}
			
			if ($key == 'erfahrung' || $key == 'datum' || $key == 'sichtbar' || $key == 'mitfahr1' || $key == 'mitfahr2' || $key == 'mitfahr3' || $key == 'uid') {
				continue;
			}
				
			$text .= ucfirst($key).": ".$value."<br>\n";
		}
		$text .= "\n\n<br /><br />";

		if ($data['rang'] == 'sc') {
			$text .= "<b>Charakterinformationen:</b><br />";
			foreach ($sc as $key => $value) {
				if ($key == 'zauber') {
					$value = ($value == '1') ? 'Ja' : 'Nein';
				}				
				if ($key == 'uid') {
					continue;
				}
				
				$text .= ucfirst($key).": ".$value."<br>\n";
			}			
		} else {
			$text .= "<b>NSC Informationen:</b><br />\n";
			foreach ($nsc as $key => $value) {
				if ($key != 'unterkunft') {
					$value = ($value == '1') ? 'Ja' : 'Nein';
				}
				if ($key == 'uid') {
					continue;
				}
				
				$text .= ucfirst($key).": ".$value."<br>\n";				
			}
		}
		
		$text .= "</body></html>\n";
		
		$headers = "From: anmeldung@dreywassern.de\nReturn-Path: anmeldung@dreywassern.de\r\n";
      $universal_extra = "MIME-Version: 1.0\nContent-type: text/html; charset=UTF-8\nContent-transfer-encoding: 8bit\nDate: " . gmdate('D, d M Y H:i:s', time()) . " UT\n";
      $extra_headers = $universal_extra . $headers;
		mail($data['email'], 'Deine '.strtoupper($data['rang']).' Anmeldung zum '.$this->conf[0]['conname'], $text, $extra_headers); 		
	}
	
	function adminmail($data, $sc, $nsc){
		
		if ($data['mitfahr1'] != ''){
			$data['mitfahr'] = "Ich ".$data['mitfahr1']." ".$data['mitfahr2']." Plaetze ab ".$data['mitfahr3'];
		}
		
		$text = "<html><head><style type='text/css'>body{font-family:Arial,Helvetica,sans-serif;}a{color:#124481;font-weight:bold;text-decoration:underline;}</style></head><body>";
		$text .= "<br />\n<br />\nEin Teilnehmer hat sich als ".ucfirst($data['rang'])." f&uuml;r das ".$this->conf[0]['conname']." angemeldet:<br />\n<br />\n";
		foreach ($data as $key => $value) {

			if ($key == 'sanitaeter' || $key == 'vegetarier' || $key == 'aufbau' || $key == 'abbau' || $key == 'durchschlafen')
				$value = ($value == '1') ? 'Ja' : 'Nein';

			if ($key == 'krankheiten') {
				$value = ($value == '1') ? 'Ja' : 'Nein';
			}

			if ($key == 'datum') {
				$value = date('d.m.Y H:i', $value); 
			}
			
			if ($key == 'erfahrung') {
				$value = ($value == '1') ? 'Ja' : 'Nein';
			}

			if ($key == 'erfahrung_tage') {
				$value = $value." Tage";
			}
			
			if ($key == 'sichtbar' || $key == 'mitfahr1' || $key == 'mitfahr2' || $key == 'mitfahr3')
				continue;
				
			$text .= ucfirst($key).": ".$value."<br>\n";
		}
		$text .= "\n\n<br /><br />";

		if ($data['rang'] == 'sc') {
			$postdata = "<b>Charakterinformationen:</b><br />";
			foreach ($sc as $key => $value) {
				if ($key == 'zauber') {
					$value = ($value == '1') ? 'Ja' : 'Nein';
				}
				$postdata .= ucfirst($key).": ".$value."<br>\n";				

			}			
		} else {
			$postdata = "<b>NSC Informationen:</b><br />\n";
			foreach ($nsc as $key => $value) {
				if ($key != 'unterkunft') {
					$value = ($value == '1') ? 'Ja' : 'Nein';
				}
				if ($key == 'uid') {
					continue;
				}
				$postdata .= ucfirst($key).": ".$value."<br>\n";				
			}
		}

		$receiver = $this->conf[0]['email'];
		$headers = "From: ".$this->conf[0]['email']."\nReturn-Path: anmeldung@dreywassern.de\r\n";
      $universal_extra = "MIME-Version: 1.0\nContent-type: text/html; charset=UTF-8\nContent-transfer-encoding: 8bit\nDate: " . gmdate('D, d M Y H:i:s', time()) . " UT\n";
      $extra_headers = $universal_extra . $headers;
      mail($receiver, ucfirst($data['rang']).' Anmeldung zum '.$this->conf[0]['conname'].': '.$data['vorname']." ".$data['nachname'], $text.$postdata, $extra_headers);         
	}
	
	function mailing(){
		$this->setLayout('admin');			
		$data = array();
		$data['users'] = $this->db->dbCatchAll('member', '*', '', 'nachname ASC');
		#$query = mysql_query("SELECT * FROM sommerfest ORDER BY charname ASC");
		#while($res = mysql_fetch_assoc($query)){
		#	$data['users'][] = $res;
		#}
		return($data);
	}
	
	function sendMassmail(){

$this->util->_debug($_POST);

		if (count($_POST['mail']) != '0'){
			$sender = 'anmeldung@dreywassern.de';	
			$c=0;
			foreach ($_POST['mail'] as $key => $value) {
				$vsql .= ($c != '0') ? ' OR ' : '';
				$vsql .= "id = '".$key."'";
				$c++;
			}
			

			$receiver = $this->db->dbCatchAll('member', 'email', $vsql);			
			foreach ($receiver as $rec) {
				$receive .= $rec['email'].", ";
			}

			$this->util->_debug($receive);
			die();
			
			$betreff = utf8_decode($_POST['title']);
			$text = utf8_decode(nl2br($_POST['text']));
			$receiver = substr($receive, 0, -2);
	
			$headers = "From: ".$sender."\nReturn-Path: ".$sender."\r\n";
			$headers .= "Bcc: ".$receive."\r\n";
	        $universal_extra = "MIME-Version: 1.0\nContent-type: text/html; charset=UTF-8\nContent-transfer-encoding: 8bit\nDate: " . gmdate('D, d M Y H:i:s', time()) . " UT\n";
	        $extra_headers = $universal_extra . $headers;
	
	        mail($sender, $betreff, $text, $extra_headers); 
			
	        $this->setLayout('success');
			$_SESSION['msg'] = "Die Email wurden erfolgreich versendet.";
			$_SESSION['loc'] = 'admin';
		} else {
	        $this->setLayout('error');
			$_SESSION['msg'] = "Es wurden keine Empf&auml;nger ausgew&auml;hlt.";
			$_SESSION['loc'] = 'admin';
		}
			return($data);					
	}


	function login(){		
		$data = array();
		if ($_POST['login']['user'] == $this->admin && md5($_POST['login']['pw']) == $this->adminpw){
		
			$_SESSION['xcms']['login']['check'] = true;
			$_SESSION['xcms']['login']['admin'] = true;
			$_SESSION['xcms']['login']['user'] = $_POST['login']['user'];
			$_SESSION['xcms']['login']['pw'] = md5($_POST['login']['pw']);
			$_SESSION['loc'] = $_POST['loc'];
			
			$this->setLayout('success');
			$_SESSION['loc'] = 'admin';
			$_SESSION['msg'] = "Dein Login war erfolgreich.";
			$_SESSION['back'] = false;
			return($data);	
		} else if ($_POST['login']['user'] == $this->superadmin && md5($_POST['login']['pw']) == $this->sapwd){
			$_SESSION['xcms']['login']['check'] = true;
			$_SESSION['xcms']['login']['admin'] = true;
			$_SESSION['xcms']['login']['superadmin'] = true;
			$_SESSION['xcms']['login']['user'] = $_POST['login']['user'];
			$_SESSION['xcms']['login']['pw'] = md5($_POST['login']['pw']);
			$_SESSION['loc'] = $_POST['loc'];
	
			$this->setLayout('success');
			$_SESSION['loc'] = 'admin';
			$_SESSION['msg'] = "Dein Login war erfolgreich.";
			$_SESSION['back'] = false;
			return($data);						
		} else {
			$this->setLayout('error');
			$_SESSION['msg'] = "Dein Login ist leider fehlgeschlagen.";
			return ($data);				
		}
	}
	
	function logout(){
		$data = array();
		$_SESSION['loc'] = $_REQUEST['loc'];
		unset($_SESSION['xcms']);
		
		$this->setLayout('success');
		$_SESSION['msg'] = "Logout erfolgreich.";
		$_SESSION['back'] = false;
		return($data);	
	}
}