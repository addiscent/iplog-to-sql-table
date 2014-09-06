<?php
/*
    command-line-arguments.php
    Product:  iplog-to-sql-table
    Rev 2014.0905.2200
    by ckthomaston@gmail.com
   
    Description:

        The CommandLineArguments class collects switch arguments from the
        command line and provides methods for acessing the switch arguments
        and displaying program usage.
    
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

define("CLA_ERR_MISSING_REQUIRED_ARGUMENT", 201);

class CommandLineArguments {
    
    private $required_missing = FALSE;

    // required for execution
    private $ip_log_filename = NULL;
    private $db_host_name = NULL; 
    private $db_user_name = NULL;
    private $db_user_password = NULL; // if open source, don't show my SQL db password
    private $db_name = NULL;
    private $db_table_name = NULL;
    private $log_file_host_name = NULL;
    
    // optional
    private $max_file_lines = 0; // read to EOF if < 1
    private $parse_fail_break = FALSE;
    private $insert_fail_break = FALSE;
    private $full_trace_output = FALSE;
    

    // Parse command line, set values, and annunciate appropriately.
    // Some arguments are required, others optional
    public function CommandLineArguments ($CLA_GET) {
        // get IP log file name from command line
        if (array_key_exists ( 'fname', $CLA_GET )) {
            $this->ip_log_filename = $CLA_GET['fname'];
            $this->dbg_echo ("\nIP log file name : " . $this->ip_log_filename . "\n", TRUE);
        } else {
            echo "\nNo IP log file name specified.\n";
            $this->required_missing = TRUE;
        }

        if (array_key_exists ( 'dhname', $CLA_GET )) {
            $this->db_host_name = $CLA_GET['dhname'];
            $this->dbg_echo ("SQL db host name : " . $this->db_host_name . "\n", TRUE);
        } else {
            echo "No SQL db host name specified.\n";
            $this->required_missing = TRUE;
        }

        if (array_key_exists ( 'duname', $CLA_GET )) {
            $this->db_user_name = $CLA_GET['duname'];
            $this->dbg_echo ("SQL db user name : " . $this->db_user_name . "\n", TRUE);
        } else {
            echo "No SQL db user name specified.\n";
            $this->required_missing = TRUE;
        }

        if (array_key_exists ( 'dupwd', $CLA_GET )) {
            $this->db_user_password = $CLA_GET['dupwd'];
            $this->dbg_echo ("SQL db user password : Specified, but not displayed for security\n", TRUE);
        } else {
            echo "No SQL db user password specified.\n";
            $this->required_missing = TRUE;
        }

        if (array_key_exists ( 'dname', $CLA_GET )) {
            $this->db_name = $CLA_GET['dname'];
            $this->dbg_echo ("SQL db name : " . $this->db_name . "\n", TRUE);
        } else {
            echo "No SQL db name specified.\n";
            $this->required_missing = TRUE;
        }

        if (array_key_exists ( 'tname', $CLA_GET )) {
            $this->db_table_name = $CLA_GET['tname'];
            $this->dbg_echo ("SQL table name : " . $this->db_table_name . "\n", TRUE);
        } else {
            echo "No SQL table name specified.\n";
            $this->required_missing = TRUE;
        }

        if (array_key_exists ( 'hname', $CLA_GET )) {
            $this->log_file_host_name = $CLA_GET['hname'];
            $this->dbg_echo ("Host name, (domain or IP addr) : " . $this->log_file_host_name . "\n", TRUE);
        } else {
            echo "No Host name, (domain or IP addr), specified.\n";
            $this->required_missing = TRUE;
        }
        
        if ($this->required_missing) {
            $this->dbg_echo ("\nRequired arguments not specified\n");
            $this->display_usage ();
        }
        else { // all required has been specified, display options
            // user may want max num of IP log file lines to read and insert into table.
            // Typically set to a low value during debug, very high in production
            if (array_key_exists ( 'maxl', $CLA_GET )) {
                $this->max_file_lines = $CLA_GET['maxl'];
                $this->dbg_echo ("Maximum number of lines specified : " . $this->max_file_lines . "\n", TRUE);
            }
            else {
                $this->max_file_lines = 1.0e9; // attempt some very large number of lines before EOF
                $this->dbg_echo ("\nMaximum number of lines not specified, reading to EOF\n", TRUE);
            }

            // user may want to break on parse fail
            if (array_key_exists ( 'pbrk', $CLA_GET )) {
                $this->parse_fail_break = $CLA_GET['pbrk'];
                $this->dbg_echo ("\nIP log file parse-fail-break set to break.\n", TRUE);
            }
    
            // user may want to break on insertion into SQL table fail
            if (array_key_exists ( 'ibrk', $CLA_GET )) {
                $this->insert_fail_break = $CLA_GET['ibrk'];
                $this->dbg_echo ("SQL record insertion-fail-break set to break.\n", TRUE);
            }
    
            // user may want max verbosity
            if (array_key_exists ( 'maxverb', $CLA_GET )) {
                $this->full_trace_output = $CLA_GET['maxverb'];
                $this->dbg_echo ("Output tracing set to max verbosity.\n", TRUE);
            }
        }
    }

    public function display_usage () {
        echo "\nUsage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername\n"
            . "        dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname\n"
            . "        [maxl=number] [pbrk=ON] [ibrk=ON]\n"
            . "\nWhere:  fname=   IP log file name, (required)\n"
            . "       dhname=   SQL db server, (host), name, (required)\n"
            . "       duname=   SQL db user name, (required)\n"
            . "        dupwd=   SQL db user password, (required)\n"
            . "        dname=   SQL db name, (required)\n"
            . "        tname=   SQL db table, (required)\n"
            . "        hname=   Host domain name or IP address, (required)\n"
            . "      maxl=int   Maximum number of lines to\n"
            . "                 read from IP log file, (optional)\n"
            . "       pbrk=ON   Causes  exit if a parse error\n"
            . "                 is encountered, (optional)\n"
            . "       ibrk=ON   Causes exit if an insertion error\n"
            . "                 is encountered, (optional)\n"
            . "    maxverb=ON   Enables all tracing echo\n";
    }
    
    public function is_required_missing () {
        return $this->required_missing;
    }

    public function get_iplogfile_name () {
        
        return $this->ip_log_filename;
    }
    
    public function get_db_host_name () {
        
        return $this->db_host_name;
    }
    
    public function get_db_user_name () {
        
        return $this->db_user_name;
    }
    
    public function get_db_user_password () {
        
        return $this->db_user_password;
    }
    
    public function get_db_name () {
        
        return $this->db_name;
    }
    
    public function get_db_table_name () {
        
        return $this->db_table_name;
    }
    
    public function get_log_file_host_name () {
        
        return $this->log_file_host_name;
    }
    
    public function get_max_file_lines () {
        
        return $this->max_file_lines;
    }
    
    public function get_parse_fail_break () {
        
        return $this->parse_fail_break;
    }
    
    public function get_insert_fail_break () {
        
        return $this->insert_fail_break;
    }
    
    public function get_full_trace_output () {
        
        return $this->full_trace_output;
    }
    
    private function dbg_echo ($string, $do_echo = FALSE) {
        if ($do_echo)
            echo $string;
    }
}

?>