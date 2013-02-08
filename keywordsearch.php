<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href="http://chianti.ucsd.edu/~kono/cytoscape/css/main.css" type="text/css" rel="stylesheet" media="screen">
<title>Search results</title>
<script type="text/javascript" 
src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="http://chianti.ucsd.edu/~kono/cytoscape/js/menu_generator.js"></script>

	<SCRIPT LANGUAGE="JavaScript" SRC="mktree.js"></SCRIPT>
    <style type="text/css">
<!--
.style3 {
	font-size: 18;
	color: #0000FF;
}
.style4 {
	font-family: "Courier New", Courier, monospace;
	font-style: italic;
}
.highlight 
{ 
	background: #CEDAEB; 
} 
 
.highlight_important 
{ 
	background: #F8DCB8; 
}

/* Expand/collapse plugin tree */
/* Put this inside a @media qualifier so Netscape 4 ignores it */
@media screen, print { 
	/* Turn off list bullets */
	ul.mktree  li { list-style: none; } 
	/* Control how "spaced out" the tree is */
	ul.mktree, ul.mktree ul , ul.mktree li { margin-left:10px; padding:0px; }
	/* Provide space for our own "bullet" inside the LI */
	ul.mktree  li           .bullet { padding-left: 15px; }
	/* Show "bullets" in the links, depending on the class of the LI that the link's in */
	ul.mktree  li.liOpen    .bullet { cursor: pointer; background: url(../images/minus.gif)  center left no-repeat; }
	ul.mktree  li.liClosed  .bullet { cursor: pointer; background: url(../images/plus.gif)   center left no-repeat; }
	ul.mktree  li.liBullet  .bullet { cursor: default; background: url(bullet.gif) center left no-repeat; }
	/* Sublists are visible or not based on class of parent LI */
	ul.mktree  li.liOpen    ul { display: block; }
	ul.mktree  li.liClosed  ul { display: none; }
	/* Format menu items differently depending on what level of the tree they are in */
	ul.mktree  li { font-size: 12pt; }
	ul.mktree  li ul li { font-size: 11pt; }
	ul.mktree  li ul li ul li { font-size: 10pt; }
	ul.mktree  li ul li ul li ul li { font-size: 8pt; }
}

-->
    </style>

</head>

<body>

<div id="container"> 
   <script src="http://chianti.ucsd.edu/~kono/cytoscape//js/header.js"></script>


<br><br><b>Search words:</b> <?php echo $_POST["searchwords"] ?> <br><br>
<?php


$SearchText = strToUpper($_POST["searchwords"]); 


// Strip multiple whitespace
$SearchText = preg_replace("(\s+)", " ", $SearchText);

// Split text on whitespace
$searchArray =& split( " ", $SearchText );

$wordSQL = "";
$i = 0;
// Build the word query string
foreach ( $searchArray as $searchWord )
{
    if ( $i == 0 )
        $wordSQL .= "word='" . mysql_real_escape_string($searchWord)  ."' ";
    else
        $wordSQL .= " OR word='" . mysql_real_escape_string($searchWord) ."' ";
    $i++;
}

//echo "wordSQL = $wordSQL<br>";

// Include the DBMS credentials
include 'db.inc';

// Connect to the MySQL DBMS
if (!($connection = @ mysql_pconnect($dbServer, $dbUser, $dbPass)))
    showerror();

// Use the CyPluginDB database
if (!mysql_select_db($dbName, $connection))
   showerror();

// Get the total number of plugins
include 'dbUtil.inc';
$plugin_id_array = getPluginIDs($connection);
$totalPluginCount = count($plugin_id_array);

//echo "totalPluginCount = $totalPluginCount<br>";

// Search words can at most be present in 70% of the objects
$stopWordFrequency = 0.6;

// Build the full search query using logical OR if multiple words are searched on
$searchQuery = "SELECT plugin_list.plugin_auto_id as plugin_id, plugin_list.name as name, plugin_list.description as description, description_word_link.frequency as frequency
                    FROM plugin_list, description_word_link, description_words
                    WHERE plugin_list.plugin_auto_id=description_word_link.plugin_id
                    AND description_words.word_id=description_word_link.word_id
                    AND ( $wordSQL )
                    AND ( ( description_words.plugin_count / $totalPluginCount ) < $stopWordFrequency )
                    ORDER BY description_word_link.frequency DESC";

// Run the query
if (!($result = @ mysql_query($searchQuery, $connection)))
	showerror();

$plugin_id_array = array(); // create an empty array
include 'getpluginInfo.php';

if (!function_exists('str_ireplace')) {
	function str_ireplace($needle, $str, $haystack) {
		$needle = preg_quote($needle, '/');
		return preg_replace("/$needle/i", $str, $haystack);
	}
};


if (@ mysql_num_rows($result) != 0) {

    while($_row = @ mysql_fetch_array($result))
	{	    
		$pluginID = $_row["plugin_id"];
		//$pluginName = trim($_row["name"]);
		//$pluginDescription = trim($_row["description"]);
		
		$plugin_id_array[] = $_row["plugin_id"];
	}
}

?> <span class="style4">This search returns <?php echo count($plugin_id_array)?> of <?php echo $totalPluginCount ?> plugins.</span><?php

?> <ul class="mktree" id="tree1"> <?php
 
//
foreach ($plugin_id_array as $plugin_id ) {

     	$query = 'SELECT distinct plugin_auto_id,name, unique_id, description, license, license_required, project_url ' .
     	  		'FROM plugin_list,plugin_version ' .
     	  		'WHERE plugin_list.plugin_auto_id = plugin_version.plugin_id AND plugin_auto_id ='. $plugin_id;
 
  		  // Run the query
          if (!($pluginList = @ mysql_query ($query, $connection))) 
              showerror();

		//echo "\n\t\t<ul>";
		$pluginList_row = @ mysql_fetch_array($pluginList);
		echo "\n\t\t\t<li>";
		echo "\n\t\t\t\t<span class=\"style3\">",$pluginList_row["name"]."</span>";
			          
		// add plugin info
		echo "\n\t\t\t<ul>";
		echo "\n\t\t\t\t<li>";
		echo getPluginInfoPage($connection, $pluginList_row);
		echo "\n\t\t\t\t</li>";
		echo "\n\t\t\t</ul>";
			          
		echo "\n\t\t\t</li>";

		echo hightlight(stripslashes($pluginList_row["description"]), $_POST["searchwords"])."<p>";
}

?> </ul> <?php


function hightlight($str, $keywords = '') 
{ 
	$keywords = preg_replace('/\s\s+/', ' ', strip_tags(trim($keywords))); // filter 
 
	$style = 'highlight'; 
	$style_i = 'highlight_important'; 
 
	/* Apply Style */
	$var = ''; 

	foreach(explode(' ', $keywords) as $keyword) 
	{ 
		$replacement = "<span class='".$style."'>".$keyword."</span>"; 
		$var .= $replacement." "; 

		if (strlen($keyword)<3){
			continue;
		}
 
		$str = str_ireplace($keyword, $replacement, $str); 
	} 
 
	/* Apply Important Style */
	$str = str_ireplace(rtrim($var), "<span class='".$style_i."'>".$keywords."</span>", $str); 
 
	return $str; 
}


?>

<script src="http://chianti.ucsd.edu/~kono/cytoscape/js/footer.js"></script>
<br>
</body>
</html>