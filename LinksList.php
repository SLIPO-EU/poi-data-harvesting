<?php

class LinksList {
	public $data, $args, $base_url;
	protected $field, $curl_opts;
	private $curl;

	public function __construct($args) {
		$this->args = $args;
		$this->curl = new CurlTools();
		$this->curl_opts = array(
			CURLOPT_CUSTOMREQUEST  => "GET",                       // set request type post or get
			CURLOPT_POST           => false,                       // set to GET
			CURLOPT_RETURNTRANSFER => true,                        // return web page
			CURLOPT_HEADER         => false,                       // don't return headers
			CURLOPT_FOLLOWLOCATION => true,                        // follow redirects
			CURLOPT_ENCODING       => "",                          // handle all encodings
			CURLOPT_AUTOREFERER    => true,                        // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,                         // timeout on connect
			CURLOPT_TIMEOUT        => 120,                         // timeout on response
			CURLOPT_MAXREDIRS      => 4,                           // stop after 4 redirects
			CURLOPT_FRESH_CONNECT  => true,
		);
		$this->base_url = $this->baseUrl($this->args->url);
		$this->args->fields = is_array($this->args->fields) ? $this->args->fields : [$this->args->fields];
	}

	public function __destruct() {
		$data = new stdClass;
		$data->list = array_values($this->data);
		$data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		file_put_contents($this->args->filename, $data);
	}

	public function harvestLinks() {
		$urls = [$this->args->url];
		$base_url = '';
		foreach ($this->args->fields as $field) {
			$this->data = array();
			$this->field = $field;
			$this->curl->multi_connect($this->curl_opts, $urls, [$this, 'callback']);
			$this->curl->base_url = $this->base_url;
			$urls = array_keys($this->data);
		}
		return $this->data;
	}

	public function callback($html, $url) {
		$dom = new DOMTools($html);
		$data = $dom->constructList($this->field);
		$this->data = array_merge($this->data, $data);
	}

	protected function baseUrl($url) {
		$strips = explode("://", $url);
		$schema = sizeof($strips) > 1 ? $strips[0] : "http";
		$path = $strips[sizeof($strips) - 1];
		$base = explode("/", $path)[0];
		$base_url = "$schema://$base";
		return $base_url;
	}
}