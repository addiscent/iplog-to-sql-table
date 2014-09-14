<?php
/*
    File: iplog-database.php
    Product:  iplog-to-sql-table
    Rev 2014.0913.2200
    Copyright (C) Charles Thomaston, ckthomaston@gmail.com
   
    Description:
    
        The IPlogDatabase class implements database access and data insertion
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

define ("IPLDB_ERR_NONE", 100);
define ("IPLDB_ERR_UNABLE_TO_CONNECT_DB", 101);
define ("IPLDB_ERR_DB_INSERTION_FAIL", 102);
define ("IPLDB_ERR_IPL_RECORD_NULL", 103);
define ("IPLDB_ERR_DB_PROBE_FAIL", 104);
define ("IPLDB_ERR_DB_DUPLICATE_IPADDRESS", 105);

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
                                        IPid INT NOT NULL AUTO_INCREMENT,
                                        PRIMARY KEY(IPid),
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
        
        // ensure we do not append a duplicate ip log record
        $probe_sql = "SELECT * FROM $this->db_table_name WHERE
                            IPaddress = '$ip_log_record[IPaddress]' and
                            DateTime = '$ip_log_record[DateTime]' and
                            MethodURI = '$ip_log_record[MethodURI]' and
                            Status = $ip_log_record[Status] and
                            PageSize = $ip_log_record[PageSize] and
                            Referer = '$ip_log_record[Referer]' and
                            Agent = '$ip_log_record[Agent]' and
                            ThisHost = '$ip_log_record[ThisHost]'
                    ";
        
        $result = $this->db_connection->query ($probe_sql);
        if (!$result) {
            $this->last_failed_insertion_message = $this->db_connection->error;
            return IPLDB_ERR_DB_PROBE_FAIL;
        }


        $row = mysqli_fetch_array($result);
        if ($row) {
            dbg_echo ("Record probe successful, duplicate record detected\n", $full_trace_output);
            return IPLDB_ERR_DB_DUPLICATE_IPADDRESS;
        }
        dbg_echo ("No duplicate record detected\n", $full_trace_output);

        $insert_sql = "INSERT INTO $this->db_table_name
                            (
                                IPid,
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
                                $ip_log_record[IPid],
                                '$ip_log_record[IPaddress]',
                                '$ip_log_record[DateTime]',
                                '$ip_log_record[MethodURI]',
                                $ip_log_record[Status],
                                $ip_log_record[PageSize],
                                '$ip_log_record[Referer]',
                                '$ip_log_record[Agent]',
                                '$ip_log_record[ThisHost]',
                                '$ip_log_record[InsertionTime]'
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