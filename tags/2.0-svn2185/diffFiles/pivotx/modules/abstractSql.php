<?php

/**
 * Some settings for the sql class:
 */
define('HALT_ON_SQL_ERROR', false);
define('SILENT_AFTER_FAILED_CONNECT', true);
define('SQL_ERROR_HANDLER', 'setError');

$return_silent = false;

if (!isset($query_count)) { $query_count = 0; }
if (!isset($query_log)) { $query_log = array(); }

/**
 * Class AbstractSQL: a simple DB class.
 *
 * @version 0.1
 * @author Jean Paul Piccato, j2pguard-spam@yahoo.com
 * @copyright GPL, version 2
 *
 */
abstract class abstractSql {

    var $dbhost;
    var $dbuser;
    var $dbpass;
    var $dbase;

    var $sql_query;
    var $sql_link;
    var $sql_result;
    var $last_query;
    var $last_num_results;
    var $num_affected_rows;
    var $halt_on_sql_error;
    var $silent_after_failed_connect;
    var $return_silent;
    var $error_handler;

    function sql($dbase="", $host="", $user="", $pass="") {
        global $cfg;
	
        $this->dbhost = $host;
        $this->dbuser = $user;
        $this->dbpass = $pass;
        $this->dbase = $dbase;

        $this->sql_link = 0;
        $this->sql_result = '';
        $this->last_query = '';
        $this->last_num_results = 0;
        $this->num_affected_rows = 0;
        $this->halt_on_sql_error = HALT_ON_SQL_ERROR;
        $this->silent_after_failed_connect = SILENT_AFTER_FAILED_CONNECT;
        $this->error_handler = SQL_ERROR_HANDLER;
        
        $this->allow_functions = false;
    }



    /**
     * Set up the Database connection, depending on the selected DB model.
     */
    abstract function connection();


    /**
     * Close sql link
     */
    abstract function close();


    /**
     * Gets the current MySQL version
     *
     * @return string
     */
    abstract function get_server_info();

    /**
     * Gets the current MySQL version
     *
     * @return string
     */
    abstract function get_internal_error();
    
    /**
     * If an error has occured, we print a message. If 'halt_on_sql_error' is
     * set, we die(), else we continue.
     *
     * @param string $error_msg
     * @param string $sql_query
     * @param string $error_no
     *
     */
    function error( $error_msg="", $sql_query, $error_no )  {
        global $cfg;

        // if no error message was given, use the internal db error:
        if ( ($error_msg == "") && ($error_no != 0) ) {
            $error_msg = $this->get_internal_error();
        }

        /**
         * If we have a defined error_handler, we call that, else we'll print our own.
         */
        if (($this->error_handler!="") && (function_exists($this->error_handler))) {

            $handler = $this->error_handler;

            $handler('sql', $error_msg, $sql_query, $error_no);

        } else {

            $error_date = date("F j, Y, g:i a");

            $error_page = "<div style='border: 1px solid #AAA; padding: 4px; background-color: #EEE; font-family: Consolas, Courier, \"Courier New\", monospace; font-size: 80%;'><strong>mySQL Error</strong>".
            "\n\nThere appears to be an error while trying to complete your request.\n\n".
            "<strong>Query: </strong>      ".htmlentities($sql_query)."\n".
            "<strong>SQL error:</strong> ".htmlentities($error_msg)."\n".
            "<strong>Error code:</strong>  {$error_no}\n".
            "<strong>Date:</strong>        {$error_date}\n</div>\n";

            echo(nl2br($error_page));

        }

        if (function_exists('debug_printbacktrace')) {
            // call debug_printbacktrace if it's available..
            debug_printbacktrace();
        }

        if ($this->halt_on_sql_error == true ) {
            die();
        }

    }

		abstract function sql_affected_rows();

		abstract function sql_doquery($query, $link_identifier);
		
		abstract function sql_errno($link_identifier);

		abstract function getTableList($filter="");

    /**
     * Performs a query. Either pass the query to e executed as a parameter,
     *
     * @param string query
     */
    function query( $query="" ) {
        global $PIVOTX;

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        // perhaps use the cached query
        if ($query=="") {
            $query = $this->cached_query;
        }


        // Set the last_query
        $this->last_query = $query;

				$now = timetaken('int');

        // execute it.
        $this->sql_result = @$this->sql_doquery( $query, $this->sql_link );

        // If we're profiling, we use the following to get an array of all queries.
        // We also debug queries that took relatively long to perform.
        if ($PIVOTX['config']->get('log_queries') && $PIVOTX['config']->get('debug') ) {
            
            $timetaken = round(timetaken('int') - $now, 4);

            if ((timetaken('int') - $now) > 0.4) {
                debug("\nStart: ". $now ." - timetaken: " . $timetaken);
			    			debug(htmlentities($query)."\n\n");
                debug_printbacktrace();
            }

            $query = preg_replace("/\s+/", " ", $query);

            // If debug is enabled, we add a small comment to the query, so that it's
            // easier to track where it came from
            if ( function_exists('debug_backtrace') ) {
                $trace = debug_backtrace();
                $comment = sprintf(" -- %s - %s():%s ", basename($trace[0]['file']), $trace[1]['function'], $trace[0]['line']);
                $query .= $comment;               
            }
            
            //echo "<pre>\n"; print_r($query); echo "</pre>";
            
            $GLOBALS['query_log'][] = $query . " -- $timetaken sec. ";
            
        }

        

        if ($this->sql_result === false) {
            // If an error occured, we output the error.
            $this->error('', $this->last_query, $this->sql_errno($this->sql_link));

            $this->num_affected_rows = 0;

            return false;

        } else {

            // Count the num of results, and raise the total query count.
            $GLOBALS['query_count']++;

            $this->num_affected_rows = $this->affected_rows();

            return true;

        }


    }



    /**
     * Get the last performed or stored query
     *
     * @param  none
     */
    function get_last_query () {

        return $this->last_query;

    }



    /**
     * Get the last inserted id
     *
     * @param  none
     */
    abstract function get_last_id();

    /**
     * Gets the number of selected rows
     */
    abstract function num_rows();


    /**
     * Gets the number of affected rows
     */
    function affected_rows() {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $sql_affected_rows = $this->sql_affected_rows( $this->sql_link );

        return $sql_affected_rows;

    }



   /**
     * Quote variable to make safe to use in a SQL query. If you pass
     * $skipquotes as true, the string will just have added slashes, otherwise it
     * will be wrapped in quotes for convenience
     *
     * @param string $value to quote
     * @param boolean $skipquotes  to skip adding quotes
     * @return string quoted value
     */
    function quote($value, $skipquotes=false) {
        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $value = addslashes( $value );

        if(!$skipquotes) {
            $value = "'" . $value . "'";
        }

        return $value;
    }

    /**
     * Fetch a single row from the last results.
     *
     * @param string $getnames
     * @return array row
     *
     */
    abstract function fetch_row($getnames="with_names");

    /**
     * Fetch all rows from the last results.
     *
     * @param string $getnames
     * @return array rows
     *
     */
    abstract function fetch_all_rows($getnames="with_names");

    /**
     * Returns the number of executed queries
     */
    function query_count() {
        return $GLOBALS['query_count'];
    }


    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * // Simple select query instruction:
     * $qry = array();
     * $qry['select'] = "*";
     * $qry['from'] = "employees";
     * $qry['where'][] = "firstname LIKE 'b%'";
     *
     * // build and execute query..
     * $database->build_select($qry);
     * $database->query();
     *
     * $rows = $database->fetch_all_rows();
     *
     *
     * Example 2:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * // Simple select query instruction:
     * $qry = array();
     * $qry['select'] = "employees.*, inventory.*, computers.*";
     * $qry['from'] = "inventory";
     * $qry['leftjoin']['employees'] = "employees.uid = inventory.employee_uid";
     * $qry['leftjoin']['computers'] = "computers.uid = inventory.employee_uid";
     * $qry['limit'] = "3";
     *
     * // build and execute query..
     * $database->build_select($qry);
     * $database->query();
     *
     * $rows = $database->fetch_all_rows();
     *
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_select($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "SELECT ". $q['select'] ;
        $output .= "\nFROM ". $q['from'] ;


        // plak de left join's aan elkaar
        if ( (isset($q['leftjoin'])) && (is_array($q['leftjoin'])) ) {
            foreach ($q['leftjoin'] as $table => $leftjoin) {
                $output .= "\nLEFT JOIN $table ON ( ".$leftjoin." )";
            }
        }

        // plak de straight join's aan elkaar
        if ( (isset($q['join'])) && (is_array($q['join'])) ) {
            foreach ($q['join'] as $join) {
                $output .= "\nJOIN ".$join;
            }
        }


        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }



        // plak de where_or's aan elkaar
        if ( (isset($q['where_or'])) && (is_array($q['where_or'])) ) {

            // remove empty where_or's
            foreach($q['where_or'] as $key=>$value) {
                if ($value=="") { unset($q['where_or'][$key]); }
            }

            $where = implode(" OR ", $q['where_or']);

            if (count($q['where'])>=1) {
                $output .= "\nAND ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where_or'])) && (is_string($q['where_or'])) ) {

            if (count($q['where'])>1) {
                $output .= "\nAND ( ". $q['where_or'] ." ) ";
            } else {
                $output .= "\nWHERE ". $q['where_or'];
            }
        }



        // plak de group's aan elkaar
        if ( (isset($q['group'])) && (is_array($q['group'])>0) ) {
            // if $q['group'] is an array..
            $group = implode(", ", $q['group']);
            $output .= "\nGROUP BY ". $group;
        } else if ( (isset($q['group'])) && (is_string($q['group'])) ) {
            // if $q['group'] is a single string..
            $output .= "\nGROUP BY ". $q['group'];
        }

        // plak de order's aan elkaar
        if ( (isset($q['order'])) && (is_array($q['order'])) ) {
            // if $q['order'] is an array..
            $order = implode(", ", $q['order']);
            $output .= "\nORDER BY ". $order;
        } else if ( (isset($q['order'])) && (is_string($q['order'])) ) {
            // if $q['order'] is a single string..
            $output .= "\nORDER BY ". $q['order'];
        }

        // eventueel een limit
        if (isset($q['limit'])) {
            $output .= "\nLIMIT  ". $q['limit'];
        }

        $output .=";";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }



    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * $qry = array();
     * $qry['into'] = "employees";
     * $qry['value']['firstname'] = "Henk";
     * $qry['value']['lastname'] = "de Vries";
     *
     * // build and execute query..
     * $database->build_insert($qry);
     * if($database->query()) {
     *   echo "<p>updated!</p>";
     * } else {
     *   echo "<p>not updated!</p>";
     * }
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_insert($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "INSERT INTO ". $q['into'] ." (";

        // Value looks like: $qry['value']['name'] = $value;
        if (count($q['value'])>0) {
            foreach ($q['value'] as $key => $val) {
                $q['fields'][] = $key;
                $q['values'][] = $val;
            }
        }

        // plak de velden aan elkaar
        if (count($q['fields'])>0) {

            $fields = "`" . implode("`, `", $q['fields']). "`" ;
            $output .= $fields;
        }
        $output .= ") \nVALUES (";

        // plak de waarden aan elkaar
        if (count($q['values'])>0) {

            foreach( $q['values'] as $key => $value) {

                if($this->is_sql_function($value)) {
                    $q['values'][$key] = $value;
                } else {
                    $q['values'][$key] = $this->quote($value);
                }

            }

            $values = implode(", ", $q['values']);
            $output .= $values;
        }
        $output .= ");";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }



    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * $qry = array();
     * $qry['update'] = "inventory";
     * $qry['value']['amount'] = "(RAND())*100";
     * $qry['where'][] = "uid=100";
     *
     * // build and execute query..
     * $database->build_update($qry);
     * if($database->query()) {
     *   echo "<p>updated!</p>";
     * } else {
     *   echo "<p>not updated!</p>";
     * }
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_update($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "UPDATE ". $q['update'] ." SET ";

        // Value looks like: $qry['value']['name'] = $value;
        if (count($q['value'])>0) {
            foreach ($q['value'] as $key => $val) {
                $q['fields'][] = $key;
                $q['values'][] = $val;
            }
        }

        // plak de velden aan elkaar
        if (count($q['fields'])>0) {

            $values = array();
            for ($i=0; $i<count($q['fields']); $i++) {

                $key = $q['fields'][$i];
                $value =$q['values'][$i];

                if($this->is_sql_function($value)) {
                    $values[] = sprintf(" %s=%s ",$key, $value );
                } else {
                    $values[] = sprintf(" %s=%s ",$key, $this->quote($value) );
                }
            }

            $values = implode(", ", $values);
            $output .= $values;
        }

        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }

        $output .=";";

        // store as cached function$output .=";";
        $this->cached_query = $output;

        return $output;
    }

    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * $qry = array();
     * $qry['delete'] = "inventory";
     * $qry['where'][] = "uid=100";
     * $qry['limit'] = "3";
     *
     * // build and execute query..
     * $database->build_delete($qry);
     * if($database->query()) {
     *   echo "<p>deleted!</p>";
     * } else {
     *   echo "<p>not deleted!</p>";
     * }
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_delete($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "DELETE FROM ". $q['delete'];

        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }

        // eventueel een limit
        if (isset($q['limit'])) {
            $output .= "\nLIMIT  ". $q['limit'];
        }

        $output .=";";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }

    /**
     * Checks if the parameter is an sql function or not. used to determine
     * whether or not a parameter needs to be escaped.
     *
     * $this->is_sql_function("some value");
     * // returns true
     *
     * @param string string
     * @return boolean
     */
    function is_sql_function($str) {

        // Check if we're even allowed to use SQL functions. If not, return right away..
        if (!$this->allow_functions) {
            return false;
        }
        
        // Determine if value is a literal value, or a sql function.
        if(preg_match("/^([A-Z]{3,}\((.*)\))/", $str, $match)) {
            return true;
        } else {
            return false;
        }

    }
    
    /**
     * Set if we're allowed to use SQL functions in our queries. This is disabled
     * by default, for security reasons.
     *
     * @param boolean $value
     */
    function set_allow_functions($value) {
        $this->allow_functions = ($value ? true : false);
    }


    /**
     * Sets whether or not execution of the script should stop when a
     * sql error has occured.
     *
     *
     * @param boolean value
     */
    function set_halt_on_error($value) {
        $this->halt_on_sql_error = ($value ? true : false);
    }
 

/* Tables initializers */

		/**
		 * Create the SQL table for Entries.
		 *
		 * @param link $sql
		 */
		abstract function makeEntriesTable();

		/**
		 * Create the SQL table for Comments.
		 *
		 * @param link $sql
		 */
		abstract function makeCommentsTable();

		/**
		 * Create the SQL table for Trackbacks.
		 *
		 * @param link $sql
		 */
		abstract function makeTrackbacksTable();

		/**
		 * Create the SQL table for Tags.
		 *
		 * @param link $sql
		 */
		abstract function makeTagsTable();

		/**
		 * Create the SQL table for Categories.
		 *
		 * @param link $sql
		 */
		abstract function makeCategoriesTable();
		
		/**
		 * Create the SQL table for Pages.
		 *
		 * @param link $sql
		 */
		abstract function makePagesTable();

		/**
		 * Create the SQL table for Chapters.
		 *
		 * @param link $sql
		 */
		abstract function makeChaptersTable();
		
		/**
		 * Create the SQL table for the Extra fields in Entries and Pages.
		 *
		 * @param link $sql
		 */
		abstract function makeExtrafieldsTable();
}


?>
