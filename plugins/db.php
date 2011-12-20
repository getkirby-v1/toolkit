<?php

// sample config
//c::set('db.host', '127.0.0.1');
//c::set('db.user', 'root');
//c::set('db.password', '');
//c::set('db.name', 'databasename');

class db {

	public	static $trace	= array();
	private static $connections = array();
	private static $connection = false;
	private static $database = false;
	private static $charset	= false;
	private static $last_query = false;
	private static $affected = 0;

	function connect($key=false) {

		self::$connection = a::get(self::$connections, $key);
		
		// a connection is already available let's return that 
		if(is_resource(self::$connection)) return self::$connection;
			
		$credentials = c::get('db.connections');
		$credentials = a::get($credentials, $key);
		
		if(empty($credentials)) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.connect', 'Database connection failed')		
		);
		
		$host        = a::get($credentials, 'host', 'localhost');
		$user        = a::get($credentials, 'user', 'root');
		$password    = a::get($credentials, 'password', '');
		$database    = a::get($credentials, 'database');
		$charset     = a::get($credentials, 'charset', 'utf8');
		
		if(empty($database)) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.connect', 'Database connection failed')
		);

		self::$connection = @mysql_connect($host, $user, $password);
		
		if(!is_resource(self::$connection)) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.connect', 'Database connection failed')
		);

		// select the database
		$database = self::database($database);
		if(error($database)) return $database;

		// set the right charset
		$charset = self::charset($charset);
		if(error($charset)) return $charset;

		return self::$connection;

	}

	function connection() {
		return (is_resource(self::$connection)) ? self::$connection : false;
	}

	function disconnect() {

		if(!c::get('db.disconnect')) return false;

		$connection = self::connection();
		if(!$connection) return false;

		// kill the connection
		$disconnect = @mysql_close($connection);
		self::$connection = false;

		if(!$disconnect) return self::error(l::get('db.errors.disconnect', 'Disconnecting database failed'));
		return true;

	}

	function database($database) {

		if(!$database) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.missing_db_name', 'Please provide a database name')
		);

		// check if there is a selected database
		if(self::$database == $database) return true;

		// select a new database
		$select = @mysql_select_db($database, self::$connection);

		if(!$select) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.missing_db', 'Selecting database failed')
		);

		self::$database = $database;
		return $database;

	}

	function charset($charset='utf8') {

		// check if there is a assigned charset and compare it
		if(self::$charset == $charset) return true;

		// set the new charset
		$set = @mysql_query('SET NAMES ' . $charset);

		if(!$set) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.setting_charset_failed', 'Setting database charset failed')
		);

		self::$charset = $charset;
		return $charset;

	}

	function query($sql, $fetch=true) {

		$connection = self::connect();
		if(error($connection)) return $connection;

		// save the query
		self::$last_query = $sql;

		// execute the query
		$result = @mysql_query($sql, $connection);

		self::$affected = @mysql_affected_rows();
		self::$trace[] = $sql;

		if(!$result) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.query_failed', 'The database query failed'),
			'query'  => $sql
		);
		
		if(!$fetch)	return $result;

		$array = array();
		while($r = self::fetch($result)) array_push($array, $r);
		return $array;

	}

	function execute($sql) {

		$connection = self::connect();
		if(error($connection)) return $connection;

		// save the query
		self::$last_query = $sql;

		// execute the query
		$execute = @mysql_query($sql, $connection);

		self::$affected = @mysql_affected_rows();
		self::$trace[] = $sql;

		if(!$execute) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.query_failed', 'The database query failed'),
			'query'  => $sql
		);
		
		$last_id = self::last_id();
		return ($last_id === false) ? self::$affected : self::last_id();

	}

	function affected() {
		return self::$affected;
	}

	function last_id() {
		return @mysql_insert_id(self::$connection);
	}

	function fetch($result, $type=MYSQL_ASSOC) {
		if(!$result) return array();
		return @mysql_fetch_array($result, $type);
	}

	function fields($table) {

		$connection = self::connect();
		if(error($connection)) return $connection;

		$fields = @mysql_list_fields(self::$database, self::prefix($table), $connection);

		if(!$fields) return array(
			'status' => 'error',
			'msg'    => l::get('db.errors.listing_fields_failed', 'Listing fields failed')
		);

		$output = array();
		$count	= @mysql_num_fields($fields);

		for($x=0; $x<$count; $x++) {
			$output[] = @mysql_field_name($fields, $x);
		}

		return $output;

	}

	function insert($table, $input, $ignore=false) {
		$ignore = ($ignore) ? ' IGNORE' : '';
		return self::execute('INSERT' . ($ignore) . ' INTO ' . self::prefix($table) . ' SET ' . self::values($input));
	}

	function insert_all($table, $fields, $values) {
			
		$query = 'INSERT INTO ' . self::prefix($table) . ' (' . implode(',', $fields) . ') VALUES ';
		$rows  = array();
		
		foreach($values AS $v) {    
			$str = '(\'';
			$sep = '';
			
			foreach($v AS $input) {
				$str .= $sep . db::escape($input);            
				$sep = "','";  
			}

			$str .= '\')';
			$rows[] = $str;
		}
		
		$query .= implode(',', $rows);
		return db::execute($query);
	
	}

	function replace($table, $input) {
		return self::execute('REPLACE INTO ' . self::prefix($table) . ' SET ' . self::values($input));
	}

	function update($table, $input, $where) {
		return self::execute('UPDATE ' . self::prefix($table) . ' SET ' . self::values($input) . ' WHERE ' . self::where($where));
	}

	function delete($table, $where='') {
		$sql = 'DELETE FROM ' . self::prefix($table);
		if(!empty($where)) $sql .= ' WHERE ' . self::where($where);
		return self::execute($sql);
	}

	function select($table, $select='*', $where=null, $order=null, $page=null, $limit=null, $fetch=true) {

		$sql = 'SELECT ' . $select . ' FROM ' . self::prefix($table);

		if(!empty($where)) $sql .= ' WHERE ' . self::where($where);
		if(!empty($order)) $sql .= ' ORDER BY ' . $order;
		if($page !== null && $limit !== null) $sql .= ' LIMIT ' . $page . ',' . $limit;

		return self::query($sql, $fetch);

	}

	function row($table, $select='*', $where=null, $order=null) {
		$result = self::select($table, $select, $where, $order, 0,1, false);
		return self::fetch($result);
	}

	function column($table, $column, $where=null, $order=null, $page=null, $limit=null) {

		$result = self::select($table, $column, $where, $order, $page, $limit, false);

		$array = array();
		while($r = self::fetch($result)) array_push($array, a::get($r, $column));
		return $array;
	}

	function field($table, $field, $where=null, $order=null) {
		$result = self::row($table, $field, $where, $order);
		return a::get($result, $field);
	}

	function join($table_1, $table_2, $on, $select, $where=null, $order=null, $page=null, $limit=null, $type="JOIN") {
			return self::select(
				self::prefix($table_1) . ' ' . $type . ' ' .
				self::prefix($table_2) . ' ON ' .
				self::where($on),
				$select,
				self::where($where),
				$order,
				$page,
				$limit
			);
	}

	function left_join($table_1, $table_2, $on, $select, $where=null, $order=null, $page=null, $limit=null) {
			return self::join($table_1, $table_2, $on, $select, $where, $order, $page, $limit, 'LEFT JOIN');
	}

	function count($table, $where='') {
		$result = self::row($table, 'count(*)', $where);
		return ($result) ? a::get($result, 'count(*)') : 0;
	}

	function min($table, $column, $where=null) {

		$sql = 'SELECT MIN(' . $column . ') AS min FROM ' . self::prefix($table);
		if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

		$result = self::query($sql, false);
		$result = self::fetch($result);

		return a::get($result, 'min', 1);

	}

	function max($table, $column, $where=null) {

		$sql = 'SELECT MAX(' . $column . ') AS max FROM ' . self::prefix($table);
		if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

		$result = self::query($sql, false);
		$result = self::fetch($result);

		return a::get($result, 'max', 1);

	}

	function sum($table, $column, $where=null) {

		$sql = 'SELECT SUM(' . $column . ') AS sum FROM ' . self::prefix($table);
		if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

		$result = self::query($sql, false);
		$result = self::fetch($result);

		return a::get($result, 'sum', 0);

	}

	function prefix($table) {
		$prefix = c::get('db.prefix');
		if(!$prefix) return $table;
		return (!str::contains($table,$prefix)) ? $prefix . $table : $table;
	}

	function simple_fields($array) {
		if(empty($array)) return false;
		$output = array();
		foreach($array AS $key => $value) {
			$key = substr($key, strpos($key, '_')+1);
			$output[$key] = $value;
		}
		return $output;
	}

	function values($input) {
		if(!is_array($input)) return $input;

		$output = array();
		foreach($input AS $key => $value) {
			if($value === 'NOW()')
				$output[] = $key . ' = NOW()';
			elseif(is_array($value))
				$output[] = $key . ' = \'' . a::json($value) . '\'';
			else
				$output[] = $key . ' = \'' . self::escape($value) . '\'';
		}
		return implode(', ', $output);

	}

	function escape($value) {
		$value = str::stripslashes($value);
		return mysql_real_escape_string((string)$value, self::connect());
	}

	function search_clause($search, $fields, $mode='OR') {

		if(empty($search)) return false;

		$arr = array();
		foreach($fields AS $f) array_push($arr, $f . ' LIKE \'%' . $search . '%\'');
		return '(' . implode(' ' . trim($mode) . ' ', $arr) . ')';

	}

	function select_clause($fields) {
		return implode(', ', $fields);
	}

	function in($array) {
		return '\'' . implode('\',\'', $array) . '\'';
	}

	function where($array, $method='AND') {

		if(!is_array($array)) return $array;

		$output = array();
		foreach($array AS $field => $value) {
			$output[] = $field . ' = \'' . self::escape($value) . '\'';
			$separator = ' ' . $method . ' ';
		}
		return implode(' ' . $method . ' ', $output);

	}

	function error($msg=null, $exit=false) {

		$connection = self::connection();

		$error	= (mysql_error()) ? @mysql_error($connection) : false;
		$number = (mysql_errno()) ? @mysql_errno($connection) : 0;

		if(c::get('db.debugging')) {
			if($error) $msg .= ' -> ' . $error . ' (' . $number . ')';
			if(self::$last_query) $msg .= ' Query: ' . self::$last_query;
		} else $msg .= ' - ' . l::get('db.errors.msg', 'This will be fixed soon!');

		if($exit || c::get('db.debugging')) die($msg);

		return array(
			'status' => 'error',
			'msg'	=> $msg
		);

	}

}

?>