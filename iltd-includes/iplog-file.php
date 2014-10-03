<?php
/*
    File: iplog-file.php
    Product:  iplog-to-sql-table
    Rev 2014.1002.2200
    Copyright (C) 2014 Charles Thomaston - ckthomaston@dalorweb.com
   
    Description:
    
        The IPlogFile class implements file access and data extraction processes.
    
    Developer's notes:
    
        In addition to opening and closing the IP log file, this class reads
        IP log file entries, parses fields from them, and creates a record
        containing those fields, ($ip_evnt_flds), which is returned to the caller.
     
        Seven fields are parsed from each line in the IP log file:
    
            - IPaddress
            - LogDateTime
            - MethodURI
            - Status
            - PageSize
            - Referer
            - Agent
    
        The parser method traverses a passed string, parses fields from a
        single line, and stores each set of fields (IP log data) into an array.
        The array is returned to caller.
      
        Assumes IP logfile format is compatible with Combined Log Format, as
        used by Apache HTTP Server. See "Access Logs":
        
            http://httpd.apache.org/docs/2.2/logs.html
        
        The code herein was tested on CentOS 6.
      
        Typical log file line fields are:
        
            IPAddr, -, -, time, MethodURI, Status, PageSize(returned by host), Referer, Agent
     
        The -, (hyphen), fields are fields for which data was not available at
        the time of the log entry. The first two hyphens have not been seen to
        change to other values while testing logs of various vintages.
        However, they could make a surprise reappearance some day, which will
        break the parser code, so mantenance developers or source code re-users
        beware.
     
        IP log line format; assumes no leading white space or other cruft to remove
        at beginning of line:
     
        66.249.74.134 - - [15/Jul/2014:05:44:40 -0700] "GET /robots.txt HTTP/1.1" 200 60 "http://referer.example.com/" "+http://www.google.com/bot.html"
        187.120.45.180 - - [15/Jul/2014:08:31:33 -0700] "GET / HTTP/1.1" 403 - "http://referer.example.com/2.php?u=http://example.com" "Mozilla/5.0"
        130.211.176.181 - - [15/Jul/2014:08:36:36 -0700] "GET / HTTP/1.0" 301 - "-" "NerdyBot"
        130.211.176.181 - - [15/Jul/2014:08:36:38 -0700] "GET / HTTP/1.0" 200 51883 "-" "NerdyBot"
     
        The IP log entry may occasionally have a hyphen as the MethodURI field.
        This is allowed as a valid field for insertion into the database.
        
        Dependeing on the access Method, the Referer and Agent fields may
        appear in the log file as a hyphen. The PageSize also appears as a
        "-" in some records, (without quotes), if there is no object returned.
        Also, note that this code converts the PageSize field on the fly from
        hyphen to -1 if necessary, becaues PageSize must be an INT in the SQl
        db table.
     
        Some validation and adjustment is done by this function, for a few
        fields. It is up to caller to determine whether to ignore detectable
        errors, indicated by return value NULL.

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

define ( "IPLF_ERR_UNABLE_TO_OPEN_FILE", 101 );
define ( "IPLF_EOF_IPLOG", 102 );
define ( "IPLF_ERR_RECORD_LINE_NULL", 103 );

// if a parsed PageSize int field is "-", change it to -1
define ("PAGESIZE_ADJUST_VALUE", -1);


class IPlogFile
{
        
private $http_methods = array
                    (   // http://www.iana.org/assignments/http-methods/http-methods.xhtml
                        // The most frequently used methods are sorted first in
                        // this list so search time is minimized
                        "HEAD",
                        "GET",
                        "POST",
                        "PUT",
                        "DELETE",
                        "TRACE",
                        "OPTIONS",
                        "CONNECT",
                        // less frequently used are here below, in 
                        // alphabetical order
                        "ACL",
                        "BASELINE-CONTROL",
                        "BIND",
                        "CHECKIN",
                        "CHECKOUT",
                        "COPY",
                        "LABEL",
                        "LINK",
                        "LOCK",
                        "MERGE",
                        "MKACTIVITY",
                        "MKCALENDAR",
                        "MKCOL",
                        "MKREDIRECTREF",
                        "MKWORKSPACE",
                        "MOVE",
                        "ORDERPATCH",
                        "PATCH",
                        "PROPFIND",
                        "PROPPATCH",
                        "REBIND",
                        "REPORT",
                        "SEARCH",
                        "UNBIND",
                        "UNCHECKOUT",
                        "UNLINK",
                        "UNLOCK",
                        "UPDATE",
                        "UPDATEREDIRECTREF",
                        "VERSION-CONTROL"
                    );

    private $iplf_verbosity_mode = CLA_VERBOSITY_MODE_GENERAL;
    
    private $ip_log_filename = NULL;
    private $ip_log_filehandle = NULL;

    public function IPlogFile ( $ip_log_filename = NULL, $verbosity_mode = CLA_VERBOSITY_MODE_GENERAL ) {
        
        $this->ip_log_filename = $ip_log_filename;
        $this->iplf_verbosity_mode = $verbosity_mode;
    }
    
    public function __destruct () {
        
        if ($this->ip_log_filehandle) {
            fclose ($this->ip_log_filehandle);
        }
    }
    
    private function verbosity_echo ( $string, $verbosity_mode ) {
        
        if ( $this->iplf_verbosity_mode >= $verbosity_mode )
            echo $string;
    }
    
    public function get_iplog_record ( $ip_record_id, &$ip_record_line, $item_of_interest, &$return_msg ) {
        
        // open the IP log file if this is the first get_iplog_record()
        if ($this->ip_log_filename && !$this->ip_log_filehandle) {
            
            // open IP address log file
            $this->ip_log_filehandle = fopen ($this->ip_log_filename, "r");
            
            if (!$this->ip_log_filehandle) {
                
                $msg = "\nget_iplog_record - Unable to open IP log file : $this->ip_log_filename\n";
                
                verbosity_echo ( $msg, CLA_VERBOSITY_MODE_LOG );
                    
                return IPLF_ERR_UNABLE_TO_OPEN_FILE;
            }
            $msg = "get_iplog_record - Opened IP log file : $this->ip_log_filename\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
        }
        
        $ip_record_line = fgets ( $this->ip_log_filehandle );
        
        if ( feof ( $this->ip_log_filehandle ) ) {
            
            $msg = "\nget_iplog_record - IP log file returned EOF : Done parsing records.\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_GENERAL );
                
            return IPLF_EOF_IPLOG; // no record to return
        }
    
        // parse, (and validate one), fields from IP record line
        $ip_evnt_flds = $this->parse_lfln_array ( $ip_record_id, $ip_record_line, $item_of_interest, $return_msg );

        verbosity_echo ( $return_msg, CLA_VERBOSITY_MODE_ALL );
                
        return $ip_evnt_flds;
    }
    
    private function is_http_method ( $method = NULL ) {
        
        foreach ($this->http_methods as $a_method) {
            
            if ($method == $a_method)
                return TRUE;
        }
        return FALSE;
    }
    
    private function ltrim_to_data ( $in_string = NULL, $strstr_arg = NULL ) {
        
        $str_remain = strstr ( $in_string, $strstr_arg );
        $str_remain = ltrim ( $str_remain, $strstr_arg );
        
        return $str_remain;
    }
    
    // Return an array containing IP log file line fields.
    // Returns NULL if function cannot correctly populate an array to return.
    private function parse_lfln_array ( $ip_record_id, $ip_record_line, $item_of_interest, &$return_msg = NULL ) {
        
        //   Parsed fields from IP log file
        $ip_evnt_flds = array
                            (
                                "IPaddress" => "",
                                "LogDateTime" => "",
                                "MethodURI" => "",
                                "Status" => 0,
                                "PageSize" => 0,
                                "Referer" => "",
                                "Agent" => ""
                            );
        
        //  IP log line format example. Assumes no left edge white space or other
        //  cruft to remove at beginning of line:
        //  23.239.7.135 - - [15/Jul/2014:05:07:18 -0700] "GET /robots.txt HTTP/1.1" 408 51948 "referrer" "agent"
    
         // the code below walks through $string_remainder during parsing
        $string_remainder = $ip_record_line;
        
        $msg = "\nparse_lfln_array - IP log line number : $ip_record_id\n";
        
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
        // get the IP address string, by retrieving the string up to the first
        // blank space, (exclusive). If malformed, or invalid IP address, abort
        $ip_evnt_flds [ "IPaddress" ] = strstr ( $string_remainder, " ", TRUE );
        
        $long = ip2long ( $ip_evnt_flds [ "IPaddress" ] );
        
        if ( ( $long == -1 ) || ( $long === FALSE ) ) {

            $msg = "parse_lfln_array : IPaddress field is invalid, parsing aborted for this record\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
            $return_msg = NULL;
                        
            return NULL;
        }
        
        $msg = "parse_lfln_array - IPaddress : " . $ip_evnt_flds [ 'IPaddress' ] . "\n";
    
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
            
        // remove the IP addr and undesired space and dash chars from the beginning
        // of the remainder string
        $string_remainder = $this->ltrim_to_data ( $string_remainder, " - - [" );
    
        // get the date-time stamp string. If malformed, abort
        $ip_evnt_flds [ "LogDateTime" ] = strstr ( $string_remainder, "]", TRUE );
        
        if ( $ip_evnt_flds [ "LogDateTime" ] == FALSE ) {
            
            $msg = "parse_lfln_array : LogDateTime field is malformed, parsing aborted for this record\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
            $return_msg = NULL;
                        
            return NULL;
        }
        
        $msg = "parse_lfln_array - LogDateTime : " .$ip_evnt_flds [ 'LogDateTime' ] . "\n";
        
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
        // remove the date-time stamp and undesired space, dash, and double-quote
        // chars from the beginning of the remainder string
        $string_remainder = $this->ltrim_to_data ( $string_remainder, '] "' );
    
        // get the request method string, which includes URI argument to method and
        // HTTP version info. If not a valid HTTP Method, or a "-", abort
        $ip_evnt_flds [ "MethodURI" ] = strstr ( $string_remainder, '"', TRUE );
        
        if ( $ip_evnt_flds [ "MethodURI" ] != "-" ) {
            
            $method = strstr ( $ip_evnt_flds [ "MethodURI" ], " ", TRUE );
            
            switch ( $method ) {
                
                case FALSE :
                    
                    $msg = "parse_lfln_array : IP log file line $ip_record_id MethodURI field is malformed, parsing aborted for this record";
                    
                    if ( $this->iplf_verbosity_mode == CLA_VERBOSITY_MODE_ALL )
                        echo $msg;
                    else
                        if ( $item_of_interest && ( $this->iplf_verbosity_mode > CLA_VERBOSITY_MODE_SILENT ) )
                            echo "\n". $msg;
                        else
                            verbosity_echo ( "\n". $msg, CLA_VERBOSITY_MODE_GENERAL );
                        
                    $return_msg = NULL;
                        
                    return NULL;

                case !$this->is_http_method ( $method ) :
                
                    $methodURI = $ip_evnt_flds [ "MethodURI" ];
                    
                    $msg = "parse_lfln_array - Invalid Method : '$methodURI' - parsing aborted for this record\n";
                    
                    verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
                    $return_msg = NULL;
                        
                    return NULL;
                    
                default :
            }
        }
        
        $msg = "parse_lfln_array - MethodURI : " .$ip_evnt_flds [ 'MethodURI' ] . "\n";
        
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
        // remove the request, which includes HTTP version info and undesired
        // double-quote char and space from the beginning of the remainder string
        $string_remainder = $this->ltrim_to_data ( $string_remainder, '" ' );
    
        // get the server status reply, (converts to INT).
        // If malformed, abort
        $ip_evnt_flds [ "Status" ] = strstr ( $string_remainder, " ", TRUE );
        
        if ( !is_numeric ( $ip_evnt_flds ["Status" ] ) ) {
            
            $msg = "parse_lfln_array : Status field is invalid, parsing aborted for this record\n";
        
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                    
            $return_msg = NULL;
                        
            return NULL;
        }
        
        $msg = "parse_lfln_array - Status : " .$ip_evnt_flds [ 'Status' ] . "\n";
    
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
        // remove the server status and undesired space char from the
        // beginning of the remainder string
        $string_remainder = $this->ltrim_to_data ( $string_remainder, " " );
    
        // get the size of the object returned to the agent, (converts to INT).
        // If size of object is "-", convert to INT of value 0.
        // If malformed, abort
        $ip_evnt_flds [ "PageSize" ] = strstr ( $string_remainder, " ", TRUE );
        
        if ( $ip_evnt_flds ["PageSize" ] == "-" ) {
            
            $msg = "parse_lfln_array - PageSize : hyphen (-), forcing PageSize value to : "
                            . PAGESIZE_ADJUST_VALUE . "\n";
        
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
            $ip_evnt_flds [ "PageSize" ] = PAGESIZE_ADJUST_VALUE;
        }
        else {
            
            if ( !is_numeric ( $ip_evnt_flds [ "PageSize" ] ) ) {
                
                $msg = "parse_lfln_array - PageSize : PageSize field is invalid, parsing aborted for this record\n";
            
                verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
                
                $return_msg = NULL;
                        
                return NULL;
            }
        }
    
        $msg = "parse_lfln_array - PageSize : " .$ip_evnt_flds [ 'PageSize' ] . "\n";
        
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );

        // remove the size of the object and undesired space and double-quote chars
        // from the beginning of the remainder string
        $string_remainder = $this->ltrim_to_data ( $string_remainder, ' "' );
    
        // get the Referer string.  If malformed, abort
        $ip_evnt_flds [ 'Referer' ] = strstr ( $string_remainder, '"', TRUE );
        
        if ($ip_evnt_flds[ 'Referer' ] == FALSE) {
            
            $msg = "parse_lfln_array - Referer : Referer field is malformed, parsing aborted for this record\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );

            $return_msg = NULL;
                        
            return NULL;
        }
        
        $msg = "parse_lfln_array - Referer : " .$ip_evnt_flds [ 'Referer' ] . "\n";
        
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );

        // remove the Referer string and undesired space and double-quote chars
        // from the beginning of the remainder string
        $string_remainder = $this->ltrim_to_data ( $string_remainder, ' "' );
    
        // get the agent string. If malformed, abort
        $ip_evnt_flds [ "Agent" ] = strstr ( $string_remainder, '"', TRUE );
        
        if ( $ip_evnt_flds [ "Agent" ] == FALSE ) {
            
            $msg = "parse_lfln_array - Agent : Agent field is malformed, parsing aborted for this record\n";
            
            verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
    
            $return_msg = NULL;
                        
            return NULL;
        }
        
        $msg = "parse_lfln_array - Agent : " .$ip_evnt_flds [ 'Agent' ] . "\n";
        
        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
        
        $msg = "parse_lfln_array : Completed with no errors\n";

        verbosity_echo ( $msg, CLA_VERBOSITY_MODE_ALL );
        
        $return_msg = NULL;
                        
        return $ip_evnt_flds;
    }
}

?>