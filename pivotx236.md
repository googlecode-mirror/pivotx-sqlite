# pivotx\_2.3.6-sqlite #

## Definitions ##
  * **pivotx:** Original Pivotx project
  * **pivotx-sqlite:** This project
  * **pivotx\_vanilla\_xxx:** Latest plain (original) version of pivotx ported to pivotx-sqlite
  * **pivotx\_vanilla\_yyy:** Pivotx version under current phase of porting
  * **pivotx\_sqlite\_xxx:** Latest version of pivotx-sqlite
  * **pivotx\_sqlite\_yyy:** Work-in-progress version o pivotx-sqlite

## Summary of changes ##
**modules\module\_sql.php** replaced by _modules\module\_mysql.php_ + _modules\module\_sqlite.php_ + _modules\module\_sqlFactory.php_

**modules\abstractSql.php** New file. Replaces some parts of _modules\module\_sql.php_ and describes some others extracted from _data.php_.

**modules\module\_mysql.php** New file. It's the concrete MySQL implementation of _abstractSql.php_.

**modules\module\_sqlite.php** New file. It's the concrete Sqlite implementation of _abstractSql.php_.

**modules\module\_sqlFactory.php** New file. It's a factory sql. [Issue 2](https://code.google.com/p/pivotx-sqlite/issues/detail?id=2):
**I really should rewrite sqlFactory in order to make a new sql instance on every getSqlInsance call. By the moment I must instance a new factory for every sql object I need... not really a great factory** :(

**modules\entries\_sql.php** Changed the _setup db connection code_, removed hard coded SQL query, refactored all _make<`*`>Table_ calls to _$this->sql->make<`*`>Table_, changed a snippet of function getArchiveArray `[`and probably introduced a bug`]`, removed many MySQL dependencies (and eventually introduced some MySQL bugs)

**modules\module\_comments.php** Slightly modified a SQL query

**modules\module\_search.php** Changed the _setup db connection code_

**modules\module\_tags.php** Changed the _setup db connection code_

**modules\pages\_sql.php** Changed the _setup db connection code_, removed some MySQL dependencies (and eventually introduced MySQL bugs)

**convertentries2sql.phps** This file does not exists anymore on pivotx\_vanilla\_y

**data.php** Moved checkDBVersion and all _make<`*`>Table_ functions to _modules\module\_mysql_ and implemented them in _modules\module\_sqlite.php_

**forms.php** Added sqlite to configuration options, modified some comments.

**lib.php** Code cleanup, changed the signature of function setError to make it DB technology indipendent.

**objects.php** Changed the _setup db connection code_, code cleanup

## Detail of changes ##
### Files deprecated from pivotx\_vanilla\_xxx ###
These files (and their content) has been replaced by other files and are not yet needed. So they're been deleted and do not exists on pivotx-sqlite.

#### modules\module\_sql.php ####
Replaced by abstract class _abstractSql.php_ (for generic parts), and by _module\_mysql.php_ and _module\_sqlite.php_ (for parts related to a specific SQL technology). A little portion of code, related to the original connection method has been ported to _module\_sqlFactory.php_.

### New files implemented into pivotx\_sqlite\_xxx ###
#### modules\abstractSql.php ####
Abstract class that implements generic functions removed by _module\_sql.php_ and describes (but does not implements) some of methods extracted from _data.php_.

#### modules\module\_mysql.php ####
Concrete MySQL implementation of _abstractSql.php_. Almost all functions where migrated from _module\_sql.php_ and _data.php_.

#### modules\module\_sqlite.php ####
Concrete Sqlite implementation of _abstractSql.php_. This class was written from scratch looking at the existing code of _module\_mysql.php_ (Taken from _module\_sql.php_ and _data.php_).

#### modules\module\_sqlFactory.php ####
Factory sql. It's responsability is to give an instance of the right implementation of _abstractSql.php_. Written from scratch looking at the existing code of the method connection of _module\_sql.php_

### Files changed ###
Files changed between pivotx\_vanilla\_xxx and pivotx\_sqlite\_xxx.
#### modules\entries\_sql.php ####
Changed from
```
require_once(dirname(__FILE__)."/module_sql.php");
```
to
```
require_once(dirname(__FILE__)."/module_sqlFactory.php");
```

Replaced all occurrences of
```
// Set up DB connection
        $this->sql = new sql('mysql',
            $PIVOTX['config']->get('db_databasename'),
            $PIVOTX['config']->get('db_hostname'),
            $PIVOTX['config']->get('db_username'),
            $PIVOTX['config']->get('db_password')
        );
```
with
```
// Set up DB factory
        $this->sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
                                           $PIVOTX['config']->get('db_databasename'),
                                           $PIVOTX['config']->get('db_hostname'),
                                           $PIVOTX['config']->get('db_username'),
                                           $PIVOTX['config']->get('db_password')
        );
// Set up DB connection
        $this->sql = $this->sqlFactory->getSqlInstance();
```

Replaced hard coded sql query
```
$this->sql->query("SHOW TABLES LIKE '" . $PIVOTX['config']->get('db_prefix') . "%'");
$tables = $this->sql->fetch_all_rows('no_names');
```
with
```
$tables = $this->sql->getTableList($PIVOTX['config']->get('db_prefix'));
```

Replaced
```
makeEntriesTable($this->sql);
...
makeCommentsTable($this->sql);
...
makeTrackbacksTable($this->sql);
...
makeTagsTable($this->sql);
...
makeCategoriesTable($this->sql);
...
makePagesTable($this->sql);
...
makeChaptersTable($this->sql);
...
makeExtrafieldsTable($this->sql);
```
with
```
$this->sql->makeEntriesTable();
...
$this->sql->makeCommentsTable();
...
$this->sql->makeTrackbacksTable();
...
$this->sql->makeTagsTable();
...
$this->sql->makeCategoriesTable();
...
$this->sql->makePagesTable();
...
$this->sql->makeChaptersTable();
...
$this->sql->makeExtrafieldsTable();
```

Changed word mysql to sql in some comments, from
```
* Saves the current entry - mysql implementation.
```
to
```
* Saves the current entry - sql implementation.
```

Changed a snippet of function getArchiveArray, **I know they don't make the same thing, but by the moment I would accept this bug**
```
if ($unit=="month" || $unit=="year") {
                $datelength = 7;
            } else {
                $datelength = 10;
            }
            
            // Select all dates of entries in this weblog..
            $this->sql->query("SELECT DISTINCT(LEFT(date, $datelength)) AS date
```
to
```
$this->sql->query("SELECT DISTINCT(date) AS date
```

In function read\_entries changed
```
$qry['group'] = "e.date DESC, e.uid DESC";
```
to
```
$qry['group'] = "e.date, e.uid";
```
because _ordering into group by clause is not allowed by SQL standard_. **Unluckily this was implemented in pivotx\_vanilla\_yyy to fix a bug versus some versions of MySQL:**
> This group seems unnecesary at first, and it used to mess up the order on certain versions of MySQL that had a bug in it. This version, with the explicit order seems to work on both MySQL versions with and without the bug.
I dislike the idea having a piece of MySQL specific code to a generic class, so until a new workaround is not found I will accept the bug versus certain versions of MySQL.

In function save\_entry added
```
, 'extrafields' => $this->entry['extrafields']
```

In function delete\_entry() replaced
```
$this->sql->query("DELETE FROM " . $this->entriestable . " WHERE uid=$uid LIMIT 1;");
```
with
```
$this->sql->query("DELETE FROM " . $this->entriestable . " WHERE uid=$uid;");
```
because sqlite does not support the LIMIT clause. Anyway uid is primary key, so the result of these queries must be the same.

In function checkTimedPublish() removed all those nasty backticks ````` because they are MySQL specific. At least one must change the MySQL escape character so that it has ANSI-compliant syntax by using the [ANSI\_QUOTES flag](http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html#sqlmode_ansi_quotes)
```
$this->sql->query("UPDATE `".$this->entriestable."` SET status='publish', date=publish_date WHERE status='timed' AND publish_date<'$date';");
```
```
$this->sql->query("UPDATE ".$this->entriestable." SET status='publish', date=publish_date WHERE status='timed' AND publish_date<'$date';");
```

#### modules\module\_comments.php ####
In getModerationQueue()
```
ORDER BY date DESC;"
```
replaced by
```
ORDER BY co.date DESC;
```

#### modules\module\_search.php ####
Changed
```
// Set up DB connection
$database = new sql('mysql',
    $PIVOTX['config']->get('db_databasename'),
    $PIVOTX['config']->get('db_hostname'),
    $PIVOTX['config']->get('db_username'),
    $PIVOTX['config']->get('db_password')
);
```
to
```
// Set up DB factory
$sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
			     $PIVOTX['config']->get('db_databasename'),
        		     $PIVOTX['config']->get('db_hostname'),
        		     $PIVOTX['config']->get('db_username'),
        		     $PIVOTX['config']->get('db_password')
                            );
// Set up DB connection
$database = $sqlFactory->getSqlInstance();
```

#### modules\module\_tags.php ####
Changed all occurrences of `new sql('mysql',...`

First
```
// Get a DB connection..
$database = new sql('mysql',
        $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'),
        $PIVOTX['config']->get('db_username'),
        $PIVOTX['config']->get('db_password')
    );
```
to
```
// Set up DB factory
$sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
                             $PIVOTX['config']->get('db_databasename'),
        		     $PIVOTX['config']->get('db_hostname'),
        		     $PIVOTX['config']->get('db_username'),
        		     $PIVOTX['config']->get('db_password')
    			    );
// Set up DB connection
$database = $sqlFactory->getSqlInstance();
```

Second
```
// Get a DB connection..
$sql = new sql('mysql',
        $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'),
        $PIVOTX['config']->get('db_username'),
        $PIVOTX['config']->get('db_password')
    );
```
to
```
// Set up DB factory
$sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
                             $PIVOTX['config']->get('db_databasename'),
                             $PIVOTX['config']->get('db_hostname'),
                             $PIVOTX['config']->get('db_username'),
                             $PIVOTX['config']->get('db_password')
			    );
// Set up DB connection
$sql = $sqlFactory->getSqlInstance();
```

Third
```
// Get a DB connection..
$sql = new sql('mysql',
        $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'),
        $PIVOTX['config']->get('db_username'),
        $PIVOTX['config']->get('db_password')
    );        
```
to
```
// Set up DB factory
$sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
                             $PIVOTX['config']->get('db_databasename'),
                             $PIVOTX['config']->get('db_hostname'),
                             $PIVOTX['config']->get('db_username'),
                             $PIVOTX['config']->get('db_password')
			    );
// Set up DB connection
$sql = $sqlFactory->getSqlInstance();    
```

#### modules\pages\_sql.php ####
Replaced
```
require_once(dirname(__FILE__)."/module_sql.php");
```
with
```
require_once(dirname(__FILE__)."/module_sqlFactory.php");
```

From
```
// Set up DB connection
$this->sql = new sql(
        'mysql',
        $PIVOTX['config']->get('db_databasename'),
        $PIVOTX['config']->get('db_hostname'),
        $PIVOTX['config']->get('db_username'),
        $PIVOTX['config']->get('db_password')
    );
```
to
```
// Set up DB factory
$this->sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
                                   $PIVOTX['config']->get('db_databasename'),
                                   $PIVOTX['config']->get('db_hostname'),
                                   $PIVOTX['config']->get('db_username'),
                                   $PIVOTX['config']->get('db_password')
			          );
// Set up DB connection
$this->sql = $this->sqlFactory->getSqlInstance();
```

In function checkTimedPublish() removed all those nasty backticks ````` because they are MySQL specific. At least one must change the MySQL escape character so that it has ANSI-compliant syntax by using the [ANSI\_QUOTES flag](http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html#sqlmode_ansi_quotes)
```
$this->sql->query("UPDATE `".$this->pagestable."` SET status='publish', date=publish_date WHERE status='timed' AND publish_date<'$date';");
```
```
$this->sql->query("UPDATE ".$this->pagestable." SET status='publish', date=publish_date WHERE status='timed' AND publish_date<'$date';");
```

#### data.php ####
Moved these functions:
  * makePagesTable
  * makeChaptersTable
  * makeEntriesTable
  * makeExtrafieldsTable
  * makeCommentsTable
  * makeTrackbacksTable
  * makeTagsTable
  * makeCategoriesTable
  * checkDBVersion
to _modules\module\_mysql_ and implemented them in _modules\module\_sqlite.php_

#### forms.php ####
Added sqlite to configuration options, changing
```
// If we know the server supports MySQL, we allow the user to select it as DB model
if (function_exists('mysql_get_client_info')) {
    $mysqlversion = mysql_get_client_info();
    if ($mysqlversion > $minrequiredmysql) {

        $form->add(array(
            'type' => 'hr'
        ));

        $form->add(array(
            'type' => 'info',
            'text' => wordwrap(__("PivotX detected that your webserver supports MySQL databases.") ." " .
                __("If you have a MySQL user and database, specify them below.") . " " .
                __("If you do not have these, either ask your hosting provider or select the 'Flat Files' model."),
                80, "<br />\n")
        ));

        $form->add( array(
            'type' => 'select',
            'name' => 'db_model',
            'label' => __('Database Model'),
            'value' => 'mysql',
            'error' => __('Error'),
            'firstoption' => __('Select'),
            'options' => array(
               'flat' => __("Flat Files"),
               'mysql' => "MySQL",
               //'sqlite' => "SQLite",
               //'postgresql' => "PostgreSQL"
               ),
            'isrequired' => 1,
            'validation' => 'any',
            'text' => makeJtip(__('Database Model'), __('Select which type of Database to use. Flat Files will work on almost every platform. If your server is capable of using databases, the performance of PivotX will be best if you use MySQL or SQLite.'))
        ));

...
...
...

        $('#db_prefix').attr('readonly', '').removeClass('dim');
                        } else {

...
...
...
```
into
```
$options = array('flat' => __("Flat Files"));

$supportedDB = array();
// If we know the server supports MySQL, we allow the user to select it as DB model
if (function_exists('mysql_get_client_info')) {
    $mysqlversion = mysql_get_client_info();
    if ($mysqlversion > $minrequiredmysql) {
			    $supportedDB['mysql'] = true;
			    $options['mysql'] = "MySQL";
		}
}

if (function_exists('sqlite_libversion')) {
    $sqlitelibversion = sqlite_libversion();
    $supportedDB['sqlite'] = true;
    $options['sqlite'] = "SQLite";
}

//'postgresql' => "PostgreSQL"

// If we know the server supports MySQL, we allow the user to select it as DB model
if (sizeof($supportedDB)) {

      $form->add(array(
          'type' => 'hr'
      ));

      $form->add(array(
          'type' => 'info',
          'text' => wordwrap(__("PivotX detected a supported database.") ." " .
              __("You have to specify database connection properties to fileds below.") . " " .
              __("If you do not have these, either ask your hosting provider or select the 'Flat Files' model."),
              80, "<br />\n")
      ));

      $form->add( array(
          'type' => 'select',
          'name' => 'db_model',
          'label' => __('Database Model'),
          'value' => 'mysql',
          'error' => __('Error'),
          'firstoption' => __('Select'),
          'options' => $options,
          'isrequired' => 1,
          'validation' => 'any',
          'text' => makeJtip(__('Database Model'), __('Select which type of Database to use. Flat Files will work on almost every platform. If your server is capable of using databases, the performance of PivotX will be best if you use MySQL or SQLite.'))
      ));

...
...
...

      $('#db_prefix').attr('readonly', '').removeClass('dim');
                      } else 
                      if ( $('#db_model').val() == 'sqlite') {
                          $('#db_username').attr('readonly', 'readonly').addClass('dim');
                          $('#db_password').attr('readonly', 'readonly').addClass('dim');
                          $('#db_databasename').attr('readonly', '').removeClass('dim');
                          $('#db_hostname').attr('readonly', 'readonly').addClass('dim');
                          $('#db_prefix').attr('readonly', '').removeClass('dim');
                      } else 
                      {

...
...
...
}
```

In getConfigForm2
```
       //'sqlite' => "SQLite",
```
to
```
       'sqlite' => "SQLite",
```

In both getSetupUserForm and getConfigForm2 changed
```
'text' => makeJtip(__('Table Prefix'), __('The prefix to use for the database tables. By changing this, you can run multiple installations of PivotX from one MySQL database. If you don\'t intend to do so (yet), just leave this set to "pivotx_".'
```
to
```
'text' => makeJtip(__('Table Prefix'), __('The prefix to use for the database tables. By changing this, you can run multiple installations of PivotX from one SQL database. If you don\'t intend to do so (yet), just leave this set to "pivotx_".'
```

Modified some comments
```
// Add a bit of javascript to disable the form-fields for MySQL stuff,
// When the user selects flat files.
```
to
```
// Add a bit of javascript to disable the form-fields for DB stuff,
// when the user selects flat files.
```

#### lib.php ####
Replaced
```
require_once($pivotx_path.'modules/module_sql.php');
```
with
```
require_once($pivotx_path."modules/module_sqlFactory.php");
```

Changed
```
checkDBVersion();
```
with
```
$sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
...
$sql = $sqlFactory->getSqlInstance();
$sql->checkDBVersion();
```

Modified the signature of function setError to make it DB technology indipendent
```
function setError($type='general', $error_msg, $sql_query="") {
```
```
function setError($type='general', $error_msg, $sql_query="", $error_no="") {
```
and changed the body accordingly
```
...
$error_no = mysql_errno();
$error_text = mysql_error();

// If the given error is the same as the error we get from mySQL,
// we don't need to print 'em both:
if ($error_msg == $error_text) {
    $error_msg = "";
} else {
    $error_msg = "<p><strong>$error_msg</strong></p>";
}

$error = sprintf(__("<p>There was a problem with the Database: </p>
%s
<p><tt>error code %s: %s</tt></p>
</p>
<ul><li>If you're in the process of setting up PivotX, you should review your
<a href='%s'>Database connection settings</a>.</li>
<li>If it worked before, you should check if the Mysql database engine is
still running on the server (or ask your systems administrator to check for you).</li>
</ol>"),
    $error_msg,
    $error_no,
    $error_text,
    "index.php?page=configuration#section-2"
);
...
```

```
...
$error = sprintf(__("<p>There was a problem with the Database.</p>
<p><tt>error code %s: %s</tt></p>
<p><tt>query: %s</tt></p>
</p>
<ul><li>If you're in the process of setting up PivotX, you should review your
<a href='%s'>Database connection settings</a>.</li>
<li>If it worked before, you should check if the Mysql database engine is
still running on the server (or ask your systems administrator to check for you).</li>
</ol>"),
    $error_no,
    $error_msg,
    $sql_query,
    "index.php?page=configuration#section-2"
);
...
```

#### objects.php ####
From
```
// Get the amount from our SQL db..
$sql = new Sql('mysql', $PIVOTX['config']->get('db_databasename'), $PIVOTX['config']->get('db_hostname'),
$PIVOTX['config']->get('db_username'), $PIVOTX['config']->get('db_password'));
```
to
```
// Set up DB factory
$sqlFactory = new sqlFactory($PIVOTX['config']->get('db_model'),
    			     $PIVOTX['config']->get('db_databasename'),
    			     $PIVOTX['config']->get('db_hostname'),
    			     $PIVOTX['config']->get('db_username'),
    			     $PIVOTX['config']->get('db_password')
                            );
// Set up DB connection
$sql = $sqlFactory->getSqlInstance();
```

Replaced
```
$sql->query("SELECT COUNT(DISTINCT(e.uid)) FROM $entriestable AS e, $categoriestable as c WHERE e.status='publish' AND e.uid=c.target_uid AND c.category IN ('$eachcats');");
```
with
```
$qry['select']="COUNT(DISTINCT(e.uid))";
$qry['from'] = $entriestable." AS e, ".$categoriestable." as c";
$qry['where'][] = "e.status='publish' AND e.uid=c.target_uid AND c.category IN ('$eachcats')";
$sqlquery = $sql->build_select($qry);
$sql->query($sqlquery);
```