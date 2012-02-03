<?php

/**
 *
 */
class sqlFactory {


    var $dbhost;
    var $dbuser;
    var $dbpass;
    var $dbase;
    var $slqInstance;

    function sqlFactory($type="", $dbase="", $host="", $user="", $pass="") {
    		global $PIVOTX, $pivotx_path;
    
        $this->type = $type;
        $this->dbhost = $host;
        $this->dbuser = $user;
        $this->dbpass = $pass;
        $this->dbase =$dbase;

        switch($this->type) {
        
            case "mysql":
                 /**
                 * Set up a link for MySQL model
                 */
                include_once( realpath($pivotx_path). '/modules/module_mysql.php');
                $this->slqInstance = new mysql($dbase, $host, $user, $pass);
                break;

            case "sqlite":
                 /**
                 * Set up a link for SQLite model
                 */
								include_once( realpath($pivotx_path). '/modules/module_sqlite.php');
                $this->slqInstance = new sqlite($dbase, $host, $user, $pass);
                break;

            case "postgresql":

                /**
                 * Set up a link for PostgreSQL model
                 */

                // .. TODO

                return false;
                break;

            default:

                $this->error("Unknown Database Model!");
                break;


        }
    }

		function getSqlInstance() {
			return $this->slqInstance;
		}
    
    /**
     * If an error has occured, we print a message. If 'halt_on_sql_error' is
     * set, we die(), else we continue.
     *
     * @param string $error_msg
     *
     */
    function error( $error_msg="")  {
        global $cfg;

        $error_date = date("F j, Y, g:i a");

        $error_page = "<div style='border: 1px solid #AAA; padding: 4px; background-color: #EEE; font-family: Consolas, Courier, \"Courier New\", monospace; font-size: 80%;'><strong>SQLFactory Error</strong>".
            "\n\nThere appears to be an error while trying to complete your request.\n\n".
            "<strong>Query: </strong>      ".htmlentities($sql_query)."\n".
            "<strong>SQL error:</strong> ".htmlentities($error_msg)."\n".
            "<strong>Error code:</strong>  {$error_no}\n".
            "<strong>Date:</strong>        {$error_date}\n</div>\n";

        echo(nl2br($error_page));

        if (function_exists('debug_printbacktrace')) {
            // call debug_printbacktrace if it's available..
            debug_printbacktrace();
        }

        if ($this->halt_on_sql_error == true ) {
            die();
        }

    }


}


?>
