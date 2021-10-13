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

//HOOK HAUPTSEITE
$plugins->add_hook('misc_start', 'paper_misc');


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
	`papercreator` int(10) NOT NULL,
    PRIMARY KEY (`zid`)
    ) ENGINE=MyISAM".$db->build_create_table_collation());
	
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
            'value' => '2,4',
			'disporder' => 1
        ],
        'zeitungenrpg_allow_groups_paper' => [
			'title' => 'Erlaubte Gruppen: Zeitungen',
			'description' => 'Welche Gruppen dürfen Zeitungen eintragen?',
			'optionscode' => 'groupselect',
            'value' => '2,4',
			'disporder' => 1
		],
		'zeitungenrpg_rubriken' => [
			'title' => 'Welche Rubriken gibt es?',
			'description' => 'Welche Rubriken sollen beim erfassen von Artikeln auswählbar sein?',
			'optionscode' => 'text',
            'value' => 'Politik, Unterhaltung, Sport', //Kann angepasst werden 
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
<td class="trow1" align="center">
<div class="parentpaper">
		<div>{$add_paper}</div> <div>{$add_article}</div> 
	</div> 
	<table border="0" width="80%" cellpadding="{$theme[\'tablespace\']}" style="margin:auto;">
	<tr>
		<td class="thead"><strong>Zeitungsübersicht</strong></td>
	</tr>
	{$paper_view}
	</table>
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
        'title'        => 'paper_viewpaper',
        'template'    => $db->escape_string('<tr>
		<td class="trow1" align="center" valign="top"><a href="misc.php?paperentry={$paper[\'action\']}&paperid={$paper[\'zid\']}">{$paper[\'paper\']}</a></td>
	</tr>
	<tr>
		<td align="justify">{$paper[\'paperdesc\']}</td>
	</tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
	$insert_array = array(
        'title'        => 'paper_article_overview',
        'template'    => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Artikelübersicht</title>
{$headerinclude}
</head>
<body>
{$header}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4"><strong>Artikelübersicht </strong></td>
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
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
	$insert_array = array(
        'title'        => 'paper_article_overview_bit',
        'template'    => $db->escape_string('<tr>
		<td>{$paper_article[\'zeitungenrpg_rubriken\']}</td>
		<td><a href="misc.php?articleentry={$paper_article[\'articletitle\']}&articleid={$paper_article[\'aid\']}">{$paper_article[\'articletitle\']}</a></td>
		<td>{$paper_article[\'articleauthor\']}</td>
		<td>{$paper_article[\'articledate\']}</td>
		<td>{$article_options}</td>
</tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
	$insert_array = array(
        'title'        => 'paper_add_paper',
        'template'    => $db->escape_string('<a href="misc.php?action=add_paper">Zeitung hinzufügen</a>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
	
	
	$insert_array = array(
        'title'        => 'paper_addpaper_formular',
        'template'    => $db->escape_string('<html>
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
					<td class="trow1"><strong>Beschreibung der Zeitung</strong><smalltext><br>Absätze sind immer mit <*br> (ohne Sternchen) darzustellen.</smalltext> </td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="paperdesc" id="paperdesc" rows="6" cols="30" style="width: 95%"></textarea></td>
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
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
	
	$insert_array = array(
        'title'        => 'paper_article_view',
        'template'    => $db->escape_string('<html>
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
			<div id="rnh1">{$paper[\'paper\']}</div>
				<div id="rnflex1">
					<div id="rnflex2">
						<div id="rnflex3">
							<div id="rnflex4">
								{$article[\'articletitle\']}
							</div>
							<div id="rnflex5">
								<div id="rndatum">{$article[\'articledate\']}</div>
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
							<br><br>
							<img src="{$article[\'articlepicture\']}" id="rnimg">
						</div>
						<div id="rnfooter">
							<div id="rnpfeil">&#x2192;</div> {$article[\'werbung\']}

						</div>
    				</div>

					<div id="rnflex6">
						<div style="background: url(\'https://images.unsplash.com/photo-1584441405886-bc91be61e56a?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=430&q=80\'); background-position: center;" id="rnlogo"></div>
						<div id="rnsidetitle">über {$paper[\'paper\']}</div>
						{$paper[\'paperdesc\']}
    
    				</div>
			</div>
		</div>
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
		
	
	$insert_array = array(
        'title'        => 'paper_add_article',
        'template'    => $db->escape_string('<a href="misc.php?action=add_article">Artikel hinzufügen</a>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
	
	$insert_array = array(
        'title'        => 'paper_addarticle_formular',
        'template'    => $db->escape_string('<html>
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
		<form id="add_article" method="post" action="misc.php?action=add_article">
			<table width="90%">
				<tr><td class="thead" colspan="2"><strong>Artikel hinzufügen</strong></td></tr>
				<tr><td class="trow2"><strong>Zeitung auswählen</strong></td>
				<td class="trow2"><select name="paper" required>
					<option value="%">Zeitung wählen</option>
					{$paper}
					</select> 
				</td></tr>
				<tr><td class="trow2"><strong>Rubrik auswählen</strong></td>
				<td class="trow2"><select name="zeitungenrpg_rubriken" required>
					<option value="%">Rubrik wählen</option>
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
					<td class="trow1"><strong>Autor des Artikels</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="articleauthor" id="articleauthor" placeholder="Name des Autors" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Artikeldatum</strong></td>
					<td class="trow2" colspan="2"><input type="text" name="articledate" id="articledate" placeholder="Artikeldatum TT.MM.JJJJ" class="textbox" required /></td>
				</tr>
				<tr>
					<td class="trow1"><strong>Werbung</strong> Gebe hier Werbung oder zusätzliche Informationen an wie bspw. Infos über die Person um die es geht.</td>
					<td class="trow2" colspan="2"><textarea class="textarea" name="werbung" id="werbung" rows="6" cols="30" style="width: 95%"></textarea></td>
				</tr>
				<tr><td class="tcat" colspan="2" align="center"><input type="submit" name="send_article" id="submit" class="button"></td></tr>
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
	
	//CSS eingeben
	$css = array (
		'name' => 'paperplugin.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" =>	'
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
		'lastmodified' => time ()
	);
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

	$sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);
	
	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}
	
	
	rebuild_settings();
	
}

//INSTALLIEREN VOM PLUGIN
function zeitungenrpg_is_installed()
{
  global $db;
  if($db->table_exists("paper_article"))
    {
        return true;
    }
    return false;
}

//DEINSTALLIEREN VOM PLUGIN
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
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='zeitungenrpg_rubriken'");

    $db->delete_query("templates", "title LIKE '%paper%'");
    rebuild_settings();
}

//AKTIVIEREN VOM PLUGIN
function zeitungenrpg_activate()
{
	global $db, $cache;
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
    
    
}

//DEAKTIVIEREN VOM PLUGIN
function zeitungenrpg_deactivate()
{
	global $db, $cache;
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	
	
	// STYLESHEET ENTFERNEN
    $db->delete_query("themestylesheets", "name = 'paperplugin.css'");
    $query = $db->simple_select("themes", "tid");
    while($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);    }
}



function paper_misc () {
global $db, $cache, $mybb, $templates, $theme, $header, $headerinclude, $footer, $page, $paper, $zeitungenrpg_rubriken ;

	//HAUPTSEITE ERSTELLEN
	if($mybb->input['action'] == "paper")
    {
        // Add a breadcrumb
        add_breadcrumb('Zeitungen', "misc.php?action=paper");
		
		eval("\$add_paper = \"".$templates->get("paper_add_paper")."\";");
		eval("\$add_article = \"".$templates->get("paper_add_article")."\";");
			
		//ZEITUNGSÜBERSICHT AUF HAUPTSEITE ERWEITERT SICH IMMER BEI EINEM NEUEN ZEITUNGSEINTRAG
		$sql = "SELECT * FROM ".TABLE_PREFIX."paper";
    	$query = $db->query($sql);
    	while($paper = $db->fetch_array($query)) {
      		eval("\$paper_view .= \"".$templates->get("paper_viewpaper")."\";");
    	}
		
        eval("\$page = \"".$templates->get("paper_main")."\";");
        output_page($page);
    }
	
	//ÜBERSICHT NACHDEM MAN AUF DEN ZEITUNGSNAMEN GEKLICKT HAT 
	$paperentry = $mybb->input['paperentry'];
	$paperid = $mybb->input['paperid'];

    if($paperentry){
		
		add_breadcrumb('Artikelübersicht', "misc.php?paperentry={$paper['paper']}&{$paper['zid']}");

		$articlesql = $db->query("SELECT * 
		FROM ".TABLE_PREFIX."paper_article
		where zid = '".$paperid."'
		ORDER BY articledate ASC");
		while($paper_article = $db->fetch_array($articlesql)) {
			
			$aid = $paper_article['aid'];
			$articlecreator = $paper_article['articlecreator'];
							
			if ($articlecreator == $mybb->user['uid'] || $mybb->usergroup['cancp'] == 1)  {	
				$article_options = "<a href=\"misc.php?action=articleentry_edit&articleid={$paper_article['aid']}\">Editieren</a> | Löschen";
			}
			else {
            $article_options = "";
        	}
			
      		eval("\$article_overview .= \"".$templates->get("paper_article_overview_bit")."\";");
    	}		

        eval("\$page = \"".$templates->get("paper_article_overview")."\";");
        output_page($page);
    }
	
	//ÜBERSICHT NACHDEM MAN AUF DEN ARTIKELTITEL GEKLICKT HAT 
	$articleentry = $mybb->input['articleentry'];
	$articleid = $mybb->input['articleid'];
	$articltitle = $mybb->input['articletitle'];
	
    if($articleentry){
		
		
		add_breadcrumb('Artikel anzeigen', "misc.php?articleentry={$paper_article['aid']}");
		
		$articleview = "SELECT * 
		FROM ".TABLE_PREFIX."paper_article
		where aid = '".$articleid."'
		";
		$query = $db->query($articleview);
		$article = $db->fetch_array($query);
		
				
        eval("\$page .= \"".$templates->get("paper_article_view")."\";");
        output_page($page);
    }
	
		
	//NEUE ZEITUNG HINZUFÜGEN     
    if($mybb->input['action'] == "add_paper")
    {
        if (!is_member($mybb->settings['zeitungenrpg_allow_groups_paper'])) {
            error_no_permission();
            return;
             }   
    
            // Add a breadcrumb
            add_breadcrumb('Zeitung hinzufügen', "misc.php?action=add_paper");
			
			//ANSTELLE VON INSERT NEHMEN WIR DAS FÜR DAS FORMULAR
			if($_POST['send_paper']){

                $sendnew_paper = array(
                    "zid" => (int)$_POST['paper'],
                    "paper" => $db->escape_string($_POST['paper']),
                    "paperdesc" => $db->escape_string($_POST['paperdesc']),
                    "action" => $db->escape_string($_POST['page']),
					"zpicture" => $db->escape_string($_POST['zpicture'])
                );

                $db->insert_query("paper", $sendnew_paper);
                redirect("misc.php?action=paper");
            }
    
		eval("\$page = \"".$templates->get("paper_addpaper_formular")."\";");    
		output_page($page);
	}
	
	//NEUEN ARTIKEL HINZUFÜGEN     
    if($mybb->input['action'] == "add_article")
    {
        if (!is_member($mybb->settings['zeitungenrpg_allow_groups_articel'])) {
            error_no_permission();
            return;
             }
    
            // Add a breadcrumb
            add_breadcrumb('Artikel hinzufügen', "misc.php?action=add_article");
			
			$paperrubrik_setting = $mybb->settings['zeitungenrpg_rubriken'];
		
			$paper_rubriks = explode(", ", $paperrubrik_setting);
		
			foreach ($paper_rubriks as $paper_rubrik){
            $zeitungenrpg_rubriken .= "<option value='{$paper_rubrik}'>{$paper_rubrik}</option>";
        	}
			
            $paper_query = $db->query("SELECT *
            FROM ".TABLE_PREFIX."paper
            ORDER BY paper ASC
            ");

            while($row = $db->fetch_array($paper_query)){
                $paper .= "<option value='{$row['zid']}'>{$row['paper']}</option>";
            }
			
		
			
			//ANSTELLE VON INSERT NEHMEN WIR DAS FÜR DAS FORMULAR
			if($_POST['send_article']){

                $sendnew_article = array(
					"zid" => (int)$_POST['paper'],
                    "articletitle" => $db->escape_string($_POST['articletitle']),
                    "article" => $db->escape_string($_POST['article']),
					"werbung" => $db->escape_string($_POST['werbung']),
                    "articlepicture" => $db->escape_string($_POST['articlepicture']),
					"articleauthor" => $db->escape_string($_POST['articleauthor']),
					"articledate" => $db->escape_string($_POST['articledate']),
					"zeitungenrpg_rubriken" => $db->escape_string($_POST['zeitungenrpg_rubriken'])
                );

                $db->insert_query("paper_article", $sendnew_article);
                redirect("misc.php?action=paper");
            }
    
		eval("\$page = \"".$templates->get("paper_addarticle_formular")."\";");    
		output_page($page);
	}
	
	
	//HIER KÖNNEN ARTIKEL EDITIERT WERDEN 	
	if($mybb->get_input('action') == 'articleentry_edit') {
		
		add_breadcrumb('Artikel editieren', "misc.php?action=paperentry_edit");
		
		$aid = $mybb->input['edit'];
		
		$query = $db->query("SELECT *
        FROM ".TABLE_PREFIX."paper_article pa
        LEFT JOIN ".TABLE_PREFIX."paper p
        on (pa.zid = p.zid)
        LEFT JOIN ".TABLE_PREFIX."users u
        on (pa.articlecreator = u.uid)
        WHERE pa.articlecreator = '".$aid."'   
        ");
		
		$row = $db->fetch_array($query);
		
		$aid = "";
        $articletitle = "";
        $aid = "";
		$articlepicture = "";
		$articleauthor = "";
		$articledate = "";
		$zeitungenrpg_rubriken = "";
        $article = "";
		$werbung = "";
        $user = "";
		
		//Füllen wir mal alles mit Informationen
        $username = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
        $user = build_profile_link($username, $row['uid']);
        $articletitle = $row['articletitle'];
        $articlepicture = $row['articlepicture'];
        $articleauthor = $row['articleauthor'];
        $articledate = $row['articledate'];
        $aid = $row['aid'];
        $article = $row['article'];
		$werbung = $row['werbung'];
        $zid = $row['zid'];
        $aid = $row['aid'];
		
		$paper_query = $db->query("SELECT *
            FROM ".TABLE_PREFIX."paper
            ORDER BY paper ASC
            ");

        while($paper = $db->fetch_array($paper_query)){

            if($zid == $zat['zid']){
                $select = "selected=\"selected\"";
            } else {
                $select = "";
            }


            $edit_paperentry .= "<option value='{$zid['zid']}' {$select}>{$zid['paper']} </option>";
        }
		
		//Der neue Inhalt wird nun in die Datenbank eingefügt bzw. die alten daten Überschrieben.
        if($_POST['edit_article_entry']){
            $aid = $mybb->input['aid'];
            $edit_entry = array(
                "zid" => (int)$_POST['paper'],
                "articletitle" => $db->escape_string($_POST['articletitle']),
                "article" => $db->escape_string($_POST['article']),
				"werbung" => $db->escape_string($_POST['werbung']),
                "articlepicture" => $db->escape_string($_POST['articlepicture']),
				"articleauthor" => $db->escape_string($_POST['articleauthor']),
				"articledate" => $db->escape_string($_POST['articledate']),
				"zeitungenrpg_rubriken" => $db->escape_string($_POST['zeitungenrpg_rubriken'])
            );

            $db->update_query("paper_article", $edit_entry, "aid = '".$aid."'");
            redirect("misc.php?action=paper");
        }

        eval("\$page = \"".$templates->get("paper_article_edit")."\";");
        output_page($page);

    }
		
	
 	
}
