/*
    File: readme.txt
    Project:  iplog-to-sql-table
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
                  pbrk   No argument.  Causes exit if a parse or validation error
                         is encountered, (optional)
                  ibrk   No argument.  Causes exit if an insertion error
                         is encountered, (optional)
               maxverb   No argument.  Enables all tracing echo, (optional)
        
        
        IMPORTANT - The use of the "insert" option is REQUIRED if you wish
        records to be inserted into the SQL database.  By default, the "insert"
        option is NOT SET.  This gives the behavior of making the program "safe"
        to use for examination of success/fail rates of IP log parsing and
        validation errors, without commtting records to the SQL database.  If
        this fail-safe was not the default behavior, a user could
        unintentionally append records to the database, thereby contaminating it
        with duplicate records.  Forcing the user to specify the "insert"
        option prevents unintentional record insertion.
        
        The "maxl" and "maxverb" options typically are not used in production,
        they are provided as a convenience for testing and debugging.

    Files:
    
        A complete set of files for this distribution contains all of the following:
        
        - readme.txt
        - ipl-to-db.php, command line script which parses the log and inserts into table
        - command-line-arguments.php, a class required for ipl-to-db.php
        - iplog-file.php, a class required for ipl-to-db.php
        - iplog-database.php, a class required for ipl-to-db.php
        - iplog-example.log, a short example IP log file used for testing. Not required
        - itst-class-diagram.png, docmentation for developers. Not required
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
                            File : "itst-class-diagram.png"
                            File : "LICENSE"
        
        Test "ipl-to-db.php" by executing:
        
            php -f ipl-to-db.php
            
        The result should be a usage display similar to shown above in
        "Description" section.
        
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
