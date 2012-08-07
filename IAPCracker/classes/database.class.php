<?php
class DB {
	public $query, $conntection;


	/**
	 * Sets mode to non-gui debug. Query times and errors will be printed directly to page.
	 */
	function debug() {
		$this->debug = true;
	}
	
	/**
	 * Gets row count as SELECT COUNT(*) FROM ...
	 * @param string $table Table to be selected
	 * @param string $suffix Options to select
	 * @return int Count of rows
	 */
	function get_row_count($table, $suffix = "")
	{
		if ($suffix)
			$suffix = " $suffix";
		$r = $this->query("SELECT SUM(1) FROM $table $suffix");
		$a = mysql_fetch_row($r);
		return $a[0]?$a[0]:0;
	}
	
	function build_update_query($ar) {
	global $DB;
	foreach ($ar as $k=>$v) {
		if (strlen($v))
		$to_update[] = "$k =".$DB->sqlesc($v);
		else $to_update[] = "$k = NULL";//.$DB->sqlesc($v);
	}
	return implode(', ',$to_update); 
}

function build_insert_query($ar) {
	global $DB;
	foreach ($ar as $k=>$v) {
		$keys[] = "`$k`";
		if (strlen($v))
		$vals[] = $DB->sqlesc($v);
		else $vals[] = 'NULL';
	}
	return "(".implode(',',$keys).") VALUES (".implode(',',$vals).")";
}

	/**
	 * Escapes value making search query.
	 * <code>
	 * sqlwildcardesc ('The 120% alcohol');
	 * </code>
	 * @param string $x Value to be escaped
	 * @return string Escaped value
	 */
	function sqlwildcardesc($x) {
		return str_replace(array("%","_"), array("\\%","\\_"), mysql_real_escape_string($x));
	}
	
	function __construct($db) {
		$this->ttime = 0;
		$this->connection = @mysql_connect($db['host'], $db['user'], $db['pass']);
		if (!$this->connection)
		die("Error " . mysql_errno() . " aka " . mysql_error().". Failed to estabilish connection to SQL server");
		mysql_select_db($db['db'])
		or die("Cannot select database {$db['db']}: " + mysql_error());

		$this->my_set_charset($db['charset']);
		$this->query = array();
		register_shutdown_function("mysql_close");
		//$this->query[0] = array("seconds" => 0, "query" => 'TOTAL');
	}

	/**
	 * Sets charset to database connection.
	 * @param string $charset Charset to be set
	 * @return void
	 */
	function my_set_charset($charset) {
		if (!function_exists("mysql_set_charset") || !mysql_set_charset($charset)) mysql_query("SET NAMES $charset");
		return;
	}

	/**
	 * Preforms a sql query and writes query and time to statistics
	 * @param string $query Query to be performed
	 * @return resource Mysql resource
	 */
	function query($query) {
global $file;
		$query_start_time = microtime(true); // Start time
		$result = mysql_query($query);
		$query_end_time = microtime(true); // End time
		$query_time = ($query_end_time - $query_start_time);
		$this->ttime = $this->ttime + $query_time;
		if ($this->debug) {
			print "$query<br/>took $query_time, total {$this->ttime}<hr/>";

		}
		if (mysql_errno()&&mysql_errno()!=1062) {

			$to_log = "ERROR: ".mysql_errno()." - ".mysql_error()."<br/>$query<br/>took $query_time, total {$this->ttime}<br/>Backtrace:<hr/>";
			$to_log .= "<pre>";
			$to_log .= var_export(debug_backtrace(),true);
			$to_log .= "</pre><hr/>";
			//fwrite($file,$to_log);
			if (!$this->debug()) die();
		}
		$this->query[] = array("seconds" => $query_time, "query" => $query);
		return $result;
	}
	/**
	 * Escapes value to make safe $DB->query
	 * @param string $value Value to be escaped
	 * @return string Escaped value
	 * @see $DB->query()
	 */
	function sqlesc($value) {
		// Quote if not a number or a numeric string
		if (!is_numeric($value)) {
			$value = "'" . (mysql_real_escape_string((string)$value)) . "'";
		}
		return $value;
	}
	/**
	 * Preforms a sql query, returning a results
	 * @param string $query query to be executed
	 * @param string $type Type of returned data, assoc (default) - associative array, array - array, object - object
	 * @return mixed
	 */
	function query_return($query,$type='assoc') {
		$res = $this->query($query);
		if ($res) {
			if ($type=='assoc')
			while ($row = mysql_fetch_assoc($res)) {
				$return[] = $row;
			}
			elseif ($type=='array')
			while ($row = mysql_fetch_array($res)) {
				$return[] = $row;
			}
			elseif ($type=='object')
			while ($row = mysql_fetch_assoc($res)) {
				$return[] = $row;
			}
			return $return;
		} else return false;
	}

	/**
	 * Preforms an sql query, returns first row
	 * @param string $query query to be executed
	 * @param string $type Type of returned data, assoc (default) - associative array, array - array, object - object
	 * @return mixed
	 */
	function query_row($query,$type='assoc') {
		$result = $this->query_return($query,$type);
		if (!$result) return false;
		return array_shift($result);
	}

}