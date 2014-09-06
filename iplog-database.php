<?php
/*
    File: iplog-database.php
    Product:  iplog-to-sql-table
    Rev 2014.0905.2200
    by ckthomaston@gmail.com
   
    Description:
    
        The IPlogDatabase class implements database access and data insertion
        processes.
    
    Developer's notes:
    
        In addition to connecting and disconnecting the database, and ensuring
        the specified table exists in the database, this class inserts
        records into the database.

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

define("IPLDB_ERR_NONE", 100);
define("IPLDB_ERR_UNABLE_TO_CONNECT_DB", 101);
define("IPLDB_ERR_DB_INSERTION_FAIL", 102);
define("IPLDB_ERR_IPL_RECORD_NULL", 103);

class IPlogDatabase {
    
    private $db_host = NULL;
    private $db_user = NULL;
    private $db_user_pwd = NULL;
    private $db_name = NULL;
    private $db_table_name = NULL;
    
    private $db_connection = NULL;
    private $last_failed_insertion_message = NULL;
    
    public function IPlogDatabase ($db_host = NULL, $db_user = NULL, $db_user_pwd = NULL, $db_name = NULL, $db_table_name = NULL) {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_user_pwd = $db_user_pwd;
        $this->db_name = $db_name;
        $this->db_table_name = $db_table_name;
    }
    
    public function __destruct () {
            $this->dbg_echo ("Disconnected from MySQL.\n", TRUE);
    }

    public function insert_iplog_record ($ip_log_record = NULL, $full_trace_output = FALSE) 
    {
        if (!$ip_log_record)
            return IPLDB_ERR_IPL_RECORD_NULL;
            
        if ($this->db_host && $this->db_user && $this->db_name && !$this->db_connection) {
            $this->db_connection =  new mysqli
                                            (
                                            $this->db_host,
                                            $this->db_user,
                                            $this->db_user_pwd,
                                            $this->db_name
                                            );
            // Check db connection
            if ($this->db_connection->connect_errno) {
                $this->dbg_echo ("Failed to connect to MySQL : " . mysqli_connect_error() . "\n", TRUE);
                return IPLDB_ERR_UNABLE_TO_CONNECT_DB;
            }
            $this->dbg_echo ("Connected to MySQL.\n", TRUE);
            
            // Create SQL table
            $create_table_sql = "CREATE TABLE $this->db_table_name
                                    (
                                    IPEventNumber INT NOT NULL AUTO_INCREMENT,
                                    PRIMARY KEY(IPEventNumber),
                                    IPaddress TEXT,
                                    DateTime TEXT,
                                    MethodURI TEXT,
                                    Status INT,
                                    PageSize INT,
                                    Referer TEXT,
                                    Agent TEXT,
                                    ThisHost TEXT,
                                    InsertionTime TEXT
                                    )";
                    
            $result = $this->db_connection->query ($create_table_sql);
            if ($result)
                echo "Table '$this->db_table_name' created successfully\n";
            else
                echo $this->db_connection->error . "\n";
        }
        
        $insert_sql = "INSERT INTO $this->db_table_name
                            (
                                IPEventNumber,
                                IPaddress,
                                DateTime,
                                MethodURI,
                                Status,
                                PageSize,
                                Referer,
                                Agent,
                                ThisHost,
                                InsertionTime
                            )
                        VALUES
                            (
                                $ip_log_record[IPEventNumber],
                                \"$ip_log_record[IPaddress]\",
                                \"$ip_log_record[DateTime]\",
                                \"$ip_log_record[MethodURI]\",
                                $ip_log_record[Status],
                                $ip_log_record[PageSize],
                                \"$ip_log_record[Referer]\",
                                \"$ip_log_record[Agent]\",
                                \"$ip_log_record[ThisHost]\",
                                \"$ip_log_record[InsertionTime]\"
                            )";
        
            $result = $this->db_connection->query ($insert_sql);
            if (!$result) {
                $this->last_failed_insertion_message = $this->db_connection->error;
                dbg_echo ("Record insertion failed\n", TRUE);
                return IPLDB_ERR_DB_INSERTION_FAIL;
            }

        dbg_echo ("Record insertion successful\n", $full_trace_output);
        
        // record inserted
        return IPLDB_ERR_NONE;
    }
    
    public function get_ipldb_error () {
        return $this->last_failed_insertion_message;
    }
    
    private function dbg_echo ($string = NULL, $do_echo = FALSE) {
        if ($do_echo)
            echo $string;
    }
}

?>