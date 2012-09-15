<?php
if ((require "../chan_archiver.php") !== 1)
{
    die('Failed to include chan_archiver.php.');
}
$t = new chan_archiver();

if ( !isset($_POST['username']) || !isset($_POST['password']) )
{
	exit();
}
$result = $t->register($t->cleanQuery($_POST['username']), $_POST['password']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Account Registration</title>
<meta http-equiv="REFRESH" content="5;url=../"></HEAD>
<BODY>
<?php
echo "DEBUG (RESULT): " . $result;
if ($result = 1 || $result = true)
{
	echo "<h1>Registration Successful!</h1>";
}
elseif ($result = 1066 || $result = 1062)
{
	echo "<h1>A user with that name is already registered!</h1>";
}
else
{
	echo "<h1>Unknown error! Error number " . ($result * 2) . "!";
}
?>
<h2>You will be redirected back in 5 seconds.</h2>
</BODY>
</HTML>