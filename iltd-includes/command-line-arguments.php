<?php
/*
    File: command-line-arguments.php
    Product:  iplog-to-sql-table
    Rev 2014.0916.2030
    Copyright (C) 2014 Charles Thomaston - ckthomaston@gmail.com
   
    Description:

        The CommandLineArguments class collects arguments from the
        command line and provides methods for acessing the arguments,
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

// These definitions along with the class methods define the
// CommandLineArguments Class Interface
define ( "CLA_VERBOSITY_MODE_ALL", 100 );
define ( "CLA_VERBOSITY_MODE_GENERAL", 70 );
define ( "CLA_VERBOSITY_MODE_LOG", 40 );
define ( "CLA_VERBOSITY_MODE_SILENT", 0 );


class CommandLineArguments {
    
    private $required_present = array
                                    (
                                        "fname" => NULL,
                                        "dhname" => NULL,
                                        "duname" => NULL,
                                        "dname" => NULL,
                                        "tname" => NULL,
                                        "hname" => NULL
                                    );

    private $cla_verbosity_mode = "gen";
    
    
    public function CommandLineArguments () {
        
        // we must know cla_verbosity_mode first
        if (array_key_exists ( 'vmode', $_GET ))
            $this->cla_verbosity_mode = $_GET [ 'vmode' ];
            
        switch ($this->cla_verbosity_mode) {
            
            case "all" :
                
                $this->cla_verbosity_mode = CLA_VERBOSITY_MODE_ALL;
                break;
            
            case "gen" :
                
                $this->cla_verbosity_mode = CLA_VERBOSITY_MODE_GENERAL;
                break;
            
            case "log" :
                
                $this->cla_verbosity_mode = CLA_VERBOSITY_MODE_LOG;
                break;
    
            case "silent" :
                
                $this->cla_verbosity_mode = CLA_VERBOSITY_MODE_SILENT;
                break;
            
            default :
                $this->cla_verbosity_mode = CLA_VERBOSITY_MODE_GENERAL;
        }
        
        // initialize $required_missing
        if ( array_key_exists ( 'fname', $_GET ) )
            $this->required_present [ 'fname' ] = $_GET [ 'fname' ];

        if ( array_key_exists ( 'dhname', $_GET ) )
            $this->required_present [ 'dhname' ] = $_GET [ 'dhname' ];

        if ( array_key_exists ( 'duname', $_GET ) )
            $this->required_present [ 'duname' ] = $_GET [ 'duname' ];

        if ( array_key_exists ( 'dname', $_GET ) )
            $this->required_present [ 'dname' ] = $_GET [ 'dname' ];

        if ( array_key_exists ( 'tname', $_GET ) )
            $this->required_present [ 'tname' ] = $_GET [ 'tname' ];

        if ( array_key_exists ( 'hname', $_GET ) )
            $this->required_present [ 'hname' ] = $_GET [ 'hname' ];
}
    
    private function verbosity_echo ( $string, $verbosity_mode ) {
        
        if ( $this->cla_verbosity_mode >= $verbosity_mode )
            echo $string;
    }
    
    public function is_required_missing ( &$return_msg ) {
        
       foreach ( $this->required_present as $arg ) {
            
            if ( !$arg ) {
                
                $return_msg = "required_missing : Required arguments are missing from command line.\n";
                return TRUE;
            }
        }
        
        $return_msg = "required_missing : NO required arguments are missing from command line.\n";
            
        return FALSE;
    }

    public function get_iplogfile_name ( &$return_msg ) {
        
        if ( !$this->required_present [ 'fname' ] )
            $return_msg = "fname : No IP log file name specified.\n";
        else
            $return_msg = "fname : " . $this->required_present [ 'fname' ] . "\n";

        return $this->required_present [ 'fname' ];
    }
    
    public function get_db_host_name ( &$return_msg ) {
        
        if ( !$this->required_present [ 'dhname' ] )
            $return_msg = "dhname : No SQL db host name specified.\n";
        else
            $return_msg = "dhname : " . $this->required_present [ 'dhname' ] . "\n";

        return $this->required_present [ 'dhname' ];
    }
    
    public function get_db_user_name ( &$return_msg ) {
        
        if ( !$this->required_present [ 'duname' ] )
            $return_msg = "duname : No SQL db host name specified.\n";
        else
            $return_msg = "duname : " . $this->required_present [ 'duname' ] . "\n";

        return $this->required_present [ 'duname' ];
    }
    
    public function get_db_user_password ( &$return_msg ) {
        
        if ( array_key_exists ( 'dupwd', $_GET ) ) {
        
            $db_user_password = $_GET [ 'dupwd' ];

            $return_msg = "dupwd : Password specified but not shown for security\n";
            
        } else
            $return_msg = "dupwd : No db user password specified\n";

        return $db_user_password;
    }
    
    public function get_db_name ( &$return_msg ) {
        
        if ( !$this->required_present [ 'dname' ] )
            $return_msg = "dname : No SQL db host name specified.\n";
        else
            $return_msg = "dname : " . $this->required_present [ 'dname' ] . "\n";

        return $this->required_present [ 'dname' ];
    }
    
    public function get_db_table_name ( &$return_msg ) {
        
        if ( !$this->required_present [ 'tname' ] )
            $return_msg = "tname : No SQL db host name specified.\n";
        else
            $return_msg = "tname : " . $this->required_present [ 'tname' ] . "\n";

        return $this->required_present [ 'tname' ];
    }
    
    public function get_log_file_host_name ( &$return_msg ) {
        
        if ( !$this->required_present [ 'hname' ] )
            $return_msg = "hname : No SQL db host name specified.\n";
        else
            $return_msg = "hname : " . $this->required_present [ 'hname' ] . "\n";

        return $this->required_present [ 'hname' ];
    }
    
    public function get_insert_option ( &$return_msg ) {
        
        $insert_option = FALSE;
        
        if ( array_key_exists ( 'insert', $_GET ) ) {
            
            $insert_option = TRUE;
            $return_msg = "insert : SQL insert-record-option set\n";
            
        } else
            $return_msg = "insert : NOTICE - SQL insert-record-option NOT set.\n";

        return $insert_option;
    }
    
    public function get_max_file_lines ( &$return_msg ) {
        
        if ( array_key_exists ( 'maxl', $_GET ) ) {
            
            $max_file_lines = $_GET [ 'maxl' ];
            
            if ( ( $max_file_lines < 0 ) || !is_numeric ( $max_file_lines ) ) {
                
                $return_msg = "maxl : '$max_file_lines' is invalid, setting maximum nuber of lines to 0\n";
                
                $max_file_lines = 0;
            } else {
                
                $return_msg = "maxl : $max_file_lines\n";
            }
        } else {
            
            $max_file_lines = 1.0e9; // will hit EOF before reaching this number
            $return_msg = "maxl : EOF\n";
        }

        return $max_file_lines;
    }
    
    public function get_parse_fail_break ( &$return_msg ) {
        
        $parse_fail_break = FALSE;
        
        if ( array_key_exists ( 'pbrk', $_GET ) ) {
            
            $parse_fail_break = TRUE;
            $return_msg = "pbrk : IP log file parse-validate-fail-break set to break.\n";
            
        } else
            $return_msg = "pbrk : IP log file parse-validate-fail-break set to not break.\n";

        return $parse_fail_break;
    }
    
    public function get_insert_fail_break ( &$return_msg ) {
        
        $insert_fail_break = TRUE;
        
        if ( array_key_exists ( 'ibrk', $_GET ) ) {
            
            $insert_fail_break = TRUE;
            $return_msg = "ibrk : SQL record insertion-fail-break set to break.\n";
            
        } else
            $return_msg = "ibrk : SQL record insertion-fail-break set to not break.\n";

        return $insert_fail_break;
    }
    
    public function get_verbosity_mode ( &$return_msg ) {
        
        switch ($this->cla_verbosity_mode) {
            
            case CLA_VERBOSITY_MODE_ALL :
                
                $return_msg = "vmode : all\n";
                break;
            
            case CLA_VERBOSITY_MODE_GENERAL :
                
                $return_msg = "vmode : gen\n";
                break;
            
            case CLA_VERBOSITY_MODE_LOG :
                
                $return_msg = "vmode : log\n";
                break;
    
            case CLA_VERBOSITY_MODE_SILENT :
                
                $return_msg = "vmode : silent\n";
                break;
            
            default :
                
                $return_msg = "vmode : Default display tracing set to 'gen'.\n";
        }
        
        return $this->cla_verbosity_mode;
    }
    
    public function get_items_of_interest ( &$return_msg ) {
        
        $items_of_interest = FALSE;
        
        if ( array_key_exists ( 'ioi', $_GET ) ) {
            
            $items_of_interest = TRUE;
            $return_msg = "ioi : Item Of Interest set\n";
            
        } else
            $return_msg = "ioi : Item Of Interest not set\n";

        return $items_of_interest;
    }
    
    public function display_usage ()
    {
        
echo "
Usage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername
        dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname
        [insert] [maxl=number] [pbrk] [ibrk] [vmode=enum]

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
                 
          ioi    Optional. No argument. Forces display of an 'Item of
                 Interest'. This is a data or event item which will be
                 displayed regardless of the 'vmode' setting, (except
                 for 'silent', nothing is displayed during 'silent'
                 vmode). At this time, there is only one IOI, which is
                 the 'MethodURI field malformed' error annunciation.
";

    }
}

?>