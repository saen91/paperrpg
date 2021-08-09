<?php

/**
 * Zeitungsplugin
 *   
 */
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function zeitungenrpg_info()
{
  return array(
    "name" => "Zeitungen RPG",
    "description" => "Ein Plugin mit welchem Zeitungen und Artikel erstellt werden können",
    "author" => "saen",
	"authorsite" => "https://github.com/saen91",
    "version" => "1.0",
    "compatibility" => "18*"
  );
}


// Diese Funktion installiert das Plugin
// Hier legen wir Datenbanktabellen an, aktualisieren Tabellen, erstellen die Settings für das Plugin und legen die Templates an
function zeitungenrpg_install()
{
	global $db, $mybb; 
	
	//LEGE TABELLE AN Zeitung
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."paper` (
	`zid`int(10) NOT NULL auto_increment,
	`papertitle` varchar (255) CHARACTER SET utf8 NOT NULL,
    `paperdesc` longtext CHARACTER SET utf8 NOT NULL,
    PRIMARY KEY (`zid`)
    ) ENGINE=MyISAM".$db->build_create_table_collation());
	
	//LEGE TABELLE AN Artikel
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."paper_article` (
	`zid` int(11) NOT NULL AUTO_INCREMENT,
	`articletitle` varchar (255) CHARACTER SET utf8 NOT NULL,
	`article` longtext CHARACTER SET utf8 NOT NULL,
	`articlepicture` varchar (255) CHARACTER SET utf8 NOT NULL,
	`articleauthor` varchar (255) CHARACTER SET utf8 NOT NULL,
	`articledate` varchar (140) NOT NULL, 
	PRIMARY KEY (`zid`)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");
	
	// EINSTELLUNGEN 
	$setting_group = [
		'name' => 'zeitungenrpg',
		'title' => 'Zeitungen, Arikel für RPG',
		'description' => 'Zeitungen und Arikel für RPG Einstellungen',
		'disporder' => 1,
		'isdefault' => 0
	];
	
	$gid = $db->insert_query("settinggroups", $setting_group);
	
	$setting_array = [
		'zeitungenrpg_allow_groups_articel' => [
			'title' => 'Erlaubte Gruppen: Artikel',
			'description' => 'Welche Gruppen dürfen Artikel einreichen?',
			'optionscode' => 'groupselect',
            'value' => '2',
			'disporder' => 1
        ],
        'zeitungenrpg_allow_groups_paper' => [
			'title' => 'Erlaubte Gruppen: Zeitungen',
			'description' => 'Welche Gruppen dürfen Zeitungen eintragen?',
			'optionscode' => 'groupselect',
            'value' => '2',
			'disporder' => 1
		]
    ];
	
	foreach($setting_array as $name => $setting)
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
<td>Herzlich Willkommen in einer Überischtsseite unserer möglichen Zeitungen. Wenn ihr auf den Titel einer Zeitung klickt, kommt ihr zu einer Übersicht der jeweiligen Artikel. </td>
</tr>
<tr>
<td class="trow1" align="center">
VARIABLE FÜR ZEITUNGEN EINFÜGEN
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
		
		
	$insert_array = array(
'title' => 'paper_articel_main',
'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Artikelübersicht </title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>Artikelübersicht</strong></td>
</tr>
<tr>
<td class="trow1" align="center" width="50%"><h2>Datum</h2></td>
<td class="trow1" align="center" width="50%"><h2>Artikel</h2></td>
</tr>
<tr>
<td class="trow1" align="center" width="50%">Hier steht das Datum</td>
<td class="trow1" align="center" width="50%">Hier steht der Artikel Titel mit Link</td>
</tr>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);
		
	$insert_array = array(
'title' => 'paper_articel_bit',
'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - ArtikelTITEL </title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>HIER ARTIKELTITEL</strong></td>
</tr>
<tr>
<td class="trow1" align="center">
Hier steht dann der Artikel
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
		);
		$db->insert_query("templates", $insert_array);


$insert_array = array(
        'title'        => 'paper_newarticle',
        'template'    => $db->escape_string('	<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Zeitungen (Neuer Artikel)</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
	<td class="thead" colspan="2"><strong>Neuer Artikel</strong></td>
	</tr>
	<tr>
		<td class="trow1" align="center" valign="top" width="30%">
		</td>
		<td class="trow1" align="center" valign="top">
			<form id="add_article" method="post" action="misc.php?action=add_article">
				<table width="90%">
					<tr><td class="thead" colspan="2"> <strong>Neuen Artikel erstellen</strong></td></tr>
					<tr><td class="trow1"><strong>Zeitung auswählen</strong></td>
					<tr><td class="trow2">
						<select name="paper" required>
							<option value="%">Zeitung wählen</option>
							{$papers}
						</select>
					</td></tr>
					<tr>
						<td class="trow1"><strong>Artikeltitel</strong>
							<div class="smalltext">Wie soll der Titel des Artikels lauten?</div>
						</td>
						<td class="trow2">
							<input type="text" name="articletitle" id="articletitle" placeholder="Artikeltitel" class="textbox" required /> 
						</td>
					</tr>
					<tr>
						<td class="trow1"><strong>Artikelbild</strong>
							<div class="smalltext">Hast du ein Bild für den Artikel?</div>
						</td>
						<td class="trow2">
							<input type="text" name="articlepicture" id="articlepicture" placeholder="link zum Bild" class="textbox" required /> 
						</td>
					</tr>
					<tr>
						<td class="trow1"><strong>Datum</strong>
							<div class="smalltext">Wann ist der Artikel erschienen?</div>
						</td>
						<td class="trow2">
							<input type="text" name="articledate" id="articledate" placeholder="Artikeldatum" class="textbox" required /> 
						</td>
					</tr>
					<tr>
						<td class="trow1"><strong>Artikel</strong>
						</td>
						<td class="trow2">
							<textarea class="textarea" name="article" id="article" rows="6" cols="30" style="width: 95%"></textarea>
						</td>
					</tr>
					<tr>
						<td class="trow1"><strong>Autor</strong>
							<div class="smalltext">Welcher Reporter oder welche Reporterin hat den Artikel verfasst?</div>
						</td>
						<td class="trow2">
							<input type="text" name="articleauthor" id="articleauthor" placeholder="Autor des Artikels" class="textbox" required /> 
						</td>
					</tr>
					<tr>
						<td class="tcat" colspan="2" align="center">
							<input type="submit" name="add_article_entry" value="Eintrag einreichen" id="submit" class="button">
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
{$footer}
</body>
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


}

function zeitungenrpg_is_installed()
{
  global $db;
  if($db->table_exists("paper_article"))
    {
        return true;
    }
    return false;
}

function zeitungenrpg_uninstall()
{
	global $db;
    if($db->table_exists("paper"))
    {
        $db->drop_table("paper");
    }

    if($db->table_exists("paper_article"))
    {
        $db->drop_table("paper_article");
    }

    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='zeitungenrpg'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='zeitungenrpg_allow_groups_articel'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='zeitungenrpg_allow_groups_paper'");

    $db->delete_query("templates", "title LIKE '%paper%'");
    rebuild_settings();
}

//Hier wird das Plugin aktiviert. Ich werfe hier immer die Variablen rein, die in Templates eingefügt werden müssen

function zeitungenrpg_activate()
{
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$menu_calendar}')."#i", '{$menu_calendar} {$paper_header} ');
    
}

function zeitungenrpg_deactivate()
{
  require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$paper_header}')."#i", '', 0);
}

/***
 * Jetzt kommen die Pluginspeziffischen Funktionen
 * MyBB arbeitet hier mit Hooks. Übersetzt 'Haken' Diese Haken sind im Grunde Ansatzpunkte, die in den Mybb Originaldateien
 * zu finden sind. Man wähl einen Hook und setzt dann quasi an der Stelle in der Orginaldatei an, in der der Haken eingefügt ist.
 * 
 * Alle Hooks findet man hier: https://docs.mybb.com/1.8/development/plugins/hooks/ 
 * Wenn du jetzt zum Beispiel etwas am Forumdisplay ändern willst suchst du hier nach 'forumdisplay' und schaust, welche Haken mybb
 * zur Verfügung stellt. In dem Fall finden wir ein paar. Man kann jetzt in den Code schauen, welchen genau man braucht, in dem man sich 
 * die Stelle im forumdisplay.php anschaut. in der Regel sind sie aber vom Namen her schon ziemlich eindeutig :) 
 * Ich verrate dir einfach, dass wir an dieser stelle den hier brauchen: forumdisplay_thread
 * 
 * Jetzt müssen wir der Plugin datei sagen, welche Funktion hier ansetzen soll
 */
$plugins->add_hook("forumdisplay_thread", "profilefieldsForumdisplay_showFields");
//Vorne kommt der Name des Hooks, den haben wir rausgesucht und dann der Name der Funktion, die wir schreiben. 

//jetzt erstellen wir die Funktion
function zeitungenrpg_showFields()
{
  //Hier passiert dann die ganze Magic.
}

//natürlich kann man pro plugin verschiedene Hooks verwenden :) Entsprechen würdest du einfach das 
//add_hook nochmal machen und eben eine weitere funktion erstellen
