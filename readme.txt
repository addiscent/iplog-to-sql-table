/*
    Project:  iplog-to-sql-table
    Rev 2014.0905.2200
    by ckthomaston@gmail.com
   
    Description:
    
        Reads an IP log file, parses fields from each line, and inserts one
        record of fields for each line into an SQL database table.
    
        If parsing an IP log file line fails due to malformed fields, or if the 
        field does not pass validation, that record will not be inserted into the
        table.  However, further attempts will be made to fetch, parse, and insert
        subsequent lines and records, until EOF.
    
        Usage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername\n"
                dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname\n"
                [maxl=number] [pbrk=ON] [ibrk=ON]\n"
                
        Where:  fname=   IP log file name, (required)
               dhname=   SQL db server, (host), name, (required)
               duname=   SQL db user name, (required)
                dupwd=   SQL db user password, (required)
                dname=   SQL db name, (required)
                tname=   SQL db table, (required)
                hname=   Host domain name or IP address, (required)
                 maxl=   Maximum number of lines to
                         read from IP log file, (optional)
               pbrk=ON   Causes  exit if a parse error
                         is encountered, (optional)
               ibrk=ON   Causes exit if an insertion error
                         is encountered, (optional)
            maxverb=ON   Enables all tracing echo


        The "maxl" option is typically not useful for production, it is provided
        as a convenience for testing and debugging.
    
    Files:
    
        A complete set of files for this distribution contains all of the following:
        
        - readme.txt
        - ipl-to-db.php, command line script which parses the log and inserts into table
        - command-line-arguments.php, a class required for ipl-to-db.php
        - iplog-file.php, a class required for ipl-to-db.php
        - iplog-database.php, a class required for ipl-to-db.php
        - iplog-example.txt, a short example IP log file used for testing. Not required

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
