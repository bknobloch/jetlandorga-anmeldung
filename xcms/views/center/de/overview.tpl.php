	
<?php
#	echo "<pre>";
#	print_r($data);
#	echo "</pre>";

?>



<?php if ($_SESSION['xcms']['login']['admin'] == true): ?>
<script type="text/javascript">
			
	 function changeStatus(id) {
	 	$.ajax({
			type: 'POST',
			url: '?action=change', 
			data: {id: id}, 
			success: function (data) {
		  		$("#info").show('slow');
		  		$("#info").animate({opacity: 1.0}, 1000)
		  		$("#info").html('<b>Der Status wurde aktualisiert.</b>');
		  		$("#info").hide('fast');
		  		$("#status_"+id).html(data);
		}});				
	 }

 	function changeList(id) {
		$.ajax({
   			type: "POST",
   			url: "?action=changeListe",
   			data: {id: id},
   			success: function(msg){
				$('#liste_'+id).html(msg);
		  		$("#info").show('slow');
		  		$("#info").animate({opacity: 1.0}, 1000)
		  		$("#info").html('<b>Der Status der Warteliste wurde aktualisiert.</b>');
		  		$("#info").hide('fast');	
			},
   			error: function(){
   				alert('Ein Fehler ist aufgetreten.');
			}
		});
		if (action == 'show'){
			$('#disp').css('width', '400px');
		} else {
			$('#disp').css('width', '600px');
		}
		$('#disp').show('fast');
	}
	
	function getTextExtractor()
	{
	  return (function() {
	    var patternLetters = /[öäüÖÄÜáàâéèêúùûóòôÁÀÂÉÈÊÚÙÛÓÒÔß]/g;
	    var patternDateDmy = /^(?:\D+)?(\d{1,2})\.(\d{1,2})\.(\d{2,4})$/;
	    var lookupLetters = {
	      "ä": "a0", "ö": "o0", "ü": "u0",
	      "Ä": "A0", "Ö": "O0", "Ü": "U0",
	      "á": "a0", "à": "a0", "â": "a0",
	      "é": "e0", "è": "e0", "ê": "e0",
	      "ú": "u0", "ù": "u0", "û": "u0",
	      "ó": "o0", "ò": "o0", "ô": "o0",
	      "Á": "A0", "À": "A0", "Â": "A0",
	      "É": "E0", "È": "E0", "Ê": "E0",
	      "Ú": "U0", "Ù": "U0", "Û": "U0",
	      "Ó": "O0", "Ò": "O0", "Ô": "O0",
	      "ß": "s0"
	    };
	    var letterTranslator = function(match) { 
	      return lookupLetters[match] || match;
	    }

	    return function(node) {
	      var text = $.trim($(node).text());
	      var date = text.match(patternDateDmy);
	      if (date)
	        return [date[3], date[2], date[1]].join("-");
	      else
	        return text.replace(patternLetters, letterTranslator);
	    }
	  })();
	}

$(document).ready(function(){
	
	// Tablelayout
	$("#large1").tablesorter({
		widgets: ['zebra'],
		sortList : [[0,0], [1,0]],
		textExtraction: getTextExtractor(),
	});
	
	$("#large2").tablesorter({
		widgets: ['zebra'],
		sortList : [[0,0], [1,0]],
		textExtraction: getTextExtractor(),
	});	

	$("#large3").tablesorter({
		widgets: ['zebra'],
		sortList : [[0,0], [1,0]],
		textExtraction: getTextExtractor(),
	});	
});

</script>





<style>

	table {
		margin: 20px 0 20px 0 !important;
		padding: 0 5px;
		font: 10px "Lucida Grande", Helvetica, Verdana, Arial;
	}

	th, td {
		padding: 1px 10px 1px 4px;
		vertical-align: top;
		height: 15px;
		border: 1px solid black;
		border-top: none;
		color: black;
	}
	
	th {
		background-color: #82CFEF;
		color: white;
		border: solid 1px black;	
	}
	
	tbody tr {
		border: 1px solid black;
		background-color:  #A4C3CC;
	}
	
	tbody tr.even {
		background-color: #9AB7BF;
	
	}
	
	a:link, a:active, a:visited {
		text-decoration: none;
	}
	
	.button {
		border: 1px solid black;
		font: 12px "Lucida Grande", Helvetica, Verdana, Arial;
		background-color: white;
	}

.sort {
    text-decoration: none;
    color: #888;
    background-color: #fea;
    border: 1px solid #ddd;
    width: 100px;
    padding: 5px 0;
    text-align: center;
}	
	
	#disp{
		float: left; 
		margin: 110px 0 0 200px; 
		display: none; 
		background-color: #F3FEF2;
		 
		z-index: 1000; 
		position: relative;
		border: black 1px solid;
	}

</style>

	<div style='margin: 0 0 20px 20px;'><a style='color: white; font-weight: bold' href='?action=admin'>&laquo; Zur&uuml;ck</a></div>
	<div id="info" style="color: white; z-index: 99; position: absolute; background-color: green; border: black 1px solid; width: 300px;  padding: 10px; margin-left: 250px; display: none; text-align:center"></div>
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">SCs (<?=$data['sc_count'];?>)</a></li>
			<li><a href="#tabs-2">NSCs (<?=$data['nsc_count'];?>)</a></li>
			<li><a href="#tabs-3">gelöschte Teilnehmer  (<?=$data['deleted_count'];?>)</a></li>
		</ul>
		
	<div id="tabs-1" >	
		<div style='border: 1px solid #ddd; padding: 10px 10px 10px 10px; width: 898px; background-color: #5C6D73;'>
		<table id="large1" cellspacing="0" width="798">
			<thead>
				<tr>
					<th class='tbl_head' style='width: 150px'>Nachname</th>			
					<th class='tbl_head' style='width: 150px'>Vorname</th>
					<th class='tbl_head'>Datum</th>
					<th class='tbl_head'>Bezahlt</th>
					<th class='tbl_head'>Warteliste</th>
					<th class='tbl_head'>WL-Rang</th>
					<th class='tbl_head'>Orga Nachricht</th>					
					<th class='tbl_head' style='width: 20px'>L&ouml;schen</th>
				</tr>
				<tr><td colspan='6' style='border: none; border-bottom: 1px solid black; background-color: #5C6D73;'></td></tR>			
			</thead>

			<tbody>
				<?php if(!empty($data['sc'])): ?>
					<?php foreach ($data['sc'] as $datas): ?>
						<?php
							$status_wl  = ($datas['warteliste'] == '1') ? 'glyphicon glyphicon-ok text-success' : 'glyphicon glyphicon-remove text-danger';
							$status_bez  = ($datas['bezahlt'] == '1') ? 'glyphicon glyphicon-ok text-success' : 'glyphicon glyphicon-remove text-danger';
							$type = ($datas['rang'] == '1') ? 'SC' : 'NSC'; 
						?>
						<tr>
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['nachname'])?></div></a></td>							
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['vorname'])?></div></a></td>
							<td class='cell' style='text-align: center'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=date('d.m.Y', $datas['datum'])?></div></a></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer' onclick="changeStatus('<?=$datas['id']?>')" id="status_<?=$datas['id']?>"><span class='<?php echo $status_bez; ?>' aria-hidden='false'></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer' onclick="changeList('<?=$datas['id']?>')" id="liste_<?=$datas['id']?>"><span class='<?php echo $status_wl; ?>' aria-hidden='false'></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer' id="liste_rang_<?=$datas['id']?>"><?=$datas['warteliste_rang']?></td>												
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer'><?=$datas['orga_message']?></td>						
							<td class='cell' style='text-align:center'>										
								<a href='javascript:void();' onClick="if (confirm('Diesen Teilnehmer wirklich l&ouml;schen?')) window.location='?action=delete&id=<?=$datas['id']?>'"><img src='xcms/views/images/icons/delete.png' border='0' title='L&ouml;schen'></a>
							</td>
						</tr>
					<?php endforeach; ?>	
				<?php else: ?>
					<tr><td colspan='7' style='text-align: center; padding: 5px'>Es sind keine Eintr&auml;ge im Bereich SCs zu finden.</td></tr>
				<?php endif; ?>

			</tbody>
		</table>
		</div>
	</div>
	<div id="tabs-2">
		<div style='border: 1px solid #ddd; padding: 10px 10px 10px 10px; width: 898px;background-color: #5C6D73;'>
		<table id="large2" cellspacing="0" width="798">
			<thead>
				<tr>
					<th class='tbl_head' style='width: 150px'>Nachname</th>			
					<th class='tbl_head' style='width: 150px'>Vorname</th>
					<th class='tbl_head'>Datum</th>
					<th class='tbl_head'>Bezahlt</th>
					<th class='tbl_head'>Warteliste</th>
					<th class='tbl_head'>WL-Rang</th>					
					<th class='tbl_head'>Orga Nachricht</th>					
					<th class='tbl_head' style='width:20px'>L&ouml;schen</th>
				</tr>
			<tr><td colspan='6' style='border: none; border-bottom: 1px solid black; background-color: #5C6D73;'></td></tR>
			</thead>
			<tbody>
				<?php if(is_array($data['nsc'][0])): ?>
					<?php foreach ($data['nsc'] as $datas): ?>

						<?php
							$status_wl  = ($datas['warteliste'] == '1') ? 'glyphicon glyphicon-ok text-success' : 'glyphicon glyphicon-remove text-danger';
							$status_bez  = ($datas['bezahlt'] == '1') ? 'glyphicon glyphicon-ok text-success' : 'glyphicon glyphicon-remove text-danger';
							$type = ($datas['rang'] == '1') ? 'SC' : 'NSC'; 
						?>


						<tr>
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['nachname'])?></div></a></td>							
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['vorname'])?></div></a></td>
							<td class='cell' style='text-align: center'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=date('d.m.Y', $datas['datum'])?></div></a></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer' onclick="changeStatus('<?=$datas['id']?>')" id="status_<?=$datas['id']?>"><span class='<?php echo $status_bez; ?>' aria-hidden='false'></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer' onclick="changeList('<?=$datas['id']?>')" id="liste_<?=$datas['id']?>"><span class='<?php echo $status_wl; ?>' aria-hidden='false'></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer' id="liste_rang_<?=$datas['id']?>"><?=$datas['warteliste_rang']?></td>	
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer'><?=$datas['orga_message']?></td>							
							<td class='cell' style='text-align:center'>										
								<a href='javascript:void();' onClick="if (confirm('Diesen Teilnehmer wirklich l&ouml;schen?')) window.location='?action=delete&id=<?=$datas['id']?>'"><img src='xcms/views/images/icons/delete.png' border='0' title='L&ouml;schen'></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr><td colspan='7' style='text-align: center; padding: 5px'>Es sind keine Eintr&auml;ge im Bereich NSCs zu finden.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		</div>
	 </div>	 
<div id="tabs-3">
		<div style='border: 1px solid #ddd; padding: 10px 10px 10px 10px; width: 898px;background-color: #5C6D73;'>
		<table id="large3" cellspacing="0" width="798">
			<thead>
				<tr>
					<th class='tbl_head' style='width: 150px'>Nachname</th>			
					<th class='tbl_head' style='width: 150px'>Vorname</th>
					<th class='tbl_head' style='width: 150px'>Rang</th>
					<th class='tbl_head'>AnmeldeDatum</th>
					<th class='tbl_head'>LöschDatum</th>
					<th class='tbl_head'>Orga Nachricht</th>
				</tr>
			<tr><td colspan='6' style='border: none; border-bottom: 1px solid black; background-color: #5C6D73;'></td></tR>
			</thead>
			<tbody>
				<?php if(is_array($data['deleted'][0])): ?>
					<?php foreach ($data['deleted'] as $datas): ?>

						<tr>
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['nachname'])?></div></a></td>							
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['vorname'])?></div></a></td>
							<td class='cell'><a href='?action=edit&id=<?=$datas['id']?>' title='Editieren'><div style='height:18px;padding-top:3px'><?=ucfirst($datas['rang'])?></div></a></td>							
							<td class='cell' style='text-align: center'><div style='height:18px;padding-top:3px'><?=date('d.m.Y', $datas['datum'])?></div></a></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer'><?=date('d.m.Y', $datas['deleted_date'])?></td>
							<td class='cell' style='text-align: center;margin-left: 0px;cursor:pointer'><?=$datas['orga_message']?></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr><td colspan='7' style='text-align: center; padding: 5px'>Es sind keine Eintr&auml;ge im Bereich Gelöscht zu finden.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		</div>
	 </div>	 	 
	 </div> 
</div>

<? else: ?>
	<script>self.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?action=logmein'</script>
<? endif;?>
