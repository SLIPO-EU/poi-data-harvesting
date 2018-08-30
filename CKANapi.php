<?php

require_once "Harvester.php";

class CKANapi extends Harvester {

	public function __construct($args) {
		parent::__construct($args);
		$version = isset($this->args->version) ? $this->args->version : '3';
		$this->url = "{$this->args->url}/api/$version";
	}

	public function connect($url, $action, $query=null) {
		$url = "$url/action/$action";

		if ($query)
			$url .= "?$query";

		// $url = urldecode(urlencode($url));
		// echo $url . PHP_EOL;

		return json_decode($this->curl->xmlConnect($url));
	}

	public function collect() {
		return $this->connect($this->url, "package_list");
	}

	public function query() {
		$query = array();
		if (!empty($this->args->query))
			$query[] = "q=({$this->args->query})";
		if (!empty($this->args->filter_query))
			$query[] = "fq=({$this->args->filter_query})";
		$query = implode("&", $query);
		// $query = rawurlencode($query);
		$response = $this->connect($this->url, "package_search", $query);
		if (isset($response))
			$this->data = $response->result;
	}

	public function packages($names) {
		$urls = array_map([$this, 'prefix'], $names->result);
		$this->curl->multi_connect($this->curl->xml_opts, $urls, [$this, 'getPackage']);
	}

	public function getPackage($content) {
		$this->data[] = $content->result;
	}

	protected function prefix($value) {
		$prefix = "{$this->url}/action/package_show?id=";
		return $prefix . $value;
	}

}