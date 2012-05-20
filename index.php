<?php
session_start();
include "chan_archiver.php";
$t = new chan_archiver();
if ( !isset($archiver_config[ 'updater_enabled' ]) || $archiver_config[ 'updater_enabled' ] )
    $t->doUpdate();

// login stuff
if ( isset( $_REQUEST[ 'login' ] ) && isset( $_REQUEST[ 'user' ] ) && isset( $_REQUEST[ 'pass' ] ) )
{
    $_SESSION[ 'uname' ] = $_REQUEST[ 'user' ];
    $_SESSION[ 'pword' ] = $_REQUEST[ 'pass' ];
}
// commands
$isloggedin = ( isset( $_SESSION[ 'uname' ] ) && isset( $_SESSION[ 'pword' ] ) && $_SESSION[ 'uname' ] == $archiver_config[ 'login_user' ] && $_SESSION[ 'pword' ] == $archiver_config[ 'login_pass' ] ) || !$archiver_config[ 'login_enabled' ];
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
    if ( substr( $_REQUEST[ 'url' ], 0, 7 ) != "http://" )
        $_REQUEST[ 'url' ] = "http://" . $_REQUEST[ 'url' ];
    if ( !isset( $_REQUEST[ 'desc' ] ) )
        $_REQUEST[ 'desc' ] = "";
    if ( $c = preg_match_all( "/.*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?(?:[a-z][a-z0-9_]*).*?((?:[a-z][a-z0-9_]*)).*?(\d+)/is", $_REQUEST[ 'url' ], $matches ) )
        $return .= $t->addThread( $matches[ 2 ][ 0 ], $matches[ 1 ][ 0 ], $_REQUEST[ 'desc' ] );
}

if ( $return != "" )
{
    $_SESSION[ 'returnvar' ] = $return;
    header( 'Location: index.php' );
    exit;
}
echo <<<ENDHTML
<html>
<head>
<title>4chan archiver - by anon e moose</title>
<link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">
<style type="text/css">
.infobox{
width:350px;
border:solid 1px #DEDEDE;
background:#FFFFCC url(images/menu_tick.png) 8px 6px no-repeat;
color:#222222;
padding:4px;
text-align:center;
}
.alertbox{
width:350px;
border:solid 1px #DEDEDE;
background:#FF3330 url(images/menu_light.png) 8px 6px no-repeat;
color:#222222;
padding:4px;
text-align:center;
}
</style>
</head>
<body>
<a href="http://github.com/emoose/4chan-archiver/"><h2>4chan archiver - by anon e moose</h2></a>
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

if ( !$isloggedin )
{
    echo <<<ENDHTML
<form action="?refresh" method="POST">
<table border="1" bordercolor="#FFCC00" style="background-color:#FFFFCC" width="340" cellpadding="3" cellspacing="3">
	<tr>
        <td><b>Admin Login</b></td>
    </tr>
    <tr>
        <td>Username: <input type="text" name="user" size="20" /></td>
    </tr>
    <tr>
        <td>Password: <input type="password" name="pass" size="20" /></td>
        <td><input type="submit" name="login" value="Login"/></td>
    </tr>
</table>
</form>
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
if ( $addenabled )
{
    echo <<<ENDHTML
<form action="?refresh" method="POST">
<table border="1" bordercolor="#FFCC00" style="background-color:#FFFFCC" width="610" cellpadding="3" cellspacing="3">
	<tr>
        <td><b>Add Thread</b></td>
    </tr>
    <tr>
        <td>Thread URL: <input type="text" name="url" size="60" /></td>
    </tr>
    <tr>
        <td>Thread Description: <input type="text" name="desc" size="60" /></td>
        <td><input type="submit" name="add" value="Add"/></td>
    </tr>
</table>
</form>
ENDHTML;
}
$threads = $t->getThreads();
echo "<form action=\"?refresh\" method=\"POST\">";
if ( $chkenabled )
{
    $onclick = $t->getOngoingThreadCount() >= 10 ? "alert('Since you have many ongoing threads it may seem like the page has hung, just be patient and they will all update');" : "";
    echo <<<ENDHTML
<form action="?refresh" method="POST">
<input type="submit" name="chka" onclick="$onclick" value="Recheck All"/>
</form>
ENDHTML;
}
echo <<<ENDHTML
<table border="1" bordercolor="#FFCC00" style="background-color:#FFFFCC" width="900" cellpadding="3" cellspacing="3">
	<tr>
		<td>Thread ID</td>
		<td>Board</td>
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
    $lastchecked = time() - $thr[ 3 ] . " seconds ago";
    if ( $thr[ 3 ] == 0 )
        $lastchecked = "never";
    $status = $thr[ 2 ] == 1 ? "Ongoing" : "404'd";
    $local  = $archiver_config[ 'pubstorage' ] . $thr[ 1 ] . "/" . $thr[ 0 ] . ".html";
    $link   = "<a href=\"$thrlink\">{$thr[0]}</a> <a href=\"$local\">(local)</a>";
    $check  = $chkenabled ? "<input type=\"submit\" name=\"chk\" value=\"Check\"/>" : "";
    $desc   = $delenabled ? "<input type=\"text\" name=\"desc\" value=\"{$thr[4]}\"/><input type=\"submit\" name=\"upd\" value=\"Update\"/>" : $thr[ 4 ];
    if ( $thr[ 2 ] == 0 )
    {
        $lastchecked = "";
        $link        = "<a href=\"$local\">{$thr[0]}</a>";
        $check       = "";
    }
    if ( $delenabled )
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
		<td>{$thr[1]}</td>
		<td>$desc</td>
		<td>$status</td>
		<td>$lastchecked</td>
		<td>$lastpost</td>
		<td>$check</td>
	</tr>
    </form>
ENDHTML;
    $i++;
}

echo "</table><br />";
$bookmarkleturl = "http://" . ( $_SERVER[ 'HTTP_HOST' ] ? $_SERVER[ 'HTTP_HOST' ] : $_SERVER[ "SERVER_NAME" ] ) . $_SERVER[ "SCRIPT_NAME" ];
?>
<font size="1" family="Verdana">downloaded from <a href="http://github.com/emoose/4chan-archiver/">github.com/emoose/4chan-archiver</a>. <abbr title="use this when you're on the page you want to archive"><a href="javascript:open('<?php
echo $bookmarkleturl;
?>?add=Add&url=' + document.URL.replace('http://', ''));">bookmarklet</a></abbr></font>
</body>
</html>