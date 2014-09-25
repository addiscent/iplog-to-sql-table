iplog-to-sql-table
==================

Description:

    A command line PHP script which reads an IP log file and inserts into an
    SQL database table one record of fields for each line.
        
    If parsing an IP log file line fails due to malformed fields, or if the 
    field does not pass validation, that record will not be inserted into the
    table.  However, further attempts will be made to fetch, parse, and insert
    subsequent lines and records, until EOF.
    
    If the specified table does not already exist in the SQL database, it
    will be created.  If a table already exists, records in the table for
    which IP log file entries already exist will not be inserted.
    
    Usage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername
            dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname
            [insert] [maxl=number] [maxdup=number] [pbrk] [ibrk] [vmode=enum] [ioi]
    
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
                     records is reached. Because duplicate are searched for
                     from most recent in time to oldest, this will reduce
                     the amount of unnecessary searching. Default is
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
    
        php -f ipl-to-db.php
        
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

    The first is "IPid", which is the record index. It is always 0 so the
    query appends records, instead of inserting them at the specific table
    record index given by IPid.

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


Copyright (C) Charles Thomaston - ckthomaston@gmail.com
