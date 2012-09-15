<?php
if ((require "../chan_archiver.php") !== 1)
{
    die('Failed to include chan_archiver.php.');
}
$t = new chan_archiver();

echo <<<ENDHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<meta name="robots" content="noarchive">
<title>CloudArchiver</title>
<link rel="stylesheet" type="text/css" href="../assets/yui.2.css">
<link rel="stylesheet" type="text/css" href="../assets/global.7.css">
<link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">
<link rel="apple-touch-icon" href="http://static.4chan.org/image/apple-touch-icon-iphone.png">
<link rel="apple-touch-icon" sizes="72x72" href="http://static.4chan.org/image/apple-touch-icon-ipad.png">
<link rel="apple-touch-icon" sizes="114x114" href="http://static.4chan.org/image/apple-touch-icon-iphone-retina.png">
<link rel="apple-touch-icon" sizes="144x144" href="http://static.4chan.org/image/apple-touch-icon-ipad-retina.png">
<link rel="stylesheet" type="text/css" href="../assets/faq.2.css">
</style>
</head><link rel="stylesheet" type="text/css" href="data:text/css,">
<body style="background: #FFE url('../assets/fade.png') top repeat-x; color: #800;">
  <div id="doc">
    <div id="hd">
      <div id="logo" style="background: url('../assets/logo.png') top left no-repeat; font-size: 1px; line-height: 0; height: 120px; overflow: hidden; margin: 0 auto; width: 300px;">
        <h1>CloudArchiver</h1>
      </div>
    </div>
      <div class="box-outer top-box">
        <div class="box-inner">
          <div class="boxbar">
            <h2>Register</h2>
                      </div>
          <div class="boxcontent">
ENDHTML;

if ( $archiver_config[ 'register_enabled' ] )
{
    echo <<<ENDHTML
	<br />
<form action="register.php" method="POST">
<table border="0" width="540" cellpadding="3" cellspacing="3">
	<tr>
        <td><b>Register</b></td>
    </tr>
    <tr>
        <td>Username: </td><td><input type="text" name="username" size="60" /></td>
    </tr>
    <tr>
        <td>Password: </td><td><input type="password" name="password" size="60" /></td>
    </tr>
	<tr>
		<td><input type="submit" name="register" value="Register"/></td>
	</tr>
</table>
</form>
</br>
ENDHTML;
}
else
{
	echo "<p>Registering new accounts has been disabled.</p>";
}
?>
    </div>
        </div>
      </div>
	  <div id="ft">
      <br class="clear-bug">
      <div id="copyright">All data is encrypted with SHA512 before inserted into a secure database.
	  <br />
      Not affilated with 4chan in any way. Released under the GNU General License 3.
      </div>
    </div>
</body>
</html>