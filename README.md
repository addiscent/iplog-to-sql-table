iplog-to-sql-table
==================

Reads an IP log, (HTTP access), file and inserts one record of fields for each line into an SQL database table.

Description:

    Reads an IP log file, parses fields from each line, and inserts one
    record of fields for each line into an SQL database table.

    If parsing an IP log file line fails due to malformed fields, or if the 
    field does not pass validation, that record will not be inserted into the
    table.  However, further attempts will be made to fetch, parse, and insert
    subsequent lines and records, until EOF.

    Usage:  ipl-to-db fname=logfilename dhname=dbhostname duname=dbusername
            dupwd=dbuserpasswd dname=dbname tname=tblname hname=hostname
            [maxl=number] [pbrk=ON] [ibrk=ON] [maxverb=ON]
            
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
        maxverb=ON   Enables all tracing echo, (optional)


    The "maxl" and "maxverb" options typically are not used in production,
    they are provided as a convenience for testing and debugging.

Files:

    A complete set of files for this distribution contains all of the following:
    
    - readme.txt
    - ipl-to-db.php, command line script which parses the log and inserts into table
    - command-line-arguments.php, a class required for ipl-to-db.php
    - iplog-file.php, a class required for ipl-to-db.php
    - iplog-database.php, a class required for ipl-to-db.php
    - iplog-example.txt, a short example IP log file used for testing. Not required

Installation:

    After downloading the "iplog-to-sql-table-X.X.X" zip or tar.gz release
    file, unzip the contents into your "bin" directory, or any directory
    in your executables PATH.  Ensure that an "iltd-includes" subdirectory
    was created in your "bin", (PATH), directory. It must contain all of
    the PHP files listed above, except "ipl-to-db.php".  "ipl-to-db.php"
    should be one level up, in your "bin" directory.  Execute as with any
    PHP command line program:
    
        php -f (...)/bin/ipl-to-db.php fname=logfilename dhname=dbhostname ... etc
        
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
