<?php
// enjoi -stk25
error_reporting( E_ALL );

include "config.php";

class chan_archiver
{
    public $mysql;
    public $threadurl = "http://boards.4chan.org/%s/res/%s"; // board, ID
    public $updaterurl = "https://github.com/steamruler/CloudArchiver/tarball/master";
    public $compareurl = "https://github.com/steamruler/CloudArchiver/compare/";
    public $currentVersion;
    public $latestVersion;
    public $updateAvailable;
	
	public function cleanQuery($string)
	{
		if(get_magic_quotes_gpc())  // prevents duplicate backslashes
		{
			$string = stripslashes($string);
		}
		if (phpversion() >= '4.3.0')
		{
			$string = mysql_real_escape_string($string);
		}
		else
		{
			$string = mysql_escape_string($string);
		}
		return $string;
	}
	
    public function doUpdate()
    {
        $size   = 0;
        $handle = 0;
        if ( file_exists( "version.txt" ) )
        {
            $size   = filesize( "version.txt" );
            $handle = fopen( "version.txt", "r+" );
        }
        if ( !$handle || $size <= 0 )
        {
            $this->currentVersion = $this->getCurrentLatest();
            $this->saveCurrentVersion();
        }
        else
        {
            $this->currentVersion = fread( $handle, $size );
            fclose( $handle );
        }
        $this->latestVersion   = $this->getCurrentLatest();
        $this->updateAvailable = $this->latestVersion != $this->currentVersion;
    }
    
    protected function saveCurrentVersion()
    {
        $handle = fopen( "version.txt", "w+" );
        if ( !$handle )
            die( 'Unable to open version.txt' );
        fwrite( $handle, $this->currentVersion );
        fclose( $handle );
    }
    
    protected function getCurrentLatest()
    {
        $headers = get_headers( $this->updaterurl, 1 );
        $latest  = explode( "filename=", $headers[ 'Content-Disposition' ] );
        $latest  = str_replace( ".tar.gz", "", str_replace( "steamruler-CloudArchiver-", "", $latest[ 1 ] ) );
        return $latest;
    }
    
    protected function connectDB()
    {
        global $archiver_config;
        if ( !$this->mysql )
        {
            $this->mysql = mysql_connect( $archiver_config[ 'mysql_host' ], $archiver_config[ 'mysql_user' ], $archiver_config[ 'mysql_pass' ] );
            if ( !$this->mysql )
                die( 'Could not connect: ' . mysql_error() );
            mysql_select_db( $archiver_config[ 'mysql_db' ], $this->mysql );
            
        }
    }
    
    protected function closeDB()
    {
        if ( $this->mysql )
        {
            mysql_close( $this->mysql );
            $this->mysql = null;
        }
    }
    
    protected function getSource( $url )
    {
        if ( ( $source = @file_get_contents( $url ) ) == false )
            return false;
        return $source;
    }
    
    protected function downloadFile( $url, $location )
    {
        $file = "";
        if ( ( $handle = @fopen( $url, "r" ) ) )
        {
            while ( $line = fread( $handle, 8192 ) )
                $file .= $line;
            fclose( $handle );
            $this->writeFile( $file, $location );
        }
    }
    
    protected function writeFile( $data, $location )
    {
        if ( ( $handle = fopen( $location, "w+" ) ) )
        {
            fwrite( $handle, $data );
            fclose( $handle );
            return true;
        }
        return false;
    }
    
    protected function rrmdir( $dir )
    {
        foreach ( glob( $dir . '/*' ) as $file )
        {
            if ( is_dir( $file ) )
                $this->rrmdir( $file );
            else
                unlink( $file );
        }
        rmdir( $dir );
    }
    
    public function checkThreads( $checktime )
    {
        $this->connectDB();
        $query = mysql_query( "SELECT * FROM `threads` WHERE `Status` = '1'" );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        $num = mysql_num_rows( $query );
        if ( $num <= 0 )
            return false;
		$num2 = 0;
        while ( $row = mysql_fetch_object( $query ) )
        {
            if ( $checktime && time() - $row->LastChecked < 90 )
                continue;
            
            $this->updateThread( $row->ID, $row->Board );
			$num2++;
        }
        $this->closeDB();
		$return = "Checked " . $num2 . " threads at " . date(H:i) . ".";
        return $return;
    }
    
    public function updateThread( $threadid, $board )
    {
        global $archiver_config;
        $this->connectDB();
        $thrquery = mysql_query( sprintf( "SELECT * FROM `posts` WHERE `Board` = '%s' AND `ThreadID` = '%s'", $board, $threadid ) );
        $postarr  = array();
        while ( $post = mysql_fetch_object( $thrquery ) )
            array_push( $postarr, $post->ID );
        
        $url  = sprintf( $this->threadurl, $board, $threadid );
        $data = $this->getSource( $url );
        if ( !$data ) // must have 404'd
        {
            mysql_query( sprintf( "UPDATE `threads` SET `Status` = '0' WHERE `Board` = '%s' AND `ID` = '%s'", $board, $threadid ) );
            mysql_query( sprintf( "DELETE FROM `posts` WHERE `Board` = '%s' AND `ThreadID` = '%s'", $board, $threadid ) );
            return;
        }
        $fixeddata = str_replace( "=\"//", "=\"http://", $data );
        $fixeddata = str_replace( "\"" . $threadid . "#", "\"" . $threadid . ".html#", $fixeddata );
        $fixeddata = str_replace( "text/rocketscript", "text/javascript", $fixeddata );
        $fixeddata = str_replace( "data-rocketsrc", "src", $fixeddata );
        if ( is_dir( $archiver_config[ 'storage' ] . $board . "/" ) === FALSE )
            mkdir( $archiver_config[ 'storage' ] . $board . "/" );
        if ( is_dir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" ) === FALSE )
            mkdir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" );
        if ( is_dir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/thumbs/" ) === FALSE )
            mkdir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/thumbs/" );
        
        $posts = explode( "class=\"postContainer", $data );
        for ( $i = 1; $i < count( $posts ); $i++ )
        {
            $post = explode( "</blockquote> </div>", $posts[ $i ] );
            $id   = explode( "title=\"Quote this post\">", $post[ 0 ] );
            $id   = explode( "</a>", $id[ 1 ] );
            $id   = $id[ 0 ];
            if ( in_array( $id, $postarr ) )
                continue;
            
            $posttime = explode( "data-utc=\"", $post[ 0 ] );
            $posttime = explode( "\"", $posttime[ 1 ] );
            $posttime = $posttime[ 0 ];
            
            $file = explode( "\">File:", $post[ 0 ] );
            if ( count( $file ) > 1 )
            {
                $file     = explode( "</a></div>", $file[ 1 ] );
                $file     = $file[ 0 ];
                $fileorig = explode( "<a href=\"", $file );
                $fileorig = explode( "\" target=\"_blank\"", $fileorig[ 1 ] );
                $fileorig = $fileorig[ 0 ];
                $fileurl  = "http:" . $fileorig;
                
                $filethum = explode( "<img src=\"", $file );
                $filethum = explode( "\" alt=", $filethum[ 1 ] );
                $filethum = $filethum[ 0 ];
                $thumurl  = "http:" . $filethum;
                
                $filestor    = $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" . basename( $fileurl );
                $thumstor    = $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/thumbs/" . basename( $thumurl );
                $pubfilestor = $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/" . basename( $fileurl );
                $pubthumstor = $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/thumbs/" . basename( $thumurl );
                
                $this->downloadFile( $fileurl, $filestor );
                $this->downloadFile( $thumurl, $thumstor );
                $fixeddata = str_replace( $fileurl, $pubfilestor, $fixeddata );
                $fixeddata = str_replace( $thumurl, $pubthumstor, $fixeddata );
            }
            mysql_query( sprintf( "INSERT INTO `posts` ( `ID`, `ThreadID`, `Board`, `PostTime` ) VALUES ( '%s', '%s', '%s', '%s' )", $id, $threadid, $board, $posttime ) );
        }
        // fix for posts we already have downloaded
        $fixeddata = str_replace( "http://1.thumbs.4chan.org/" . $board . "/thumb/", $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/thumbs/", $fixeddata );
        $fixeddata = str_replace( "http://0.thumbs.4chan.org/" . $board . "/thumb/", $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/thumbs/", $fixeddata );
        $fixeddata = str_replace( "http://images.4chan.org/" . $board . "/src/", $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/", $fixeddata );
        // thread is done
        mysql_query( sprintf( "UPDATE `threads` SET `LastChecked` = '%s' WHERE `Board` = '%s' AND `ID` = '%s'", time(), $board, $threadid ) );
        $this->writeFile( $fixeddata, $archiver_config[ 'storage' ] . $board . "/" . $threadid . ".html" );
        $this->closeDB();
        return sprintf( "Checked %s (/%s/) at %s<br />\r\n", $threadid, $board, time() );
    }
    
    public function addThread( $threadid, $board, $description, $usrnme )
    {
        $this->connectDB();
        // check if we already have it
        $query = mysql_query( sprintf( "SELECT * FROM `threads` WHERE `ID` = '%s' AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        $num = mysql_num_rows( $query );
        if ( $num > 0 )
            return false;
        // guess we don't, lets add it
        $query = mysql_query( sprintf( "INSERT INTO `threads` ( `ID`, `Board`, `Status`, `LastChecked`, `Description`, `TimeAdded`, `AddedBy` ) VALUES ( '%s', '%s', '1', '0', '%s', '%s', '%s' )", $this->cleanQuery($threadid), $this->cleanQuery($board), $this->cleanQuery($description), time(), $this->cleanQuery($usrnme) ) );
        if ( !$query )
            die( 'Could not add thread: ' . mysql_error() );
        $this->closeDB();
        return sprintf( "Added thread %s (/%s/)<br />\r\n", $threadid, $board );
    }
    
    public function removeThread( $threadid, $board, $deletefiles = 0 )
    {
        global $archiver_config;
        $this->connectDB();
        // check if we already have it
        $query = mysql_query( sprintf( "SELECT * FROM `threads` WHERE `ID` = '%s' AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        $num = mysql_num_rows( $query );
        if ( $num <= 0 )
            return false;
        if ( $deletefiles )
        {
            if(is_dir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" ))
                $this->rrmdir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" );
            if(file_exists( $archiver_config[ 'storage' ] . $board . "/" . $threadid . ".html" ))
                unlink( $archiver_config[ 'storage' ] . $board . "/" . $threadid . ".html" );
        }
        mysql_query( sprintf( "DELETE FROM `threads` WHERE `ID` = '%s' AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        mysql_query( sprintf( "DELETE FROM `posts` WHERE `ThreadID` = '%s' AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        $this->closeDB();
        return sprintf( "Removed thread %s (/%s/)<br />\r\n", $threadid, $board );
    }
	
	public function login( $username, $passhash )
    {
		global $archiver_config;
		$this->connectDB();
		$passhashhash = hash('sha512', $passhash);
        $query = mysql_query( sprintf( "SELECT * FROM `users` WHERE `Username` = '%s' AND `PassHash` = '%s'", $this->cleanQuery($username), $passhashhash) );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        $num = mysql_num_rows( $query );
        if ( $num <= 0 )
		{
            return false;
		}
		else
		{
			return true;
        }
        $this->closeDB();
    }
	
	public function register( $username, $passhash )
    {
		global $archiver_config;
		$this->connectDB();
		$passhashhash = hash('sha512', $passhash);
        $query = mysql_query( sprintf( "INSERT INTO `users` ( `Username`, `PassHash` ) VALUES ( '%s', '%s' )", $this->cleanQuery($username), $passhashhash ) );
        if ( !$query )
            return mysql_errno(); //http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
		else
		{
			return true;
        }
        $this->closeDB();
    }
    
    public function setThreadDescription( $threadid, $board, $description )
    {
        $this->connectDB();
        // check if we already have it
        $query = mysql_query( sprintf( "SELECT * FROM `threads` WHERE `ID` = '%s' AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        $num = mysql_num_rows( $query );
        if ( $num <= 0 )
            return false;
        mysql_query( sprintf( "UPDATE `threads` SET `Description` = '%s' WHERE `ID` = '%s' AND Board = '%s'", $this->cleanQuery($description), $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        $this->closeDB();
        return sprintf( "Updated thread %s (/%s/)<br />\r\n", $threadid, $board );
    }
    
    public function getOngoingThreadCount()
    {
        $this->connectDB();
        $query = mysql_query( "SELECT * FROM `threads` WHERE `Status` = '1'" );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        
        $num = mysql_num_rows( $query );
        $this->closeDB();
        return $num;
    }
    
    public function getThreads()
    {
        $this->connectDB();
        $query = mysql_query( "SELECT * FROM `threads` ORDER BY `Board`, `TimeAdded`, `ID` ASC" );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        $thrarray = array();
        while ( $thr = mysql_fetch_object( $query ) )
        {
            $q2       = mysql_query( sprintf( "SELECT * FROM `posts` WHERE `ThreadID` = '%s' AND `Board` = '%s' ORDER BY `PostTime` DESC", $thr->ID, $thr->Board ) );
            $lasttime = 0;
            if ( !$q2 )
                die( 'Could not query database: ' . mysql_error() );
            if ( mysql_num_rows( $q2 ) > 0 )
                $lasttime = mysql_fetch_object( $q2 )->PostTime;
            array_push( $thrarray, array(
                 $thr->ID,
                $thr->Board,
                $thr->Status,
                $thr->LastChecked,
                $thr->Description,
                $lasttime,
				$thr->AddedBy
            ) );
        }
        $this->closeDB();
        return $thrarray;
    }
}
?>