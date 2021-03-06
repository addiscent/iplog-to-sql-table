<?php
/*
    File: iplog-database.php
    Product:  iplog-to-sql-table
    Rev 2014.0925.0850
    Copyright (C) 2016 Rex Addiscentis - raddiscentis@addiscent.com
   
    Description:
    
        The IPlogEntriesData class implements database access and data insertion
        processes.
    
    Developer's notes:
    
        In addition to connecting the database and ensuring the specified table
        exists in the database, this class inserts records into the database.
        Note that it will not insert a duplicate record.

        For more information about re-using the source code in this product, see
        the .php files.
    
    License:

        The license under which this software product is released is GPLv2.  
    
    Disclaimer:
    
        Some of the source code used in this product may have been re-used from
        other sources.
        
        None of the source code in this product, original or derived, has been
        surveyed for vulnerabilities to potential security risks.
        
        Use at your own risk, author is not liable for damages, product is not
        warranted to be useful for anything, copyrights held by their respective
        owners.
*/

/*
    These definitions are part of the CommandLineArguments Class Interface
    
    define ( "CLA_VERBOSITY_MODE_ALL", ... );
    define ( "CLA_VERBOSITY_MODE_GENERAL", ... );
    define ( "CLA_VERBOSITY_MODE_LOG", ... );
    define ( "CLA_VERBOSITY_MODE_SILENT", ... );
*/

define ( "IPLDB_ERR_NONE", 100 );
define ( "IPLDB_ERR_UNABLE_TO_CONNECT_DB", 101 );
define ( "IPLDB_ERR_DB_INSERTION_FAIL", 102 );
define ( "IPLDB_ERR_IPL_RECORD_NULL", 103 );
define ( "IPLDB_ERR_DB_PROBE_FAIL", 104 );
define ( "IPLDB_ERR_DB_DUPLICATE_IPADDRESS", 105 );

class IPlogEntriesData {
    
    private $db_host = NULL;
    private $db_user = NULL;
    private $db_user_pwd = NULL;
    private $db_name = NULL;
    private $db_table_name = NULL;

    private $db_connection = NULL;

    private $last_failed_insertion_message = NULL;
    
    private $ipdb_verbosity_mode = 0;

    public function IPlogEntriesData (
                                    $db_host = NULL,
                                    $db_user = NULL,
                                    $db_user_pwd = NULL,
                                    $db_name = NULL,
                                    $db_table_name = NULL,
                                    $verbosity_mode
                                  )
    {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_user_pwd = $db_user_pwd;
        $this->db_name = $db_name;
        $this->db_table_name = $db_table_name;
        $this->ipdb_verbosity_mode = $verbosity_mode;
        
        if ( $this->db_host && $this->db_user && $this->db_name ) {
            
            $this->db_connection =  new mysqli
                                            (
                                            $this->db_host,
                                            $this->db_user,
                                            $this->db_user_pwd,
                                            $this->db_name
                                            );
            // Check db connection
            if ( $this->db_connection->connect_errno ) {
                
                $msg = "IPlogEntriesData - Failed to connect to MySQL : " . mysqli_connect_error() . "\n";
                
                $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                
                $this->db_connection = NULL;
                
            } else {
                
                $msg = "IPlogEntriesData : Connected to MySQL.\n";
                
                $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
                // Create SQL table
                $create_table_sql = "CREATE TABLE $this->db_table_name
                                        (
                                            IPLid INT NOT NULL AUTO_INCREMENT,
                                            PRIMARY KEY(IPLid),
                                            DateTimeCreated TEXT NOT NULL,
                                            IPaddress TEXT NOT NULL,
                                            LogDateTime TEXT NOT NULL,
                                            MethodURI TEXT NOT NULL,
                                            Status INT NOT NULL,
                                            PageSize INT NOT NULL,
                                            Referer TEXT NOT NULL,
                                            Agent TEXT NOT NULL,
                                            Host TEXT NOT NULL
                                        )";
                        
                $result = $this->db_connection->query ( $create_table_sql );
                
                if ( $result ) {
                    
                    $msg = "IPlogEntriesData : Table '$this->db_table_name' created successfully\n";
                    
                    $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                } else {
                   
                    $msg = "IPlogEntriesData : " . $this->db_connection->error . "\n";
                    
                    $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                }
            }
        }
    }
    
    private function verbosity_echo ( $string, $verbosity_mode ) {
        
        if ( $this->ipdb_verbosity_mode >= $verbosity_mode )
            echo $string;
    }
    
    public function insert_iplog_record ($ip_log_record = NULL ) {
        
        if ( !$ip_log_record )
            return IPLDB_ERR_IPL_RECORD_NULL;
            
        if ( !$this->db_connection )
            return IPLDB_ERR_UNABLE_TO_CONNECT_DB;
            
        $ip_log_record['MethodURI'] = $this->db_connection->real_escape_string ( $ip_log_record['MethodURI'] );
        $ip_log_record['Referer'] = $this->db_connection->real_escape_string ( $ip_log_record['Referer'] );
        $ip_log_record['Agent'] = $this->db_connection->real_escape_string ( $ip_log_record['Agent'] );

        // ensure we do not append a duplicate ip log record
        $probe_sql = "SELECT * FROM $this->db_table_name WHERE
                            IPaddress = '$ip_log_record[IPaddress]' and
                            LogDateTime = '$ip_log_record[LogDateTime]' and
                            MethodURI = '$ip_log_record[MethodURI]' and
                            Status = $ip_log_record[Status] and
                            PageSize = $ip_log_record[PageSize] and
                            Referer = '$ip_log_record[Referer]' and
                            Agent = '$ip_log_record[Agent]' and
                            Host = '$ip_log_record[Host]'
                    ";
        
        $result = $this->db_connection->query ( $probe_sql );
        
        if ( !$result ) {
            
            $this->last_failed_insertion_message = $this->db_connection->error;
            
            return IPLDB_ERR_DB_PROBE_FAIL;
        }


        $row = mysqli_fetch_array ( $result );
        
        if ( $row ) {
            
            $msg = "insert_iplog_record : Record probe successful, duplicate record detected\n";
            
            $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
            return IPLDB_ERR_DB_DUPLICATE_IPADDRESS;
        }
        
        $msg = "insert_iplog_record : No duplicate record detected\n";
        
        $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
        $insert_sql = "INSERT INTO $this->db_table_name
                            (
                                IPLid,
                                DateTimeCreated,
                                IPaddress,
                                LogDateTime,
                                MethodURI,
                                Status,
                                PageSize,
                                Referer,
                                Agent,
                                Host
                            )
                        VALUES
                            (
                                $ip_log_record[IPLid],
                                '$ip_log_record[DateTimeCreated]',
                                '$ip_log_record[IPaddress]',
                                '$ip_log_record[LogDateTime]',
                                '$ip_log_record[MethodURI]',
                                $ip_log_record[Status],
                                $ip_log_record[PageSize],
                                '$ip_log_record[Referer]',
                                '$ip_log_record[Agent]',
                                '$ip_log_record[Host]'
                            )";
        
            $result = $this->db_connection->query ($insert_sql);
            
            if ( !$result ) {
                
                $this->last_failed_insertion_message = $this->db_connection->error;
                
                $msg = "insert_iplog_record : Record insertion failed\n";
                
                $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                
                return IPLDB_ERR_DB_INSERTION_FAIL;
            }

        $msg = "insert_iplog_record : Record insertion successful\n";
        
        $this->verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
        // record inserted
        return IPLDB_ERR_NONE;
    }
    
    public function get_ipldb_error () {
        
        return $this->last_failed_insertion_message;
    }
}

?>
