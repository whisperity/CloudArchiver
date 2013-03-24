<?php

//what you doin here, son?
error_reporting( E_ALL );
session_start();
require_once 'init.php';

if ( !isset($archiver_config[ 'updater_enabled' ]) || $archiver_config[ 'updater_enabled' ] )
    $t->doUpdate();

// login stuff
if ( isset( $_REQUEST[ 'login' ] ) && isset( $_REQUEST[ 'user' ] ) && isset( $_REQUEST[ 'pass' ] ) )
{
    $_SESSION[ 'uname' ] = $_REQUEST[ 'user' ];
    $_SESSION[ 'pword' ] = $_REQUEST[ 'pass' ];
}
// commands
$isloggedin = ( isset( $_SESSION[ 'uname' ] ) && isset( $_SESSION[ 'pword' ] ) && $t->login($_SESSION[ 'uname' ], $_SESSION[ 'pword' ]) ) || !$archiver_config[ 'login_enabled' ];
$delenabled = ( !$archiver_config[ 'login_del' ] || $isloggedin );
$chkenabled = ( !$archiver_config[ 'login_chk' ] || $isloggedin );
$addenabled = ( !$archiver_config[ 'login_add' ] || $isloggedin );

$return = "";
if ( $delenabled && isset( $_REQUEST[ 'del' ] ) && isset( $_REQUEST[ 'id' ] ) && isset( $_REQUEST[ 'brd' ] ) )
    $return .= $t->removeThread( $_REQUEST[ 'id' ], $_REQUEST[ 'brd' ], $_REQUEST[ 'files' ] );

if ( $chkenabled && isset( $_REQUEST[ 'chk' ] ) && isset( $_REQUEST[ 'id' ] ) && isset( $_REQUEST[ 'brd' ] ) )
    $return .= $t->updateThread( $_REQUEST[ 'id' ], $_REQUEST[ 'brd' ] );

if ( $chkenabled && isset( $_REQUEST[ 'chka' ] ) )
    $return .= $t->checkThreads( false );

if ( $delenabled && isset( $_REQUEST[ 'upd' ] ) && isset( $_REQUEST[ 'id' ] ) && isset( $_REQUEST[ 'brd' ] ) )
    $return .= $t->setThreadDescription( $_REQUEST[ 'id' ], $_REQUEST[ 'brd' ], $_REQUEST[ 'desc' ] );

if ( $addenabled && isset( $_REQUEST[ 'add' ] ) && isset( $_REQUEST[ 'url' ] ) )
{
    if ( substr( $_REQUEST[ 'url' ], 0, 8 ) == "https://" )
	{
		$_REQUEST[ 'url' ] = "http://" . substr( $_REQUEST[ 'url' ], 9 );
	}
	else if ( substr( $_REQUEST[ 'url' ], 0, 7 ) != "http://" )
	{
		$return = "Invalid link";
	}
        
    if ( !isset( $_REQUEST[ 'desc' ] ) )
        $_REQUEST[ 'desc' ] = "";
    if ( $c = preg_match_all( "/.*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?((?:[a-z][a-z0-9_]*)).*?(\d+)/is", $_REQUEST[ 'url' ], $matches ) )
        $return .= $t->addThread( $matches[ 2 ][ 0 ], $matches[ 1 ][ 0 ], $_REQUEST[ 'desc' ], $_SESSION[ 'uname' ] );
}

if ( $return != "" )
{
    $_SESSION[ 'returnvar' ] = $return;
    header( 'Location: index.php' );
    exit;
}
echo <<<ENDHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<meta name="robots" content="noarchive">
<title>CloudArchiver</title>
<link rel="stylesheet" type="text/css" href="./assets/yui.2.css">
<link rel="stylesheet" type="text/css" href="./assets/global.7.css">
<link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">
<link rel="apple-touch-icon" href="http://static.4chan.org/image/apple-touch-icon-iphone.png">
<link rel="apple-touch-icon" sizes="72x72" href="http://static.4chan.org/image/apple-touch-icon-ipad.png">
<link rel="apple-touch-icon" sizes="114x114" href="http://static.4chan.org/image/apple-touch-icon-iphone-retina.png">
<link rel="apple-touch-icon" sizes="144x144" href="http://static.4chan.org/image/apple-touch-icon-ipad-retina.png">
<link rel="stylesheet" type="text/css" href="./assets/faq.2.css">
<style type="text/css">
.infobox{
width:725px;
border:solid 1px #800;
background:#E04000 url(images/menu_tick.png) 8px 6px no-repeat;
color:#FFFFFF;
padding:4px;
text-align:center;
}
.alertbox{
width:725px;
border:solid 1px #800;
background:#FF3330 url(images/menu_light.png) 8px 6px no-repeat;
color:#FFFFFF;
padding:4px;
text-align:center;
}
</style>
</head><link rel="stylesheet" type="text/css" href="data:text/css,">
<body style="background: #FFE url('./assets/fade.png') top repeat-x; color: #800;">
  <div id="header_nav" style="text-align: right; position:absolute; right:0px; width:600px; padding: 3px; border: 1px #800; color: #800; display: block;">
ENDHTML;
if ( !$isloggedin )
{
    echo <<<ENDHTML
<form action="?refresh" method="POST">
Username: <input type="text" name="user" size="20" /> Password: <input type="password" name="pass" size="20" /> <input type="submit" name="login" value="Login"/>
</form>
<br />
ENDHTML;
    
}
else if ( $archiver_config[ 'login_enabled' ] )
{
    echo <<<ENDHTML
<form action="?refresh" method="POST">
<input type="hidden" name="user" value="" />
<input type="hidden" name="pass" value="" />
<input type="submit" name="login" value="Logout"/>
</form>
ENDHTML;
}

echo <<<ENDHTML
  
  </div>
ENDHTML;

if ( $archiver_config[ 'register_enabled' ] )
{
	echo <<<ENDHTML
	<div id="header_nav2" style="text-align: left; position:absolute; left:0px; width:600px; padding: 3px; border: 1px #800; color: #800; display: block;">
	<a href="./register/">[Register]</a>
	</div>
ENDHTML;
}

echo <<<ENDHTML
  <div id="doc">
    <div id="hd">
      <div id="logo" style="background: url('./assets/logo.png') top left no-repeat; font-size: 1px; line-height: 0; height: 120px; overflow: hidden; margin: 0 auto; width: 300px;">
        <h1>CloudArchiver</h1>
      </div>
    </div>
      <div class="box-outer top-box">
        <div class="box-inner">
          <div class="boxbar">
            <h2>Archives</h2>
                      </div>
          <div class="boxcontent">
ENDHTML;

if ( $t->updateAvailable )
{
    echo <<<ENDHTML
    <div class="alertbox">There is an <a href="{$t->updaterurl}" onclick="alert('make sure you delete version.txt after updating!');">update</a> available! <a href="{$t->compareurl}{$t->currentVersion}...{$t->latestVersion}">(diff)</a></div><br />
ENDHTML;
}
if ( isset( $_SESSION[ 'returnvar' ] ) && $_SESSION[ 'returnvar' ] != "" )
{
    $arr = explode( '<br />', $_SESSION[ 'returnvar' ] );
    foreach ( $arr as $str )
    {
        if ( empty( $str ) || strlen( $str ) <= 3 )
            continue;
        echo <<<ENDHTML
    <div class="infobox">$str</div><br />
ENDHTML;
    }
    $_SESSION[ 'returnvar' ] = "";
    unset( $_SESSION[ 'returnvar' ] );
}




if ( $addenabled )
{
    echo <<<ENDHTML
	<br />
<form action="?refresh" method="POST">
<table border="0" width="540" cellpadding="3" cellspacing="3">
	<tr>
        <td><b>Add Thread</b></td>
    </tr>
    <tr>
        <td>Thread URL: </td><td><input type="text" name="url" size="60" /></td>
    </tr>
    <tr>
        <td>Thread Description: </td><td><input type="text" name="desc" size="60" /></td>
    </tr>
	<tr>
	<td><input type="submit" name="add" value="Add"/></td>
	</tr>
</table>
</form>
</br>
ENDHTML;
}
$threads = $t->getThreads();
echo "<form action=\"?refresh\" method=\"POST\">";
if ( $chkenabled )
{
    $onclick = $t->getOngoingThreadCount() >= 10 ? "alert('Since there are many ongoing threads it may seem like the page has hung, just be patient and they will all update');" : "";
    echo <<<ENDHTML
<form action="?refresh" method="POST">
<input type="submit" name="chka" onclick="$onclick" value="Recheck All"/>
</form>
ENDHTML;
}
echo <<<ENDHTML
<br />
<table border="1" bordercolor="#800" style="background-color: #FCA" width="735" cellpadding="3" cellspacing="3">
	<tr>
		<td>Thread ID</td>
		<td>Board</td>
		<td>Added by</td>
		<td>Description</td>
		<td>Status</td>
		<td>Last Checked</td>
		<td>Last Post</td>
		<td>Actions</td>
	</tr>
ENDHTML;
$i = 0;
foreach ( $threads as $thr )
{
    $thrlink     = sprintf( $t->threadurl, $thr[ 1 ], $thr[ 0 ] );
    $lastchecked = time() - $thr[ 3 ];
	$lastchecked_end = " seconds ago";
	if ($lastchecked > 60)
	{
		$lastchecked = floor($lastchecked / 60);
		$lastchecked_end = " minutes ago";
		if ($lastchecked > 60)
		{
			$lastchecked = floor($lastchecked / 60);
			$lastchecked_end = "hours ago";
		}
	}
    if ( $thr[ 3 ] == 0 )
	{
		$lastchecked = "";
		$lastchecked_end = "never";
	}
	
    $status = $thr[ 2 ] == 1 ? "Alive" : "Dead";
	$addedby = $thr[ 6 ];
    $local  = $archiver_config[ 'pubstorage' ] . $thr[ 1 ] . "/" . $thr[ 0 ] . ".html";
    $link   = "<a href=\"$thrlink\">{$thr[0]}</a> <a href=\"$local\">(local)</a>";
	if ( $archiver_config[ 'restrict_actions' ] )
	{
		$delallowed = ( $delenabled && ($addedby == $_SESSION[ 'uname' ]) );
		$chkallowed = ( $chkenabled && ($addedby == $_SESSION[ 'uname' ]) );
	}
	else
	{
		$delallowed = $delenabled;
		$chkallowed = $chkenabled;
	}
    $check  = $chkallowed ? "<input type=\"submit\" name=\"chk\" value=\"Check\"/>" : "";
    $desc   = $delallowed ? "<input type=\"text\" name=\"desc\" value=\"{$thr[4]}\"/><input type=\"submit\" name=\"upd\" value=\"Update\"/>" : $thr[ 4 ];
    if ( $thr[ 2 ] == 0 )
    {
        $lastchecked = "";
        $link        = "{$thr[0]} <a href=\"$local\">(local)</a>";
        $check       = "";
    }
    if ( $delallowed )
        $check .= <<<ENDHTML
    <input type="submit" name="del" onclick="if(confirm('Delete files too?')) document.getElementById('files{$i}').value='1';" value="Remove"/>
ENDHTML;
    $lastpost = date( "m/d/y, g:i a", $thr[ 5 ] );
    if ( $thr[ 5 ] == "" || $thr[ 5 ] <= 0 )
        $lastpost = "N/A";
    echo <<<ENDHTML

    <form action="?refresh" method="POST">
    <input type="hidden" name="id" value="{$thr[0]}"/>
    <input type="hidden" name="brd" value="{$thr[1]}"/>
    <input type="hidden" name="files" id="files{$i}" value="0"/>
	<tr>
		<td>$link</td>
		<td>/{$thr[1]}/</td>
		<td>$addedby</td>
		<td>$desc</td>
		<td>$status</td>
		<td>$lastchecked$lastchecked_end</td>
		<td>$lastpost</td>
		<td>$check</td>
	</tr>
    </form>
ENDHTML;
    $i++;
}

echo "</table><br />";
echo <<<ENDHTML

    </div>
        </div>
      </div>
	  <div id="ft">
      <br class="clear-bug">
      <div id="copyright"><a href="https://github.com/steamruler/CloudArchiver">GitHub</a> | 
ENDHTML;
$bookmarkleturl = "http://" . ( $_SERVER[ 'HTTP_HOST' ] ? $_SERVER[ 'HTTP_HOST' ] : $_SERVER[ "SERVER_NAME" ] ) . $_SERVER[ "SCRIPT_NAME" ];
?>
<abbr title="Use this when you're on the page you want to archive"><a href="javascript:open('<?php
echo $bookmarkleturl;
?>?add=Add&url=' + document.URL.replace('http://', ''));">Bookmarklet</a></abbr>
	  <br />
      Not affilated with 4chan in any way. Released under the GNU General License 3.
      </div>
    </div>
</body>
</html>