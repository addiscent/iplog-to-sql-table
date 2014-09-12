<?php
/*
    File: ipl-to-db.php
    Product:  iplog-to-sql-table
    Rev 2014.0911.2200
    by ckthomaston@gmail.com
   
    Description:
    
        Reads an IP log file, parses fields from each line, and inserts one
        record of fields for each line into an SQL database table.  If the
        specified table does not already exist in the SQL database, it will
        be created.  If a table already exists, the IP log records are appended.
        
        If parsing an IP log file line fails due to malformed fields, or if the 
        field does not pass validation, that record will not be inserted into the
        table.  However, further attempts will be made to fetch, parse, and insert
        subsequent lines and records, until EOF.
        
        Usage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername
                dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname
                [insert] [maxl=number] [pbrk] [ibrk] [maxverb]
        
        Where:  fname=   IP log file name, (required)
               dhname=   SQL db server, (host), name, (required)
               duname=   SQL db user name, (required)
                dupwd=   SQL db user password, (required)
                dname=   SQL db name, (required)
                tname=   SQL db table, (required)
                hname=   Host domain name or IP address, (required)
                insert   No Argument.  Causes insertion of successfully\n"
                         parsed/validated IP records, (optional)\n"
                 maxl=   Maximum number of lines to
                         read from IP log file, (optional)
                  pbrk   No Argument.  Causes  exit if a parse error
                         is encountered, (optional)
                  ibrk   No Argument.  Causes exit if an insertion error
                         is encountered, (optional)
               maxverb   No Argument.  Enables all tracing echo, (optional)
        
        
        IMPORTANT - The use of the "insert" option is REQUIRED if you wish
        records to be inserted into the SQL database.  By default, the "insert"
        option is NOT SET.  This gives the behavior of making the program "safe"
        to use for examination of success/fail rates of IP log parsing and
        validation errors, without commtting records to the SQL database.  If
        this fail-safe was not the default behavior, a user could
        unintentionally append records to the database, therby contaminating it
        with duplicate records.  Forcing the user to specify the "insert"
        option prevents unintentional record insertion.
        
        The "maxl" and "maxverb" options typically are not used in production,
        they are provided as a convenience for testing and debugging.

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

define("CLI_ERR_NONE", 0);
define("CLI_ERR_MISSING_REQUIRED_ARGUMENT", 101);
define("CLI_ERR_UNABLE_TO_OPEN_FILE", 102);
define("CLI_ERR_UNABLE_TO_CONNECT_DB", 103);


function dbg_echo ($string, $do_echo = FALSE) {
    if ($do_echo)
        echo $string;
}

// main() ===========================================

// mark time for elapsed time displayed in Summary
$start_time = time();

echo "ipl-to-db.php v2014.0911.2200\n"
    . "Inserts parsed IP log file line fields into an SQL db.\n";

// put command line arguments into the $_GET variable
parse_str (implode('&', array_slice($argv, 1)), $_GET);

$CommandLineData = new CommandLineArguments ($_GET);

if ($CommandLineData->is_required_missing())
    exit(CLI_ERR_MISSING_REQUIRED_ARGUMENT);

// required command line arguments ------------------------------------

// get IP log file name from command line
$ip_log_filename = $CommandLineData->get_iplogfile_name();

// SQL db-specific data
$db_host = $CommandLineData->get_db_host_name(); 
$db_user = $CommandLineData->get_db_user_name();
$db_user_pwd = $CommandLineData->get_db_user_password();
$db_name = $CommandLineData->get_db_name();
$db_table_name = $CommandLineData->get_db_table_name();

// name of the host for which the IP log file was generated
$this_host = $CommandLineData->get_log_file_host_name();

// optional command line arguments ---------------------------------------

// user may want to insert records into SQL database
$insert_record_option = $CommandLineData->get_insert_option();
    
// user may want to set max num of lines to read from IP log file.
// Typically set to a low value during debug, very high in production
$max_file_lines = $CommandLineData->get_max_file_lines();

// user may want to break on parse or validation fail
$parse_fail_break = $CommandLineData->get_parse_fail_break();
    
// user may want to break on insertion into SQL table fail
$insert_fail_break = $CommandLineData->get_insert_fail_break();

// user may want max verbosity, only useful for short test IP log files
$full_trace_output = $CommandLineData->get_full_trace_output();

$IPlogFile = new IPlogFile ($ip_log_filename);

$IPlogDatabase = new IPlogDatabase ($db_host, $db_user, $db_user_pwd, $db_name, $db_table_name);

/*
    Seven fields are parsed from the IP log file:

        - IPaddress
        - DateTime
        - MethodURI
        - Status
        - PageSize
        - Referer
        - Agent

    Ten fields are inserted into the SQL db table:

    The first is the record ID, $ip_event_number, which is the record index.
    It is always 0 so that the query appends records, instead of inserting
    them at the specific table record index given by $ip_event_number.

    The next seven fields inserted are those enumerated above, parsed
    from the IP log file.

    The next field inserted, $this_host, is the domain or IP address of the
    host, (given on the command line), for which the IP log file was created. 

    Lastly, the current time, $insertion_time, is created on-the fly
    for each record.
*/

dbg_echo ("Processing records...\n", TRUE);

$ip_log_record = array
                    (
                        "IPEventNumber" => 0,
                        "IPaddress" => "",
                        "DateTime" => "",
                        "MethodURI" => "",
                        "Status" => 0,
                        "PageSize" => 0,
                        "Referer" => "",
                        "Agent" => "",
                        "ThisHost" => "",
                        "InsertionTime" => ""
                    );
    
// if SQL auto-increment/auto-append is desired, this value must be zero. 
$ip_event_number = 0;

// date and time this record was inserted into SQL table.
// Format is yyyy.mmdd.hhmm.ss because it is trivial to sort it as an
// SQL table column
$insertion_time = NULL; 

// the IP log line for one access, (string)
$ip_record_line = NULL;

// misc for IP log file working stats
$logfile_readln_attempts = 0;
$parse_fail_count = 0;

// misc for SQL table insertion working stats
$lines_inserted = 0;
$insert_fail_count = 0;

// process up to $max_file_lines in the IP log file.
while ($logfile_readln_attempts++ < $max_file_lines) {

    $ip_evnt_flds = $IPlogFile->get_iplog_record($logfile_readln_attempts, $ip_record_line, $full_trace_output);
    
    if ($ip_evnt_flds == IPLF_ERR_UNABLE_TO_OPEN_FILE) {
        dbg_echo ("Cannot continue, exiting\n\n", TRUE);
        exit (CLI_ERR_UNABLE_TO_OPEN_FILE);
    }

    if ($ip_evnt_flds == IPLF_EOF_IPLOG) {
        $logfile_readln_attempts--; // don't count EOF attempt
        break;
    }

    // if parse or validation fails, skip insertion into SQL table.  If no
    // parse break set, continue to read and parse next IP log file record
    if (!$ip_evnt_flds) {
        $parse_fail_count++;
        dbg_echo ("IP log file line contents : $ip_record_line"
            . "NOTICE : IP log file line parse or validation failed, skipping\n"
            . "         this line without inserting into SQL table.\n", TRUE);
        if ($parse_fail_break) {
            dbg_echo ("pbrk : IP log file line parse failed, no more IP log lines will be read.\n", TRUE);
            break;
        }
    } else { // we have a populated $ip_evnt_flds array, insert it into the db if allowed
        if ($insert_record_option) {  // if insert-record-option set, do insertion
            $ip_log_record["IPEventNumber"] = $ip_event_number;
            $ip_log_record["IPaddress"] = $ip_evnt_flds["IPaddress"];
            $ip_log_record["DateTime"] = $ip_evnt_flds["DateTime"];
            $ip_log_record["MethodURI"] = $ip_evnt_flds["MethodURI"];
            $ip_log_record["Status"] = $ip_evnt_flds["Status"];
            $ip_log_record["PageSize"] = $ip_evnt_flds["PageSize"];
            $ip_log_record["Referer"] = $ip_evnt_flds["Referer"];
            $ip_log_record["Agent"] = $ip_evnt_flds["Agent"];
            $ip_log_record["ThisHost"] = $this_host;
            $ip_log_record["InsertionTime"] = date("Y.md.Hi.s");
    
            $insertion_result = $IPlogDatabase->insert_iplog_record ($ip_log_record, $full_trace_output);
            
            if ($insertion_result == IPLDB_ERR_UNABLE_TO_CONNECT_DB) {
                dbg_echo ("Cannot continue, exiting\n\n", TRUE);
                exit (CLI_ERR_UNABLE_TO_CONNECT_DB);
            }
            
            if ($insertion_result == IPLDB_ERR_DB_INSERTION_FAIL) {
                $failed_insertion_message = $IPlogDatabase->get_ipldb_error();
                dbg_echo ("\nError during record INSERT : " . $failed_insertion_message . "\n", TRUE);
                dbg_echo (
                    "Dump SQL insertion query\n" .
                    "   IPEventNumber : $ip_log_record[IPEventNumber]\n" .
                    "   IPaddress : $ip_log_record[IPaddress]\n" .
                    "   DateTime : $ip_log_record[DateTime]\n" .
                    "   MethodURI : $ip_log_record[MethodURI]\n" .
                    "   Status : $ip_log_record[Status]\n" .
                    "   PageSize : $ip_log_record[PageSize]\n" .
                    "   Referer : $ip_log_record[Referer]\n" .
                    "   Agent : $ip_log_record[Agent]\n" .
                    "   ThisHost : $ip_log_record[ThisHost]\n" .
                    "   InsertionTime : $ip_log_record[InsertionTime]\n"
                    , TRUE);
                $insert_fail_count++;
                dbg_echo ("Insertion failed, record not added. Current insertion fail count: $insert_fail_count\n", $full_trace_output);
                if ($insert_fail_break) {
                    dbg_echo ("ibrk : SQL insertion failed, no more IP log lines will be read.\n", TRUE);
                    break;
                }
            } 
            $lines_inserted++;
            dbg_echo ("This record added to SQL table.\n", $full_trace_output);
        } else
            dbg_echo ("insert : insert-record-option not set, skipping insertion.\n", $full_trace_output);
    }
}

$end_time = time ();
$elapsed_time = $end_time - $start_time;
$elapsed_time = date ("H:i:s",$elapsed_time);

dbg_echo ("\nSUMMARY\n", TRUE);
dbg_echo ("  All specified records processed.\n", TRUE);
dbg_echo ("  Elapsed time, (hr:min:sec) : $elapsed_time\n\n", TRUE);
dbg_echo ("  Totals\n", TRUE);
dbg_echo ("    IP log file lines read : $logfile_readln_attempts\n", TRUE);
dbg_echo ("    IP log line parse or validation errors : $parse_fail_count\n", TRUE);
dbg_echo ("    Records inserted into SQL table : $lines_inserted\n", TRUE);
dbg_echo ("    SQL insertion errors : $insert_fail_count\n\n", TRUE);

exit(CLI_ERR_NONE);
?>
