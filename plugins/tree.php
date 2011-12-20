<?php

class tree {

	var $params = array(
		'table'     => 'tree',
		'select'    => '*',
		'where'     => 'parent = "{parent}"',
		'sort'      => 'sort ASC',
		'id-field'  => 'id',
		'uri-field' => 'uri'
	);

	var $index = array();
	
	function __construct($params=array()) {
		$this->setup($params);
		$this->get();				
	}

	function setup($params) {
		if(!is_array($params)) return $this->params['table'] = $params;
		$this->params = array_merge($this->params, $params);
	}

	function get($key=false) {

		if(!$key) {
			if(!empty($this->index)) return $this->index;
			$this->climb();
			return $this->index;
		}
		
		// define the available index keys
		$keys = array('raw', 'uri', 'id');

		// sanitize the key
		if(!in_array($key, $keys)) $key = 'raw';

		// check if the index is already there
		if(isset($this->index[$key])) return $this->index[$key];
		
		// walk the entries and gather all indexes
		self::climb();

		// return the right index
		return a::get($this->index, $key, array());
	
	}

	function where($parent) {
		return str_replace('{parent}', $parent, $this->params['where']);
	}

	function climb($parent=0, $level=0, $uri=false) {
		
		// fetch the data
		$data = db::select(
			$this->params['table'],
			$this->params['select'],
			$this->where($parent), 
			$this->params['sort']
		);
		
		$children = array();
		
		// loop through all children		
		foreach($data AS $item) {

			$uri = ltrim($uri . '/' . $item[ $this->params['uri-field'] ], '/');
			$id  = $item[ $this->params['id-field'] ];

			$item['uri']   = $uri;
			$item['level'] = $level;

			$this->index[ 'raw' ][ $id  ] = $item;
			$this->index[ 'id'  ][ $id  ] = $item;
			$this->index[ 'uri' ][ $uri ] = $item;
			
			// add the children if there are any
			$item['children'] = $this->climb($id, ($level+1), $uri);
			
			// add them to the children array
			$children[] = $item;
									
			// prepare the indexes
			$this->index[ 'raw' ][ $id  ]['children'] = $item['children'];
			$this->index[ 'id'  ][ $id  ]['children'] = array();
			$this->index[ 'uri' ][ $uri ]['children'] = array();
			
			// clear children for flat indexes			
			foreach($item['children'] AS $child) {
				$this->index[ 'id'  ][ $id  ]['children'][] = $child['id'];
				$this->index[ 'uri' ][ $uri ]['children'][] = $child['uri'];
			} 
									
		}
		
		return $children;
									
	}
	
}

?>