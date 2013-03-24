<?php
error_reporting( E_ALL );

class chan_archiver
{
    /**
     *
     * @var mysqli
     */
    private $mysqli;
    public $threadurl = "http://boards.4chan.org/%s/res/%s"; // board, ID
    
    public function injectDatabase($mysqli)
    {
        if ($mysqli instanceof mysqli)
            $this->mysqli = $mysqli;
        else
            die('Attempted to inject non-mysqli database provider.');
    }
    
	public function cleanQuery($string)
	{
        return $this->mysqli->real_escape_string($string);
	}
    
    protected function getSource( $url )
    {
        if ( ( $source = @file_get_contents( $url ) ) == false )
            return false;
        return $source;
    }
    
    protected function downloadFile( $url, $location )
    {
        set_time_limit(0);
        
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
    
    protected function rmdir( $dir )
    {
        foreach ( glob( $dir . '/*' ) as $file )
        {
            if ( is_dir( $file ) )
                $this->rmdir( $file );
            else
                unlink( $file );
        }
        rmdir( $dir );
    }
    
    public function checkThreads( $checktime )
    {
        $query = $this->mysqli->query("SELECT * FROM `threads` WHERE `Status` = '1'");
        if ( !$query )
            die( 'Could not query database: ' . $this->mysqli->error() );
        
        if ( $query->num_rows <= 0 )
            return false;
        
		$count = 0;
        while ( $row = $query->fetch_object() )
        {
            if ( $checktime && time() - $row->LastChecked < 90 )
		       continue;
            
            $this->updateThread( $row->ID, $row->Board );
			$count++;
        }
        
        $return = "Checked " . $count . " threads at " . date('H:i') . ".";
        return $return;
    }
    
    public function updateThread( $threadid, $board )
    {
        global $archiver_config;
        
        $thrquery = $this->mysqli->query( sprintf( "SELECT * FROM `posts` WHERE `Board` = '%s' 
            AND `ThreadID` = '%s'", $board, $threadid ) );
        $postarr  = array();
        while ( $post = $thrquery->fetch_object() )
            array_push( $postarr, $post->ID );
        
        $url  = sprintf( $this->threadurl, $board, $threadid );
        $data = $this->getSource( $url );
        if ( !$data ) // must have 404'd
        {
            $this->mysqli->real_query( sprintf( "UPDATE `threads` SET `Status` = '0' 
                WHERE `Board` = '%s' AND `ID` = '%s'", $board, $threadid ) );
            $this->mysqli->real_query( sprintf( "DELETE FROM `posts` 
                WHERE `Board` = '%s' AND `ThreadID` = '%s'", $board, $threadid ) );
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
            $this->mysqli->query( sprintf( "INSERT INTO `posts` ( `ID`, `ThreadID`, `Board`, `PostTime` ) 
                VALUES ( '%s', '%s', '%s', '%s' )", $id, $threadid, $board, $posttime ) );
        }
        // fix for posts we already have downloaded
        $fixeddata = str_replace( "http://1.thumbs.4chan.org/" . $board . "/thumb/", $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/thumbs/", $fixeddata );
        $fixeddata = str_replace( "http://0.thumbs.4chan.org/" . $board . "/thumb/", $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/thumbs/", $fixeddata );
        $fixeddata = str_replace( "http://images.4chan.org/" . $board . "/src/", $archiver_config[ 'pubstorage' ] . $board . "/" . $threadid . "/", $fixeddata );
        // thread is done
        $this->mysqli->real_query( sprintf( "UPDATE `threads` SET `LastChecked` = '%s' 
            WHERE `Board` = '%s' AND `ID` = '%s'", time(), $board, $threadid ) );
        $this->writeFile( $fixeddata, $archiver_config[ 'storage' ] . $board . "/" . $threadid . ".html" );
        
        return sprintf( "Checked %s (/%s/) at %s<br />\r\n", $threadid, $board, time() );
    }
    
    public function addThread( $threadid, $board, $description, $usrnme )
    {
        // Check whether the thread is already added.
        $query = $this->mysqli->query( sprintf( "SELECT * FROM `threads` WHERE `ID` = '%s' 
            AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        if ( !$query )
            die( 'Could not query database: ' . $this->mysqli->error );
        
        if ( $query->num_rows > 0 )
            return false;
        
        // If the thread isn't added yet, we do so.
        $add_query = $this->mysqli->query( sprintf( "INSERT INTO `threads`
            ( `ID`, `Board`, `Status`, `LastChecked`, `Description`, `TimeAdded`, `AddedBy` )
            VALUES ( '%s', '%s', '1', '0', '%s', '%s', '%s' )",
                $this->cleanQuery($threadid), $this->cleanQuery($board), $this->cleanQuery($description),
                time(), $this->cleanQuery($usrnme) ) );
        if ( !$add_query )
            die( 'Could not add thread: ' . $this->mysqli->error );
        
        return sprintf( "Added thread %s (/%s/)<br />\r\n", $threadid, $board );
    }
    
    public function removeThread( $threadid, $board, $deletefiles = 0 )
    {
        global $archiver_config;
        
        $query = $this->mysqli->query( sprintf( "SELECT * FROM `threads` WHERE `ID` = '%s' 
            AND `Board` = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        if ( !$query )
            die( 'Could not query database: ' . $this->mysqli->error );
        
        if ( $query->num_rows <= 0 )
            return false;
        
        if ( $deletefiles )
        {
            if(is_dir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" ))
                $this->rmdir( $archiver_config[ 'storage' ] . $board . "/" . $threadid . "/" );
            if(file_exists( $archiver_config[ 'storage' ] . $board . "/" . $threadid . ".html" ))
                unlink( $archiver_config[ 'storage' ] . $board . "/" . $threadid . ".html" );
        }
        $this->mysqli->real_query( sprintf("DELETE FROM `threads` WHERE `ID` = '%s' 
            AND `Board` = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ));
        $this->mysqli->real_query( sprintf("DELETE FROM `posts` WHERE `ThreadID` = '%s'
            AND `Board` = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ));
        
        return sprintf( "Removed thread %s (/%s/)<br />\r\n", $threadid, $board );
    }
	
	public function login( $username, $password )
    {
		$passhash = hash('sha512', $password);
        $query = $this->mysqli->query(sprintf("SELECT `Username`, `PassHash` FROM `users`
            WHERE `Username` = '%s' AND `PassHash` = '%s'", $this->cleanQuery($username), $passhash));
        
        return ($query->num_rows === 1 ? true : false);
    }
	
	public function register( $username, $password )
    {
        $passhash = hash('sha512', $password);
        $query = $this->mysqli->query(sprintf("INSERT INTO `users` ( `Username`, `PassHash` )
            VALUES ('%s', '%s')", $this->cleanQuery($username), $passhash));
        
        return ($query ? true : $this->mysqli->errno);
    }
    
    public function setThreadDescription( $threadid, $board, $description )
    {
        // Check whether we already have the thread
        $query = $this->mysqli->query( sprintf( "SELECT * FROM `threads` WHERE `ID` = '%s' 
            AND Board = '%s'", $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        if ( !$query )
            die( 'Could not query database: ' . mysql_error() );
        
        if ( $query->num_rows <= 0 )
            return false;
        
        $this->mysqli->query( sprintf( "UPDATE `threads` SET `Description` = '%s' 
            WHERE `ID` = '%s' AND Board = '%s'", $this->cleanQuery($description), 
            $this->cleanQuery($threadid), $this->cleanQuery($board) ) );
        
        return sprintf( "Updated thread %s (/%s/)<br />\r\n", $threadid, $board );
    }
    
    public function getOngoingThreadCount()
    {
        $query = $this->mysqli->query("SELECT COUNT(`ID`) FROM `threads` WHERE `Status` = '1'");
        if ( !$query )
            die( 'Could not query database: ' . $this->mysqli->error );
        
        $row = $query->fetch_row();
        return $row[0];
    }
    
    public function getThreads()
    {
        $query = $this->mysqli->query("SELECT * FROM `threads` ORDER BY `Board`, `TimeAdded`, `ID` ASC");
        if ( !$query )
            die( 'Could not query database: ' .$this->mysqli->error);
        
        $thrarray = array();
        
        while ( $thr = $query->fetch_object() )
        {
            $posts = $this->mysqli->query( sprintf( "SELECT * FROM `posts` WHERE `ThreadID` = '%s' AND
                `Board` = '%s' ORDER BY `PostTime` DESC", $thr->ID, $thr->Board));
            $lasttime = 0;
            if ( !$posts )
                die( 'Could not query database: ' . $this->mysqli->error );
            
            if ( $posts->num_rows > 0 )
                $lasttime = $posts->fetch_object()->PostTime;
            
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
        
        return $thrarray;
    }
}
?>