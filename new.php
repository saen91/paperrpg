<?php
/**
 * Zeitungsplugin
 *
 */
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );
// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

//HOOK HAUPTSEITE
$plugins->add_hook('misc_start', 'paper_misc');

function zeitungenrpg_info()
{
	return array(
		'name' => "Zeitungen RPG",
		'description' => "Ein Plugin mit welchem Zeitungen und Artikel erstellt werden können",
		'author' => "saen",
		'authorsite' => "https://github.com/saen91",
		'version' => "1.0",
		'compatibility' => "18*"
	);
}

function zeitungenrpg_install()
{
	global $db, $mybb;

	//LEGE TABELLE AN Zeitung
	$db->write_query("CREATE TABLE `" . TABLE_PREFIX . "paper` (
	`zid` int(11)  NOT NULL auto_increment,
	`zpicture` varchar (255) CHARACTER SET utf8 NOT NULL,
	`action` varchar(140) NOT NULL,
	`paper` varchar(500) CHARACTER SET utf8 NOT NULL,
	`paperdesc` longtext CHARACTER SET utf8 NOT NULL,
	`papercreator` int(10) UNSIGNED NOT NULL,	
	PRIMARY KEY (`zid`)
	) ENGINE=MyISAM" . $db->build_create_table_collation());

	//LEGE TABELLE AN Artikel
	$db->write_query("CREATE TABLE `" . TABLE_PREFIX . "paper_article` (
	`aid` int(11) NOT NULL  AUTO_INCREMENT,	
	`zid` int(11) NOT NULL,	
	`articletitle` varchar (255) CHARACTER SET utf8 NOT NULL,
	`article` longtext CHARACTER SET utf8 NOT NULL,
	`werbung` longtext CHARACTER SET utf8 NOT NULL,
	`articlepicture` varchar (255) CHARACTER SET utf8 NOT NULL,
	`articleauthor` varchar (255) CHARACTER SET utf8 NOT NULL,
	`articledate` varchar (140) NOT NULL, 
	`zeitungenrpg_rubriken` varchar(500) CHARACTER SET utf8 NOT NULL,
	`articlecreator` int(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`aid`)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

	//LEGE TABELLE AN Images hochladen
	$db->write_query("CREATE TABLE `" . TABLE_PREFIX . "paper_imgs` (
	`paper_imgId` int(11) NOT NULL AUTO_INCREMENT,
	`paper_filesize` int(11) NOT NULL,
	`paper_filename` varchar(200) NOT NULL,
	`paper_width` int(11) NOT NULL,
	`paper_height` int(11) NOT NULL,
	`paper_uid` int(11) NOT NULL,
	`paper_aid` int(11) NOT NULL,
	`paper_type` varchar(11) NOT NULL,
	PRIMARY KEY (`paper_imgId`)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");

	if (!file_exists(MYBB_ROOT . 'uploads/paper'))
	{
		mkdir(MYBB_ROOT . 'uploads/paper', 0755, true);
	}

	// EINSTELLUNGEN
	$setting_group = ['name' => 'zeitungenrpg', 'title' => 'Zeitungen, Arikel für RPG', 'description' => 'Zeitungen und Arikel für RPG Einstellungen', 'disporder' => 1, 'isdefault' => 0];

	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = ['zeitungenrpg_allow_groups_articel' => array(
		'title' => 'Erlaubte Gruppen: Artikel',
		'description' => 'Welche Gruppen dürfen Artikel einreichen?',
		'optionscode' => 'groupselect',
		'value' => '2,4',
		'disporder' => 1
	) , 'zeitungenrpg_allow_groups_paper' => array(
		'title' => 'Erlaubte Gruppen: Zeitungen',
		'description' => 'Welche Gruppen dürfen Zeitungen eintragen?',
		'optionscode' => 'groupselect',
		'value' => '2,4',
		'disporder' => 2
	) , 'zeitungenrpg_rubriken' => array(
		'title' => 'Welche Rubriken gibt es?',
		'description' => 'Welche Rubriken sollen beim erfassen von Artikeln auswählbar sein?',
		'optionscode' => 'text',
		'value' => 'Politik, Unterhaltung, Sport', //Kann angepasst werden
		'disporder' => 3
	) , 'zeitungenrpg_uploadImg' => array(
		'title' => 'Artikel: Bilder hochladen',
		'description' => 'Dürfen Mitglieder Bilder für die Artikel hochladen?',
		'optionscode' => 'yesno',
		'value' => '1', // Default
		'disporder' => 4
	) ,
	'zeitungenrpg_uploadImgSize' => array(
		'title' => 'Artikel: Dateigröße',
		'description' => 'Gebe hier die Dateigröße von den Bildern an, die User hochladen können.',
		'optionscode' => 'text',
		'value' => '2000000', // Default
		'disporder' => 5
	) , 'zeitungenrpg_uploadImgWidth' => array(
		'title' => 'Artikel: Breite der Bilder',
		'description' => 'Gebe hier die Breite der Bilder an.',
		'optionscode' => 'text',
		'value' => '400', // Default
		'disporder' => 6
	) ,

	'zeitungenrpg_uploadImgHeight' => array(
		'title' => 'Artikel: Höhe der Bilder',
		'description' => 'Gebe hier die Höhe der Bilder an.',
		'optionscode' => 'text',
		'value' => '200', // Default
		'disporder' => 7
	) ];

	foreach ($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}

	rebuild_settings();

	// Template hinzufügen:
	$insert_array = array(
		'title' => 'paper_main',
		'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Zeitungen unsers Forums</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>Zeitungsübersicht</strong></td>
</tr>
<tr>
<td class="trow1" align="center">
<div class="parentpaper">
		<div>{$add_paper}</div> <div>{$add_article}</div> 
	</div> 
	<table border="0" width="80%" cellpadding="{$theme[\'tablespace\']}" style="margin:auto;">
	<tr>
		<td class="thead"><strong>Zeitungsübersicht</strong></td>
	</tr>
	<tr>
		<td>
		{$paper_view}
		</td>
	</tr>
	</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_viewpaper',
		'template' => $db->escape_string('<div id="paperback">
	<table width="100%">
		<tr>
			<td align="center" colspan="2" valign="top">
				<div class="papertitel"><a href="misc.php?paperentry={$paper[\'action\']}&paperid={$paper[\'zid\']}">{$paper[\'paper\']}</a></div>
			</td>
		</tr>
		<tr>
			<td align="left" valign="top" width="100px"><img src="{$paper[\'zpicture\']}" class="paperimg">               
			</td>
			<td align="left" valign="top">
				<div class="paperdesc firefoxscroll chromescroll">{$paper[\'paperdesc\']}</div>
			</td>
		</tr>
	</table>            
</div>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_article_overview',
		'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Artikelübersicht</title>
{$headerinclude}
</head>
<body>
{$header}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="5"><strong>Artikelübersicht </strong></td>
	</tr>
	<tr>
		<td width="20%" align="center" class="trow1">Rubrik</td>
		<td width="30%" align="center" class="trow1">Titel</td>
		<td width="30%" align="center" class="trow1">Autor</td>
		<td width="10%" align="center" class="trow1">Datum</td>
		<td width="10%" align="center" class="trow1"></td>
	</tr>
	{$article_overview}
	</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_article_overview_bit',
		'template' => $db->escape_string('<tr>
		<td>{$paper_article[\'zeitungenrpg_rubriken\']}</td>
		<td><a href="misc.php?articleentry={$paper_article[\'articletitle\']}&articleid={$paper_article[\'aid\']}">{$paper_article[\'articletitle\']}</a></td>
		<td>{$paper_article[\'articleauthor\']}</td>
		<td>{$articledate}</td>
		<td>{$article_options}</td>
</tr>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_add_paper',
		'template' => $db->escape_string('<a href="misc.php?action=add_paper">Zeitung hinzufügen</a>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_addpaper_formular',
		'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Zeitung hinzufügen</title>
{$headerinclude}
</head>
<body>
{$header}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="2"><strong>Zeitung hinzufügen</strong></td>
	</tr>
	<tr>
		<td class="trow1" align="center" valign="top">
		<form id="add_paper" method="post" action="misc.php?action=add_paper">
			<table width="90%">
				<tr><td class="thead" colspan="2"><strong>Zeitung hinzufügen</strong></td></tr>
				<tr>
					<td class="trow1"><strong>Zeitungsname</strong></td>
					<td class="trow2"><input type="text" name="paper" id="paper" placeholder="Zeitungsname" class="textbox" required /> </td>
				</tr>
				<tr>
					<td class="trow1"><strong>Beschreibung der Zeitung</strong><smalltext></td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="paperdesc" id="paperdesc" placeholder="Absätze sind immer mit <*br> (ohne Sternchen) darzustellen." rows="6" cols="30" style="width: 95%"></textarea></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Zeitungslink</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="page" id="page" placeholder="Linkname für Zeitung" class="textbox" required /></td>
				</tr>
				<tr><td class="tcat" colspan="2" align="center"><input type="submit" name="send_paper" id="submit" class="button"></td></tr>
			</table>
		</form>
		</td>
	</tr>
	</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_article_view',
		'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} -{$article[\'articletitle\']} </title>
{$headerinclude}
</head>
<body>
{$header}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">	
	<tr><td class="thead"><strong>{$article[\'articletitle\']}</strong></td></tr>
	<tr><td>
		<div id="ridenewspaper">
			<div id="rnh1">{$article[\'paper\']}</div>
				<div id="rnflex1">
					<div id="rnflex2">
						<div id="rnflex3">
							<div id="rnflex4">
								{$article[\'articletitle\']}
							</div>
							<div id="rnflex5">
								<div id="rndatum">{$articledate}</div>
								<div id="rndreieck"></div>
							</div>
						</div>

						<div id="rntitle">
							{$article[\'articletitle\']}
						</div>
						<div id="rntext">
							{$article[\'article\']}
							<div id="rnauthor">{$article[\'articleauthor\']}
							</div>
							{$article[\'articlepicture\']}
						</div>
						{$article[\'werbung\']}
					</div>

					<div id="rnflex6">
						<div style="background: url(\'{$article[\'zpicture\']}\'); background-position: center;" id="rnlogo"></div>
						<div id="rnsidetitle">über {$article[\'paper\']}</div>
						{$article[\'paperdesc\']}
					</div>
			</div>
		</div>
		</td>
	</tr>
	</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_add_article',
		'template' => $db->escape_string('<a href="misc.php?action=add_article">Artikel hinzufügen</a>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_article_edit',
		'template' => $db->escape_string('<head>
<title>{$mybb->settings[\'bbname\']} - Artikel bearbeiten</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>Artikel bearbeiten</strong></td>
</tr>
<tr>
		<td class="trow1" align="center" valign="top" width="90%">
			<form id="edit_article_entry" method="post" action="misc.php?action=articleentry_edit&articleid={$paper_article[\'aid\']}">
				<input type="hidden" name="article_entry" id="article_entry" value="{$aid}" class="textbox" />
					<table width="90%">
						<tr><td class="trow2"><strong>Zeitung auswählen</strong></td>
				<td class="trow2"><select name="paper" required>
					<option value="%" disabled>Zeitung wählen</option>
					{$paper}
					</select> 
				</td></tr>
				<tr><td class="trow2"><strong>Rubrik auswählen</strong></td>
				<td class="trow2"><select name="zeitungenrpg_rubriken" required>
					<option value="%" disabled>Rubrik wählen</option>
					{$zeitungenrpg_rubriken}
					</select> 
				</td></tr>
				<tr>
					<td class="trow1"><strong>Artikeltitel</strong></td>
					<td class="trow2"><input type="text" name="articletitle" id="articletitle" value="$articletitle" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikel</strong></td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="article" id="article" rows="6" cols="30" style="width: 95%">{$articletext}</textarea></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikelbild</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="articlepicture" id="articlepicture" value="$articlepicture" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Autor des Artikels</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="articleauthor" id="articleauthor" value="$articleauthor" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikeldatum</strong></td>
					<td class="trow2" colspan="2"><input type="date" name="articledate" id="articledate" value="$articledate" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Werbung</strong></td>
					<td class="trow2" colspan="2">						
						<textarea class="textarea" name="werbung" id="werbung" rows="6" cols="30" style="width: 95%">{$werbung}</textarea>
					</td>
				</tr>
						<tr><td class="tcat" colspan="2" align="center">
							<input type="submit" name="editarticle" value="Artikel editieren" id="submit" class="button">
							
							<input type="hidden" name="articleid" value="$articleid">
							<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
						</td></tr>
					</table>
			</form>
		</td>
		</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_addarticle_formular',
		'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Artikel hinzufügen</title>
{$headerinclude}
</head>
<body>
{$header}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="2"><strong>Artikel hinzufügen</strong></td>
	</tr>
	<tr>
		<td class="trow1" align="center" valign="top">
		<form id="add_article" method="post" action="misc.php?action=add_article" enctype="multipart/form-data">
			<table width="90%">
				<tr><td class="thead" colspan="2"><strong>Artikel hinzufügen</strong></td></tr>
				<tr><td class="trow2"><strong>Zeitung auswählen</strong></td>
				<td class="trow2"><select name="paper" required>
					<option value="%" disabled>Zeitung wählen</option>
					{$paper}
					</select> 
				</td></tr>
				<tr><td class="trow2"><strong>Rubrik auswählen</strong></td>
				<td class="trow2"><select name="zeitungenrpg_rubriken" required>
					<option value="%" disabled>Rubrik wählen</option>
					{$zeitungenrpg_rubriken}
					</select> 
				</td></tr>
				<tr>
					<td class="trow1"><strong>Artikeltitel</strong></td>
					<td class="trow2"><input type="text" name="articletitle" id="articletitle" placeholder="Artikeltitel" class="textbox" required /> </td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikel</strong></td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="article" id="article" rows="6" cols="30" style="width: 95%"></textarea></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikelbild</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="articlepicture" id="articlepicture" placeholder="Link für ein Artikelbild" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikelbild</strong></td>
					<td class="trow2" colspan="2"><input type="file" name="uploadImg" size="60" maxlength="255"></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Autor des Artikels</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="articleauthor" id="articleauthor" placeholder="Name des Autors" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikeldatum</strong></td>
					<td class="trow2" colspan="2"><input type="date" name="articledate" id="articledate" class="textbox" required /></td>
				</tr>			
				<tr>
					<td class="trow1"><strong>Werbung</strong></td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="werbung" id="werbung"  placeholder="Gebe hier Werbung oder zusätzliche Informationen an wie bspw. Infos über die Person um die es geht." rows="6" cols="30" style="width: 95%"></textarea></td>
				</tr>
				<tr><td class="tcat" colspan="2" align="center"><input type="submit" name="send_article" id="submit" class="button"></td></tr>
			</table>
		</form>
		</td>
	</tr>
	</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'paper_edit',
		'template' => $db->escape_string('<head>
<title>{$mybb->settings[\'bbname\']} - Zeitung bearbeiten</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>Zeitung bearbeiten</strong></td>
</tr>
<tr>
		<td class="trow1" align="center" valign="top" width="90%">
			<form id="edit_paper" method="post" action="misc.php?action=paper_edit&paperid={$paperrow[\'zid\']}">
				<input type="hidden" name="paper" id="paper" value="{$zid}" class="textbox" />
				<table width="90%">
				<tr><td class="thead" colspan="2"><strong>Zeitung hinzufügen</strong></td></tr>
				<tr>
					<td class="trow1"><strong>Zeitungsname</strong></td>
					<td class="trow2"><input type="text" name="paper" id="paper" value="$paper" class="textbox" required /> </td>
				</tr>
				<tr>
					<td class="trow1"><strong>Beschreibung der Zeitung</strong><smalltext></td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="paperdesc" id="paperdesc" rows="6" cols="30" style="width: 95%">{$paperdesc}</textarea></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Zeitungslink</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="paperaction" id="paperaction" value="$paperaction" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="tcat" colspan="2" align="center">
						<input type="submit" name="editpaper" value="Zeitung editieren" id="submit" class="button">
						
						<input type="hidden" name="paperid" value="$paperid">
						<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					</td>
				</tr>				
			</table>
			</form>
		</td>
		</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	//CSS eingeben
	$css = array(
		'name' => 'paperplugin.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" => '
.parentpaper {
	color: #000;
	margin: 20px 10px;
	display: flex;
	text-align: justify;
	justify-content: space-between;
	width: 80%;
}

.parentpaper > div {
	width: 50%;
	color: #000;
	margin: 20px 10px;
	justify-content: space-between;
	text-align:center;
	background: #0072bf;
	padding: 5px;
}

.parentpaper > div a{
	color: #fff !important;
}

/*Container Zeitungen*/
#paperback {width: 33%; height: 180px; float: left; margin-bottom: 20px; background:#dadada;margin-left: 2px;}
/*Zeitungsname Hauptseite*/
#paperback .papertitel { font-family: prata, serif; font-size: 30px;}
/*Zeitungsbild Hauptseite*/
#paperback .paperimg {width: 100px; height: 100px; margin: 0 auto; margin-bottom: 5px; border: 10px solid rgba(255,255,255,0.5);background-clip: padding-box; } 
/*Zeitungsbeschreibung Hauptseite*/
#paperback .paperdesc { line-height: 18px;  color: #000;  font-family: Verdana, sans-serif;  font-size: 11px;  text-align: justify; padding:0px 6px;max-height:100px;margin-right: 5px;}
/*Scrollbalken*/
#paperback .firefoxscroll {scrollbar-width: thin; scrollbar-color: #383836 #dadada ;}
#paperback .chromescroll {overflow-y: scroll;	padding: 4px;}
#paperback .chromescroll::-webkit-scrollbar {width: 4px;}
#paperback .chromescroll::-webkit-scrollbar-track {background-color: #dadada;}
#paperback .chromescroll::-webkit-scrollbar-thumb {background-color: #383836;}



/*Container Zeitungsartikel*/
#ridenewspaper { line-height: normal;width: 700px; background: #ffffff; box-sizing: border-box; padding: 20px; 	margin: 0 auto;border: #333 solid 1px;}  

/*Zeitungsname oben*/
#ridenewspaper #rnh1 { font-family: prata, serif; font-size: 50px; text-transform: uppercase; font-weight: bold; color: #1b1b1b; text-align: center; margin-bottom: 10px; } 

/*Spaltenbereich als Flex*/
#ridenewspaper #rnflex1 { display: flex; } 

/*Artikelname oben & Datum Container*/
#ridenewspaper #rnflex3 { display: flex; margin-bottom: 10px; border-top: solid 1px #efefef; border-bottom: solid 1px #efefef; } 
/*Artikelname klein, oben*/
#ridenewspaper #rnflex4 { width: 75%; height: 20px; overflow: hidden; line-height: 20px; text-transform: uppercase; font-weight: bold; color: #1b1b1b; font-family: Mulish, sans-serif; font-size: 12px; }
/*Datum*/
#ridenewspaper #rnflex5 { width: 25%; overflow: auto; }
#ridenewspaper #rndreieck { width: 0; height: 0; border-style: solid; border-width: 0 0 20px 20px; border-color: transparent transparent #efefef transparent; }
#ridenewspaper #rndatum { float: right; box-sizing: border-box; padding: 2px 10px 0px 11px; background: #efefef; text-align: center; height: 20px; color: #000; font-family: Verdana, sans-serif; font-size: 12px; } 

/*Über die Zeitung Container*/
#ridenewspaper #rnflex6 { line-height: 17px; width: 30%; background: #efefef; box-sizing: border-box; padding: 5px; color: #000; font-family: Verdana, sans-serif; font-size: 10px; text-align: justify; }
/*Über Zeitung Logo & Titel*/
#ridenewspaper #rnlogo { width: 100px; height: 100px; margin: 0 auto; margin-bottom: 5px; } 
#ridenewspaper #rnsidetitle { text-transform: uppercase; font-weight: bold; color: #1b1b1b; font-size: 12px; font-family: Mulish, sans-serif; }

/*Artikelbereich*/
#ridenewspaper #rnflex2 { width: 70%; box-sizing: border-box; padding-right: 10px; } 
/*Großer Artikelname*/
#ridenewspaper #rntitle { font-family: prata, serif; font-size: 35px; font-weight: bold; color: #1b1b1b; padding: 0px 30px 10px 20px; }
/*Artikeltext*/
#ridenewspaper #rntext { column-count: 2; column-gap: 20px; line-height: 19px; color: #000; font-family: Verdana, sans-serif; font-size: 12px; text-align: justify; } 
#ridenewspaper #rntext::first-letter { float: left; margin: 9px 5px 5px 0px; font-size: 40px; font-family: prata, serif; }
#ridenewspaper #rnauthor {font-style:italic;font-family: Verdana, sans-serif; font-size: 12px; text-align:right;}
/*Artikelbild*/
#ridenewspaper #rnimg { width: 100%; }

/*Footerbereich*/
#ridenewspaper #rnfooter { margin-top: 10px; padding-top: 10px; border-top: solid 1px #dcdcdc; line-height: 17px; color: #000; font-family: Verdana, sans-serif; font-size: 10px; text-align: justify; }
#ridenewspaper #rnpfeil { float: left; margin: 0px 5px 0px 0px; font-size: 20px; font-weight: bold; } ',
		'cachefile' => 'paperplugin.css',
		'lastmodified' => time()
	);
	require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

	$sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array(
		"cachefile" => "css.php?stylesheet=" . $sid
	) , "sid = '" . $sid . "'", 1);

	$tids = $db->simple_select("themes", "tid");
	while ($theme = $db->fetch_array($tids))
	{
		update_theme_stylesheet_list($theme['tid']);
	}

	rebuild_settings();
	
	if (!is_writable(MYBB_ROOT . 'uploads/paper/')) {
        @chmod(MYBB_ROOT . 'uploads/paper/', 0755);
    }

}

//INSTALLIEREN VOM PLUGIN
function zeitungenrpg_is_installed()
{
	global $db;
	if ($db->table_exists("paper_article"))
	{
		return true;
	}
	return false;
}

//DEINSTALLIEREN VOM PLUGIN
function zeitungenrpg_uninstall()
{
	global $db;

	if ($db->table_exists("paper"))
	{
		$db->drop_table("paper");
	}

	if ($db->table_exists("paper_article"))
	{
		$db->drop_table("paper_article");
	}

	if ($db->table_exists("paper_imgs"))
	{
		$db->drop_table("paper_imgs");
	}

	$db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='zeitungenrpg'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_allow_groups_articel'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_allow_groups_paper'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_rubriken'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_uploadImg'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_uploadImgSize'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_uploadImgWidth'");
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='zeitungenrpg_uploadImgHeight'");

	$db->delete_query("templates", "title LIKE '%paper%'");
	rebuild_settings();
}

//AKTIVIEREN VOM PLUGIN
function zeitungenrpg_activate()
{
	global $db, $cache;
	require MYBB_ROOT . "/inc/adminfunctions_templates.php";

}

//DEAKTIVIEREN VOM PLUGIN
function zeitungenrpg_deactivate()
{
	global $db, $cache;
	require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

	// STYLESHEET ENTFERNEN
	$db->delete_query("themestylesheets", "name = 'paperplugin.css'");
	$query = $db->simple_select("themes", "tid");
	while ($theme = $db->fetch_array($query))
	{
		update_theme_stylesheet_list($theme['tid']);
	}
}

/** **
 * Upload of images
 * @param int $id to which id of Post or answer
 * @param string $type post or answer
 ** **
 */
function uploadImg()
{
	global $db, $mybb;
	
	if(!isset($_GET['file'])) {
	die("Bitte eine Datei auswählen");
	}

	$uploadImgWidth = intval($mybb->settings['zeitungenrpg_uploadImgWidth']);
	$uploadImgHeight = intval($mybb->settings['zeitungenrpg_uploadImgHeight']);
	$maxfilesize = intval($mybb->settings['zeitungenrpg_uploadImgSize']);
	$fail = false;
	$sizes = getimagesize($_FILES['uploadImg']['tmp_name']);

	$imgpath = "uploads/paper/";
	if (!is_writable('uploads/paper/'))
	{
		echo "<script>alert('Der Pfad ist nicht beschreibar.')</script>";
	}

	if ($sizes === false)
	{
		@unlink($imgpath);
		move_uploaded_file($_FILES['uploadImg']['tmp_name'], 'uploads/paper/' . $_FILES['uploadImg']['name']);
		$_FILES['uploadImg']['tmp_name'] = $imgpath;
		$sizes = getimagesize($_FILES['uploadImg']['tmp_name']);
		$fail = true;
	}

	// No size, so something could be wrong with image
	if ($sizes === false)
	{
		echo "<script>alert('Dein Bild entspricht nicht den erlaubten Maßen.')</script>";
	}
	elseif ((!empty($uploadImgWidth) && $sizes[0] > $uploadImgWidth) || (!empty($uploadImgHeight) && $sizes[1] > $uploadImgHeight))
	{
		@unlink($_FILES['uploadImg']['tmp_name']); //delete
		echo "<script>alert('Dein Bild entspricht nicht den erlaubten Maßen.')</script>";
	}

	else
	{

		$filesize = $_FILES['uploadImg']['size'];
		if (!empty($maxfilesize) && $filesize > $maxfilesize)
		{
			@unlink($_FILES['uploadImg']['tmp_name']); //delete
			echo "<script>alert('Die Dateigröße des Bildes ist zu groß.')</script>";
		}

		$filetypes = array(
			1 => 'gif',
			2 => 'jpeg',
			3 => 'png',
			4 => 'bmp',
			5 => 'tiff',
			6 => 'jpg',
		);

		if (isset($filetypes[$sizes[2]]))
		{
			$filetyp = $filetypes[$sizes[2]];
		}
		else
		{
			$filetyp = '.bmp';
		}
		$filename = $mybb->user['uid'] . '-' . date('d_m_y_g_i_s') . '.' . $filetyp;

		if ($fail == false)
		{
			move_uploaded_file($_FILES['uploadImg']['tmp_name'], $imgpath . $filename);
		}
		else
		{
			rename($_FILES['uploadImg']['tmp_name'], $imgpath . $filename);
		}
		@chmod($imgpath . $filename, 0644);
		$db->write_query("INSERT INTO " . TABLE_PREFIX . "paper_imgs
						(paper_filesize, paper_filename, paper_width, paper_height, paper_uid, paper_aid, paper_type)
						VALUES ( $filesize,'$filename', $sizes[0], $sizes[1], " . $mybb->user['uid'] . ")");
	}

}

function paper_misc()
{
	global $db, $cache, $mybb, $templates, $theme, $header, $headerinclude, $footer, $page, $paper, $sendnew_article,$sendnew_paper, $zeitungenrpg_rubriken;

//----------------------------------------------------------HAUPTSEITE ERSTELLEN
	if ($mybb->input['action'] == "paper")
	{
		// Add a breadcrumb
		add_breadcrumb('Zeitungen', "misc.php?action=paper");

		eval("\$add_paper = \"" . $templates->get("paper_add_paper") . "\";");
		eval("\$add_article = \"" . $templates->get("paper_add_article") . "\";");

		//ZEITUNGSÜBERSICHT AUF HAUPTSEITE ERWEITERT SICH IMMER BEI EINEM NEUEN ZEITUNGSEINTRAG
		$sql = "SELECT * FROM " . TABLE_PREFIX . "paper";
		$query = $db->query($sql);
		while ($paper = $db->fetch_array($query))
		{
			$zid = $paper['zid'];
			$papercreator = $paper['papercreator'];
						
			if ($papercreator == $mybb->user['uid'] || $mybb->usergroup['cancp'] == 1)
			{
				$paperedit ="<a href=\"misc.php?action=paper_edit&paperid={$paper['zid']}&my_post_key={$mybb->post_code}\"><i class='far fa-edit' title='Zeitung bearbeiten'></i></a>";
			}
			else 
			{
				$paperedit ="";
			}
			
			
			if ($mybb->usergroup['cancp'] == 1)
			{
				$paperdelete ="<a href=\"misc.php?action=paperdelete&paperid={$paper['zid']}&my_post_key={$mybb->post_code}\"><i class='far fa-trash-alt' title='Zeitung löschen'></i></a>";
			}
			else 
			{
				$paperdelete ="";
			}
						
			
			eval("\$paper_view .= \"" . $templates->get("paper_viewpaper") . "\";");
		}

		eval("\$page = \"" . $templates->get("paper_main") . "\";");
		output_page($page);
	}

//----------------------------------------------------------ÜBERSICHT NACHDEM MAN AUF DEN ZEITUNGSNAMEN GEKLICKT HAT
	$paperentry = $mybb->input['paperentry'];
	$paperid = $mybb->input['paperid'];

	if ($paperentry)
	{

		add_breadcrumb('Zeitungen', "misc.php?action=paper");
		add_breadcrumb('Artikelübersicht', "misc.php?paperentry={$paper['paper']}&{$paper['zid']}");

		$articlesql = $db->query("SELECT * 
		FROM " . TABLE_PREFIX . "paper_article
		where zid = '" . $paperid . "'
		ORDER BY articledate ASC");
		while ($paper_article = $db->fetch_array($articlesql))
		{

			$aid = $paper_article['aid'];
			$articlecreator = $paper_article['articlecreator'];
			$articledate = date("d.m.Y", strtotime($paper_article['articledate'])); //Datum richtig formatieren nach Tag.Monat.Jahr


			if ($articlecreator == $mybb->user['uid'] || $mybb->usergroup['cancp'] == 1)
			{
				$article_options = "<a href=\"misc.php?action=articleentry_edit&articleid={$paper_article['aid']}&my_post_key={$mybb->post_code}\"><i class='far fa-edit' title='Artikel bearbeiten'></i></a> | <a href=\"misc.php?action=articledelete&articleid={$paper_article['aid']}&my_post_key={$mybb->post_code}\"><i class='far fa-trash-alt' title='Artikel löschen'></i></a>";
			}
			else
			{
				$article_options = "";
			}

			eval("\$article_overview .= \"" . $templates->get("paper_article_overview_bit") . "\";");
		}

		eval("\$page = \"" . $templates->get("paper_article_overview") . "\";");
		output_page($page);
	}

//----------------------------------------------------------ÜBERSICHT NACHDEM MAN AUF DEN ARTIKELTITEL GEKLICKT HAT
	$articleentry = $mybb->input['articleentry'];
	$articleid = $mybb->input['articleid'];
	$articletitle = $mybb->input['articletitle'];
	$articlepicture = $mybb->input['articlepicture'];

	if ($articleentry)
	{

		$articleview = "SELECT * 
		FROM " . TABLE_PREFIX . "paper_article pa
		LEFT JOIN  " . TABLE_PREFIX . "paper p
		ON pa.zid = p.zid
		where aid = '" . $articleid . "'
		";
		
		
		$query = $db->query($articleview);
		$article = $db->fetch_array($query);
		$action = ($article['action']);
		$zid = ($article['zid']);
		$articletitle = ($article['articletitle']);
		$articledate = date("d.m.Y", strtotime($article['articledate'])); //Datum richtig formatieren nach Tag.Monat.Jahr
		
		add_breadcrumb('Zeitungen', "misc.php?action=paper");
		add_breadcrumb("Artikelübersicht", "misc.php?paperentry={$action}&paperid={$zid}");
		add_breadcrumb('Artikel anzeigen', "misc.php?articleentry={$paper_article['aid']}");
		
		
		//Wenn kein Artikelbild angegeben ist, dann nichts anzeigen.
		if (!empty($article['articlepicture']))
		{
			$article['articlepicture'] = "<br><br><img src='{$article['articlepicture']}' id='rnimg'>";
		}
		else
		{
			$article['articlepicture'] = "";
		}

		//Wenn kein Werbungstext angegeben ist, dann keinen footer anzeigen
		if (!empty($article['werbung']))
		{
			$article['werbung'] = "<div id='rnfooter'><div id='rnpfeil'>&#x2192;</div> {$article['werbung']}</div>";
		}
		else
		{
			$article['werbung'] = "";
		}

		eval("\$page .= \"" . $templates->get("paper_article_view") . "\";");
		output_page($page);
	}

//----------------------------------------------------------NEUE ZEITUNG HINZUFÜGEN
	if ($mybb->input['action'] == "add_paper")
	{
		if (!is_member($mybb->settings['zeitungenrpg_allow_groups_paper']))
		{
			error_no_permission();
			return;
		}

		// Add a breadcrumb
		add_breadcrumb('Zeitungen', "misc.php?action=paper");
		add_breadcrumb('Zeitung hinzufügen', "misc.php?action=add_paper");

		//ANSTELLE VON INSERT NEHMEN WIR DAS FÜR DAS FORMULAR
		if ($_POST['send_paper'])
		{

			$sendnew_paper = array(
				'zid' => (int)$_POST['paper'],
				'paper' => $db->escape_string($_POST['paper']) ,
				'paperdesc' => $db->escape_string($_POST['paperdesc']) ,
				'action' => $db->escape_string($_POST['page']) ,
				'zpicture' => $db->escape_string($_POST['zpicture']) ,
				'papercreator' => (int)$mybb->user['uid']
			);

			$db->insert_query("paper", $sendnew_paper);
			redirect("misc.php?action=paper");
		}

		eval("\$page = \"" . $templates->get("paper_addpaper_formular") . "\";");
		output_page($page);
	}

//----------------------------------------------------------NEUEN ARTIKEL HINZUFÜGEN
	$articleid = $mybb->input['articleid'];
	
	if ($mybb->input['action'] == "add_article")
	{
		if (!is_member($mybb->settings['zeitungenrpg_allow_groups_articel']))
		{
			error_no_permission();
			return;
		}

		// Add a breadcrumb
		add_breadcrumb('Zeitungen', "misc.php?action=paper");
		add_breadcrumb('Artikel hinzufügen', "misc.php?action=add_article");

		$paperrubrik_setting = $mybb->settings['zeitungenrpg_rubriken'];

		$paper_rubriks = explode(", ", $paperrubrik_setting);

		foreach ($paper_rubriks as $paper_rubrik)
		{
			$zeitungenrpg_rubriken .= "<option value='{$paper_rubrik}'>{$paper_rubrik}</option>";
		}

		$paper_query = $db->query("SELECT *
			FROM " . TABLE_PREFIX . "paper
			ORDER BY paper ASC
			");

		while ($row = $db->fetch_array($paper_query))
		{
			$paper .= "<option value='{$row['zid']}'>{$row['paper']}</option>";
		}

		//ANSTELLE VON INSERT NEHMEN WIR DAS FÜR DAS FORMULAR
		if ($_POST['send_article'])
		{

			$sendnew_article = array(
				'zid' => (int)$_POST['paper'],
				'articlecreator' => (int)$mybb->user['uid'],
				'articletitle' => $db->escape_string($_POST['articletitle']) ,
				'article' => $db->escape_string($_POST['article']) ,
				'werbung' => $db->escape_string($_POST['werbung']) ,
				'articlepicture' => $db->escape_string($_POST['articlepicture']) ,
				'articleauthor' => $db->escape_string($_POST['articleauthor']) ,
				'articledate' => $db->escape_string($_POST['articledate']) ,
				'zeitungenrpg_rubriken' => $db->escape_string($_POST['zeitungenrpg_rubriken'])
			);

			$db->insert_query("paper_article", $sendnew_article);
			redirect("misc.php?action=paper");
		}
		
		if (isset($_FILES['uploadImg']['name']) && $_FILES['uploadImg']['name'] != '') {
                uploadImg($articleid);
            }
		
		if (isset($mybb->input['saveImgarticle'])) {
            uploadImg(intval($mybb->input['articleid']));
        }

		eval("\$page = \"" . $templates->get("paper_addarticle_formular") . "\";");
		output_page($page);
	}

//----------------------------------------------------------HIER KÖNNEN ARTIKEL EDITIERT WERDEN
	if ($mybb->get_input('action') == 'articleentry_edit')
	{
		//wenn eine articleid und ein postkey übergeben wurden
		if (isset($mybb->input['articleid'], $mybb->input["my_post_key"]))
		{
			//lege dafür Variablen an
			$articleid = $mybb->input['articleid'];
			$is_valid = verify_post_check($mybb->input['my_post_key'], true); //user-session-key
			
		}
		else
		{
			$articleid = "";
		}

		// wenn eine articleid und User-Session existieren
		if ($articleid && $is_valid)
		{

			//Zeitungen ausgeben Teil 1
			$paper_query = $db->query("SELECT *
				FROM " . TABLE_PREFIX . "paper
				ORDER BY paper ASC
				");
			

			//Rubriken ausgeben Teil 1
			$paperrubrik_setting = $mybb->settings['zeitungenrpg_rubriken'];
			$paper_rubriks = explode(", ", $paperrubrik_setting);


			//Daten d. Artikels, der editiert werden soll, auslesen
			$article_select = $db->query ("
			SELECT *
			FROM ".TABLE_PREFIX."paper_article pa
			LEFT JOIN ".TABLE_PREFIX."paper p
			ON pa.zid = p.zid
			WHERE aid = '" . $articleid . "'
			");
				
			//$article_select = $db->simple_select("paper_article", "*", "aid = '$articleid'");//

			while ($articlerow = $db->fetch_array($article_select))
			{
				$article_zid = $articlerow['zid'];
				$articletitle = $articlerow['articletitle'];
				$articletext = $articlerow['article'];
				$werbung = $articlerow['werbung'];
				$articlepicture = $articlerow['articlepicture'];
				$articleauthor = $articlerow['articleauthor'];
				$articledate = $articlerow['articledate'];
				$articlerubrik = $articlerow['zeitungenrpg_rubriken'];
				$article_action = $articlerow['action'];
			
				
			// Add a breadcrumb
			add_breadcrumb('Zeitungen', "misc.php?action=paper");			
			add_breadcrumb("Artikelübersicht", "misc.php?paperentry={$article_action}&paperid={$article_zid}");
			add_breadcrumb('Artikel editieren', "misc.php?action=paperentry_edit");
				
				//Zeitungen auslesen Teil 2
				while ($row = $db->fetch_array($paper_query))
				{
					//vergleiche die Zeitungs-ID des ausgewählten Artikels mit allen bestehenden IDs
					// wenn sie gleich sind, also die zid des Artikels der zid aus den settings übereinstimmt
					if ($article_zid == $row['zid'])
					{
						//wähle es mit selected aus
						$paper .= "<option value='{$row['zid']}' selected>{$row['paper']}</option>";
					}
					else
					{	
						//wenn es nicht übereinstimmt, bereite alle anderen zeitungs-optionen ohne selected vor
						$paper .= "<option value='{$row['zid']}'>{$row['paper']}</option>";
					}

				}

				//Rubriken ausgeben Teil 2
				// Artikelauswahl vor-selektieren, je nachdem, was bei der Erstellung bzw. letzten Änderung des Artikels in der DB gespeichert war
				foreach ($paper_rubriks as $paper_rubrik)
				{

					//vergleiche die Rubrik des ausgewählten Artikels mit allen bestehenden Rubriken aus den Settings
					if ($paper_rubrik == $articlerubrik)
					{	
						//wenn sie übereinstimmt, wähle als selected aus
						$zeitungenrpg_rubriken .= "<option value='{$paper_rubrik}' selected>{$paper_rubrik}</option>";
					}
					else
					{	
						//wenn sie nicht übereinstimmt, bereite alle anderen rubrik-optionen ohne selected vor
						$zeitungenrpg_rubriken .= "<option value='{$paper_rubrik}'>{$paper_rubrik}</option>";
					}

				}
			}
						

			//ANSTELLE VON INSERT NEHMEN WIR DAS FÜR DAS FORMULAR
			if ($_POST['editarticle'])
			{
				$aid = $mybb->input['articleid'];
				//alle Daten aus dem Formular in einem Array speichern
				$sendnew_article = array(
					'zid' => (int)$_POST['paper'],
					'articlecreator' => (int)$mybb->user['uid'],
					'articletitle' => $db->escape_string($_POST['articletitle']) ,
					'article' => $db->escape_string($_POST['article']) ,
					'werbung' => $db->escape_string($_POST['werbung']) ,
					'articlepicture' => $db->escape_string($_POST['articlepicture']) ,
					'articleauthor' => $db->escape_string($_POST['articleauthor']) ,
					'articledate' => $db->escape_string($_POST['articledate']) ,
					'zeitungenrpg_rubriken' => $db->escape_string($_POST['zeitungenrpg_rubriken'])
				);

				//hier werden die neu eingegebenen Daten aus dem Formular bzw. dem sendnew_article-array endgültig an die DB geschickt
				$db->update_query("paper_article", $sendnew_article, "aid = '{$aid}'");
				redirect("misc.php?articleentry={$articletitle}&articleid={$aid}");
			}

			eval("\$page = \"" . $templates->get("paper_article_edit") . "\";");
			output_page($page);

		}

	}
	

//----------------------------------------------------------HIER KÖNNEN ARTIKEL GELÖSCHT WERDEN
	if ($mybb->get_input('action') == 'articledelete')
	{
		//wenn eine article und ein postkey übergeben wurden
		if (isset($mybb->input['articleid'], $mybb->input["my_post_key"]))
		{
			//lege dafür Variablen an
			$articleid = $mybb->input['articleid'];
			$is_valid = verify_post_check($mybb->input['my_post_key'], true); //user-session-key
		}
		else
		{
			$articleid = "";
		}
		
		//wenn eine ID und USer-Session existieren 
		if ($articleid && $is_valid)
		{		
			
			//lade den Eintrag aus der DB wo die ID der ID in der URL entspricht
			$result = $db->query ("
			SELECT *
			FROM ".TABLE_PREFIX."paper_article pa
			LEFT JOIN ".TABLE_PREFIX."paper p
			ON pa.zid = p.zid
			WHERE aid = '" . $articleid . "'
			");
					
			
			while ($adeleterow = $db->fetch_array($result)) {
				$articlecreator = $adeleterow['articlecreator'];
				$zid = $adeleterow['zid'];
				$action = $adeleterow['action'];
				$uid = $adeleterow['articlecreator'];
				$articletitle = $adeleterow['articletitle'];
				$article = $adeleterow['article'];
				$werbung = $adeleterow['werbung'];				
				$articlepicture = $adeleterow['articlepicture'];
				$articleauthor = $adeleterow['articleauthor'];
				$articledate = $adeleterow['articledate'];
				$zeitungenrpg_rubriken = $adeleterow['articlezeitungenrpg_rubriken'];
			}
			
			
			// Add a breadcrumb
			add_breadcrumb('Zeitungen', "misc.php?action=paper");
			add_breadcrumb("Artikelübersicht", "misc.php?paperentry={$action}&paperid={$zid}");
			add_breadcrumb('Artikel löschen', "misc.php?action=articledelete");
			
			
					
			//wenn User Teammitglied ist oder Artikel erstellt hat
			
			if ($articlecreator == $mybb->user['uid'] || $mybb->usergroup['cancp'] == 1) 
			{
				if (isset($_POST['articledelete'])) 
				{
					$aid = $mybb->input['articleid'];
					$db->delete_query("paper_article", "aid = '{$aid}'");
					
					//kehre zur Artikelübersicht zurück, wenn Artikel gelöscht ist 
					redirect("misc.php?paperentry={$action}&paperid={$zid}");
				}
				else
				{
					//lade das Template mit Lösch-Bestätigung
					eval("\$page = \"" . $templates->get("paper_deletearticle") . "\";");
				}
				
			}
			else 
			{
				// Fehler "Du hast keine Berechtigung"
				eval("\$page = \"".$templates->get("paper_deleteerror")."\";");
			}
			
		}
		
		// gebe die jeweilige Page aus
		output_page($page);
	}

	
	
//----------------------------------------------------------HIER KÖNNEN ZEITUNGEN EDITIERT WERDEN
	if ($mybb->get_input('action') == 'paper_edit')
	{
		//wenn eine paperid und ein postkey übergeben wurden
		if (isset($mybb->input['paperid'], $mybb->input["my_post_key"]))
		{
			//lege dafür Variablen an
			$paperid = $mybb->input['paperid'];
			$is_valid = verify_post_check($mybb->input['my_post_key'], true); //user-session-key
			
		}
		else
		{
			$paperid = "";
		}

		// wenn eine paperid und User-Session existieren
		if ($paperid && $is_valid)
		{
			// Add a breadcrumb
			add_breadcrumb('Zeitungen', "misc.php?action=paper");
			add_breadcrumb('Zeitung editieren', "misc.php?action=paper_edit");

		
			//Daten d. Zeitung, die editiert werden soll, auslesen
			$paper_select = $db->simple_select("paper", "*", "zid = '$paperid'");

			while ($paperrow = $db->fetch_array($paper_select))
			{
				$zpicture = $paperrow['zpicture'];
				$paperaction = $paperrow['action'];
				$paper = $paperrow['paper'];
				$paperdesc = $paperrow['paperdesc'];
			}
				

			//ANSTELLE VON INSERT NEHMEN WIR DAS FÜR DAS FORMULAR
			if ($_POST['editpaper'])
			{
				$zid = $mybb->input['paperid'];
				//alle Daten aus dem Formular in einem Array speichern
				$sendnew_paper = array(
					'zpicture' => $db->escape_string($_POST['zpicture']),
					'action' => $db->escape_string($_POST['paperaction']),
					'paper' => $db->escape_string($_POST['paper']), 
					'paperdesc' => $db->escape_string($_POST['paperdesc']),
					'papercreator' => (int)$mybb->user['uid']
				);

				//hier werden die neu eingegebenen Daten aus dem Formular bzw. dem sendnew_paper-array endgültig an die DB geschickt
				$db->update_query("paper", $sendnew_paper, "zid = '{$zid}'");
				redirect("misc.php?action=paper");
			}

			eval("\$page = \"" . $templates->get("paper_edit") . "\";");
			output_page($page);

		}

	}
	

//----------------------------------------------------------HIER KÖNNEN ZEITUNGEN GELÖSCHT WERDEN
	if ($mybb->get_input('action') == 'paperdelete')
	{
		//wenn eine paperid und ein postkey übergeben wurden
		if (isset($mybb->input['paperid'], $mybb->input["my_post_key"]))
		{
			//lege dafür Variablen an
			$paperid = $mybb->input['paperid'];
			$is_valid = verify_post_check($mybb->input['my_post_key'], true); //user-session-key
			
		}
		else
		{
			$paperid = "";
		}
		
		//wenn eine ID und USer-Session existieren 
		if ($paperid && $is_valid)
		{	
			
			// Add a breadcrumb
			add_breadcrumb('Zeitungen', "misc.php?action=paper");
			add_breadcrumb('Zeitung löschen', "misc.php?action=paperdelete");
					
			//lade den Eintrag aus der DB wo die ID der ID in der URL entspricht
			$result = $db->query ("
			SELECT *
			FROM ".TABLE_PREFIX."paper 
			WHERE zid = '" . $paperid . "'
			");
			
			while ($pdeleterow = $db->fetch_array($result)) {
				$uid = $pdeleterow['papercreator'];
				$zpicture = $pdeleterow['zpicture'];
				$action = $pdeleterow['action'];
				$paper = $pdeleterow['paper'];
				$paperdesc = $pdeleterow['paperdesc'];
			}
			
			//wenn User Teammitglied ist
			if ($mybb->usergroup['cancp'] == 1) 
			{
				if (isset($_POST['paperdelete'])) 
				{
					$zid = $mybb->input['paperid'];
					$db->delete_query("paper", "zid = '{$zid}'");
					
					//kehre zur Hauptseite zurück, wenn Zeitung gelöscht ist 
					redirect("misc.php?action=paper");
				}
				else
				{
					//lade das Template mit Lösch-Bestätigung
					eval("\$page = \"" . $templates->get("paper_deletepaper") . "\";");
				}
				
			}
			else 
			{
				// Fehler "Du hast keine Berechtigung"
				eval("\$page = \"".$templates->get("paper_deleteerror")."\";");
			}
			
		}
		
		// gebe die jeweilige Page aus
		output_page($page);
	}
					
					

}

