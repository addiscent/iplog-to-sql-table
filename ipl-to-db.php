<?php
/*
    File: ipl-to-db.php
    Product:  iplog-to-sql-table
    Rev 2014.1002.2200
    Copyright (C) 2016 Rex Addiscentis - raddiscentis@addiscent.com
   
    Description:
    
        Reads an IP log file and inserts into an SQL database table one record
        of fields for each line.
        
        If parsing an IP log file line fails due to malformed fields, or if the 
        field does not pass validation, that record will not be inserted into the
        table.  However, further attempts will be made to fetch, parse, and insert
        subsequent lines and records, until EOF.
        
        If the specified table does not already exist in the SQL database, it
        will be created.  If a table already exists, records in the table for
        which IP log file entries already exist will not be inserted.
        
        Usage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername
                dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname
                [insert] [maxl=number] [maxdup=number] [pbrk] [ibrk] [vmode=enum]  [ioi]
        
        Where:  fname=   IP log file name, (required)
        
               dhname=   SQL db server, (host), name, (required)
               
               duname=   SQL db user name, (required)
               
                dupwd=   SQL db user password, (required)
                
                dname=   SQL db name, (required)
                
                tname=   SQL db table, (required)
                
                hname=   Host domain name or IP address, (required)
                
                insert   No argument. Causes insertion into db of successfully
                         parsed/validated IP records
                         
                 maxl=   Optional. Maximum number of lines to read from IP log
                         file. Typically only used during debugging/testing.
                         If not specified, the program default is to read to
                         input IP log file EOF
                         
               maxdup=   Optional. Stop processing when this number of duplicate
                         records is reached. Because duplicates are searched for
                         from most recent in time to oldest, this will reduce
                         the amount of unnecessary search time. Default is
                         ITST_NO_LIMIT
                         
                  pbrk   Optional. No argument. Causes exit if a parse error
                         is encountered. Typically only used during
                         debugging/testing
                         
                  ibrk   Optional. No argument. Causes exit if an insertion
                         error is encountered, (optional)
                         
            vmode=enum   Sets verbosity mode, specified by enum.  'vmode' is a
                         level of tracing verbosity ranging from 'all', (max
                         STDOUT messages enabled), to 'silent', (all STDOUT
                         messages disabled).  Between the two, 'gen', (General),
                         has more STDOUT messages than 'log', (logging mode).
                         
                            all - Maximum verbosity. Very detailed tracing.
                                  Typically only used for debugging/testing.
                                  Used most often in combination with the maxl
                                  option
                                  
                            gen - General. Displays information typically
                                  desired when executed by command line user,
                                  such as settings of required command line
                                  arguments and options, error messages
                                  indicating processing results, and a complete
                                  summary  
                                  
                            log - Logging. Limits verbosity to most important
                                  statistics needed for logging  
                                  
                         silent - Program will emit no output to STDOUT
                         
                  ioi    Optional. No argument. Forces display of an "Item of
                         Interest". This is a data or event item which will be
                         displayed regardless of the 'vmode' setting, (except
                         for 'silent', nothing is displayed during 'silent'
                         vmode). At this time, there is only one IOI, which is
                         the "MethodURI field malformed" error annunciation.
        
        
        IMPORTANT - The use of the "insert" option is REQUIRED if you wish
        records to be inserted into the SQL database.  By default, the "insert"
        option is NOT SET.  This gives the behavior of making the program "safe"
        by default to use for examination of success/fail rates of IP log parsing
        and validation errors, without commtting records to the SQL database.
        
    Files:
    
        A complete set of files for this distribution contains all of the following:
        
        - readme.txt
        - ipl-to-db.php - command line script which parses the log and inserts into table
        - command-line-arguments.php - a class required for ipl-to-db.php
        - iplog-file.php - a class required for ipl-to-db.php
        - iplog-database.php - a class required for ipl-to-db.php
        - iplog-example.log - a short example IP log file used for testing
        - itst-class-diagram.gif - docmentation for developers
        - README.md - project description on GitHub.com
        - LICENSE - A license file describing terms of use
        
    Installation Instructions:
    
        - Download either tar.gz or .zip version of "iplog-to-sql-table-X.X.X",
        - Un-tar or gunzip "iplog-to-sql-table-X.X.X"
        - Note the following files and directory structure which results
        
              Directory : "iplog-to-sql-table-X.X.X" - contains:
              
                   File      : "ipl-to-db.php"
                   File      : "README.md"
                   Directory : "iltd-includes" - contains:
                   
                            File : "readme.txt"
                            File : "command-line-arguments.php"
                            File : "iplog-file.php"
                            File : "iplog-database.php"
                            File : "iplog-example.log"
                            File : "itst-class-diagram.gif"
                            File : "LICENSE"
        
        Test "ipl-to-db.php" by executing:
        
            php ipl-to-db.php
            
        The result should be a usage display similar to shown above in
        "Description" section.
        
    Operation Details:
    
        Seven fields are parsed from the IP log file:

            - IPaddress
            - DateTime
            - MethodURI
            - Status
            - PageSize
            - Referer
            - Agent
    
        Ten fields are inserted into the SQL db table:
    
        The first is "IPLid", which is the record index. It is always 0 so the
        query appends records, instead of inserting them at the specific table
        record index given by IPLid.
    
        The next seven fields are those enumerated above, parsed from the IP
        log file.
    
        The next field, "Host", is the domain or IP address of the host,
        (specified on the command line), for which the IP log file was created. 
    
        Lastly, the current time, "InsertionTime", is created on-the fly for
        each record.

    Developer's notes:
    
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

require "./iltd-includes/command-line-arguments.php";
require "./iltd-includes/iplog-file.php";
require "./iltd-includes/iplog-database.php";

/*
    These definitions are part of the CommandLineArguments Class Interface
    
    define ( "CLA_VERBOSITY_MODE_GENERAL", ... );
    define ( "CLA_VERBOSITY_MODE_LOG", ... );
    define ( "CLA_VERBOSITY_MODE_ALL", ... );
    define ( "CLA_VERBOSITY_MODE_SILENT", ... );
*/

define ( "CLI_ERR_NONE", 0 );
define ( "CLI_ERR_MISSING_REQUIRED_ARGUMENT", 101 );
define ( "CLI_ERR_UNABLE_TO_OPEN_FILE", 102 );
define ( "CLI_ERR_UNABLE_TO_CONNECT_DB", 103 );
define ( "CLI_ERR_UNABLE_TO_CONTINUE", 104 );

define ( "ITST_NO_DUP_LIMIT", 0 );

$return_msg = array // message strings returned by CommandLineArguments class
                (
                "fname" => "",
                "dhname" => "",
                "duname" => "",
                "dupwd" => "",
                "dname" => "",
                "tname" => "",
                "hname" => "",
                "insert" => "",
                "maxl" => "",
                "maxdup" => "",
                "pbrk" => "",
                "ibrk" => "",
                "vmode" => "",
                "ioi" => ""
                );
               
function verbosity_echo ( $string, $verb_mode ) {
    
    global $verbosity_mode;
    
    if ( $verbosity_mode >= $verb_mode )
        echo $string;
}
    
// mark time for elapsed time displayed in Summary
$start_time = time ();

// put command line arguments into the $_GET variable
parse_str ( implode ( '&', array_slice ( $argv, 1 ) ), $_GET );

$CommandLineData = new CommandLineArguments (); // process command line

$verbosity_mode = $CommandLineData->get_verbosity_mode ( $return_msg [ 'vmode' ] ); // out string not used here

$msg = "ipl-to-db.php v2014.1002.1800\n";

verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                
$msg = "Inserts parsed IP log file line fields into an SQL db.\n";

verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
$msg = "copyright (C) 2016 Rex Addiscentis - raddiscentis@addiscent.com\n";

verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
if ( $CommandLineData->is_required_missing ( $out_msg ) ) {
    
    verbosity_echo ( "\n", CLA_VERBOSITY_MODE_LOG );
    verbosity_echo ( $out_msg, CLA_VERBOSITY_MODE_LOG );
            
    $CommandLineData->display_usage ();

    exit ( CLI_ERR_MISSING_REQUIRED_ARGUMENT );
}

// required command line arguments -----------------------------

// get IP log file name from command line
$ip_log_filename = $CommandLineData->get_iplogfile_name ( $return_msg [ 'fname' ] );

verbosity_echo ( "\n" . $return_msg [ 'fname' ], CLA_VERBOSITY_MODE_GENERAL );

// SQL db-specific command line arguments
$db_host = $CommandLineData->get_db_host_name ( $return_msg [ 'dhname' ] ); 

verbosity_echo ( $return_msg [ 'dhname' ], CLA_VERBOSITY_MODE_GENERAL );

$db_user = $CommandLineData->get_db_user_name ( $return_msg [ 'duname' ] );

verbosity_echo ( $return_msg [ 'duname' ], CLA_VERBOSITY_MODE_GENERAL );

$db_user_pwd = $CommandLineData->get_db_user_password ( $return_msg [ 'dupwd' ] );

verbosity_echo ( $return_msg [ 'dupwd' ], CLA_VERBOSITY_MODE_GENERAL );

$db_name = $CommandLineData->get_db_name ( $return_msg [ 'dname' ] );

verbosity_echo ( $return_msg [ 'dname' ], CLA_VERBOSITY_MODE_GENERAL );

$db_table_name = $CommandLineData->get_db_table_name ( $return_msg [ 'tname' ] );

verbosity_echo ( $return_msg [ 'tname' ], CLA_VERBOSITY_MODE_GENERAL );

$this_host = $CommandLineData->get_log_file_host_name ( $return_msg [ 'hname' ] );

verbosity_echo ( $return_msg [ 'hname' ], CLA_VERBOSITY_MODE_GENERAL );

// optional command line arguments -------------------------------

// user may want to insert records into SQL database
$insert_record_option = $CommandLineData->get_insert_option ( $return_msg [ 'insert' ] );

verbosity_echo ( $return_msg [ 'insert' ], CLA_VERBOSITY_MODE_GENERAL );
    
// user may want to set max num of lines to read from IP log file.
// Typically set to a low value during debug, very high in production
$max_file_lines = $CommandLineData->get_max_file_lines ( $return_msg [ 'maxl' ] );

verbosity_echo ( $return_msg [ 'maxl' ], CLA_VERBOSITY_MODE_GENERAL );

// user may want to stop processing after maxdup number of duplicate skips
$duplicate_event_max = $CommandLineData->get_max_duplicates ( $return_msg [ 'maxdup' ] );

verbosity_echo ( $return_msg [ 'maxdup' ], CLA_VERBOSITY_MODE_GENERAL );

// user may want to break on parse or validation fail
$parse_fail_break = $CommandLineData->get_parse_fail_break ( $return_msg [ 'pbrk' ] );

verbosity_echo ( $return_msg [ 'pbrk' ], CLA_VERBOSITY_MODE_GENERAL );
    
// user may want to break on insertion into SQL table fail
$insert_fail_break = $CommandLineData->get_insert_fail_break ( $return_msg [ 'ibrk' ] );

verbosity_echo ( $return_msg [ 'ibrk' ], CLA_VERBOSITY_MODE_GENERAL );

verbosity_echo ( $return_msg [ 'vmode' ], CLA_VERBOSITY_MODE_GENERAL );

// user may want to break on insertion into SQL table fail
$item_of_interest = $CommandLineData->get_items_of_interest ( $return_msg [ 'ioi' ] );

verbosity_echo ( $return_msg [ 'ioi' ], CLA_VERBOSITY_MODE_GENERAL );

verbosity_echo ( "\n", CLA_VERBOSITY_MODE_GENERAL );

$IPlogFile = new IPlogFile ( $ip_log_filename, $verbosity_mode );

$IPlogEntriesData = new IPlogEntriesData ( $db_host, $db_user, $db_user_pwd, $db_name, $db_table_name, $verbosity_mode );

// if SQL auto-increment/auto-append is desired, this value must be zero. 
$ip_event_number = 0;

// date and time this record was inserted into SQL table.
// Format is yyyy.mmdd.hhmm.ss because it is trivial to sort it as an
// SQL table column
$insertion_time = NULL; 

// the IP log line for one access, (string)
$ip_record_line = NULL;

// misc for IP log file working stats
$logfile_lines_read = 0;
$parse_fail_count = 0;

// misc for SQL table insertion working stats
$lines_inserted = 0;
$insert_fail_count = 0;
$loop_break = FALSE;

$ip_log_events = array ();

// only process number of lines specified on command line
for ( $i = 0, $array_index = 0; $i < $max_file_lines; $i++ ) { 
    
    $ip_event_flds = $IPlogFile->get_iplog_record(($logfile_lines_read + 1), $ip_record_line, $item_of_interest, $out_msg);
    
    if ( $ip_event_flds == IPLF_ERR_UNABLE_TO_OPEN_FILE )
        exit ( CLI_ERR_UNABLE_TO_OPEN_FILE );

    if  ( $ip_event_flds != IPLF_EOF_IPLOG ) {

        $logfile_lines_read++;
        
        if ( $ip_event_flds ) // we have a populated $ip_event_flds array, add it to the IP log events list
        
            $ip_log_events [ $array_index++ ] = $ip_event_flds;
            
        else { // $ip_event_flds == NULL
        
            $parse_fail_count++;
            
            if ( $verbosity_mode == CLA_VERBOSITY_MODE_GENERAL)
                    echo "\n";
            
            $msg = "IP log file line $logfile_lines_read contents : $ip_record_line"
                    . "NOTICE : IP log file line $logfile_lines_read parse or validation failed, skipping this line without inserting into SQL table.\n";
                
            if ( ( $verbosity_mode == CLA_VERBOSITY_MODE_LOG ) && ( $item_of_interest ) )
                echo "\n";
            
            if ( $item_of_interest )
                verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
            else
                verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                            
            if ($parse_fail_break) {
    
                $msg = "pbrk : IP log file line parse failed, no more IP log lines will be read.\n";
                
                verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
                break;
            }
        }
        
    } else
        break;
}

$reversed_ip_log_events = array_reverse ( $ip_log_events );  // we want to start with the most recent entry in log, not oldest

$duplicate_event_count = 0;

$ip_log_record = array
                    (
                        "IPLid" => 0,
                        "DateTimeCreated" => "",
                        "IPaddress" => "",
                        "LogDateTime" => "",
                        "MethodURI" => "",
                        "Status" => 0,
                        "PageSize" => 0,
                        "Referer" => "",
                        "Agent" => "",
                        "Host" => ""
                    );
    
$insert_loop_count = 0;

define ("HEARTBEAT_COUNT", 100 );

$hearbeat_count = 0; // echo a hearbeat once in a while

// we have a populated $ip_event_flds array, insert it into the db.  Because we are traversing
// the list from most recent, if we reach an entry which already exists in the table, we are done
foreach ($reversed_ip_log_events as $ip_event_flds) {
    
    if ( ( $duplicate_event_count < $duplicate_event_max ) || ( $duplicate_event_max == ITST_NO_DUP_LIMIT ) ){
        
        if ( $insert_record_option ) {  // if insert-record-option set, do insertion
 
            $insert_loop_count++;
           
            if ( ( intval ( $insert_loop_count / HEARTBEAT_COUNT ) != $hearbeat_count )
                   && ( $verbosity_mode == CLA_VERBOSITY_MODE_GENERAL ) )
                {
                    verbosity_echo ( ".", CLA_VERBOSITY_MODE_GENERAL );
                    
                    $hearbeat_count = intval ( $insert_loop_count / HEARTBEAT_COUNT );
                }
        
            $ip_log_record [ 'IPLid' ] = $ip_event_number;
            $ip_log_record [ 'DateTimeCreated' ] = date ( "Y.md.Hi.s" );
            $ip_log_record [ 'IPaddress' ] = $ip_event_flds [ 'IPaddress' ];
            $ip_log_record [ 'LogDateTime' ] = $ip_event_flds [ 'LogDateTime' ];
            $ip_log_record [ 'MethodURI' ] = $ip_event_flds [ 'MethodURI' ];
            $ip_log_record [ 'Status' ] = $ip_event_flds [ 'Status' ];
            $ip_log_record [ 'PageSize' ] = $ip_event_flds [ 'PageSize' ];
            $ip_log_record [ 'Referer' ] = $ip_event_flds [ 'Referer' ];
            $ip_log_record [ 'Agent' ] = $ip_event_flds [ 'Agent' ];
            $ip_log_record [ 'Host' ] = $this_host;
    
            $insertion_result = $IPlogEntriesData->insert_iplog_record ( $ip_log_record );
            
            switch ($insertion_result) {
                
                case IPLDB_ERR_UNABLE_TO_CONNECT_DB:
    
                    $msg = "Cannot continue, exiting\n\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
                    exit (CLI_ERR_UNABLE_TO_CONNECT_DB);
    
                case IPLDB_ERR_DB_INSERTION_FAIL:
                
                    $failed_insertion_message = $IPlogEntriesData->get_ipldb_error();
                    
                    $msg = "Error during record INSERT : " . $failed_insertion_message . "\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                
                    $msg = 
                        "Dump SQL insertion query\n" .
                        "   IPLid : $ip_log_record[IPLid]\n" .
                        "   DateTimeCreated : $ip_log_record[DateTimeCreated]\n";
                        "   IPaddress : $ip_log_record[IPaddress]\n" .
                        "   LogDateTime : $ip_log_record[LogDateTime]\n" .
                        "   MethodURI : $ip_log_record[MethodURI]\n" .
                        "   Status : $ip_log_record[Status]\n" .
                        "   PageSize : $ip_log_record[PageSize]\n" .
                        "   Referer : $ip_log_record[Referer]\n" .
                        "   Agent : $ip_log_record[Agent]\n" .
                        "   Host : $ip_log_record[Host]\n" .
    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                
                    $insert_fail_count++;
    
                    $msg = "Insertion failed, record not added. Current insertion fail count: $insert_fail_count\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                
                    if ($insert_fail_break) {
                        
                        $msg = "ibrk : SQL insertion failed, no more IP log lines will be read.\n";
                        
                        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                    }
                    
                    break;
                    
                case IPLDB_ERR_DB_PROBE_FAIL:
                
                    $msg = "SQL record probe failed\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
                    $failed_insertion_message = $IPlogEntriesData->get_ipldb_error();
                    
                    $msg = "SQL error msg : $failed_insertion_message\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                    
                    break;
                    
                case IPLDB_ERR_DB_DUPLICATE_IPADDRESS:
                    
                    $msg = "Duplicate IP log event record encountered, skipping insertion\n\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                    
                    $duplicate_event_count++;
                    
                    break;
    
                default:
                    
                    $msg = "Record added to SQL table.\n\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                    
                    $lines_inserted++;
            } 
            
        } else {
    
            $msg = "insert : insert-record-option not set, skipping insertion.\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
            
            break;
        }
    } else {
        
            $msg = "insert : reached maximum number of duplicates to skip, finished processing.\n\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
            
            break;
    }
}

$end_time = time ();

$elapsed_time = $end_time - $start_time;

$elapsed_time = date ( "H:i:s",$elapsed_time );

if ( $hearbeat_count && ( $verbosity_mode > CLA_VERBOSITY_MODE_LOG )  ) // if we possibly echoed a heartbeat dot earlier, do newlines
    echo "\n\n";

if ( ( $verbosity_mode == CLA_VERBOSITY_MODE_LOG ) && ( $item_of_interest ) && $parse_fail_count)
    echo "\n";
            
$msg = "Elapsed time (hr:min:sec) : $elapsed_time\n"
        . "Records inserted into SQL table : $lines_inserted\n"
        . "Duplicate record insertions skipped : $duplicate_event_count\n"
        . "IP log file lines read : $logfile_lines_read\n"
        . "IP log line parse or validation errors : $parse_fail_count\n"
        . "SQL insertion errors : $insert_fail_count\n";

verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
            
exit (CLI_ERR_NONE);

?>
