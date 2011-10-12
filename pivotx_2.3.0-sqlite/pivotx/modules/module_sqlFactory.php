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
    
}


?>
