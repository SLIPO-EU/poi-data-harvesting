<?php

class CurlTools {
	public $base_url, $xml_opts;

	public function __construct() {
		$header = array(
			"X-Requested-With: XMLHttpRequest",
			"Accept: application/json",
			"User-Agent: curl"
		);
		$this->xml_opts = array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_CUSTOMREQUEST => "GET",
		);
	}

	/**
	 * Wrapper for curl xml connection, using default options.
	 *
	 * @param string $url
	 *
	 * @return mixed $reply
	 */
	public function xmlConnect($url) {

		$ch = curl_init();
		$url = $this->escape($ch, $url);
		$curl_opts = $this->xml_opts;
		$curl_opts[CURLOPT_URL] = $url;
		curl_setopt_array($ch, $curl_opts);

		$reply = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$reply = substr($status, 0, 1)==="2" ? $reply : false;
		return $reply;
	}

	/**
	 * Returns the escaped url using curl_escape.
	 *
	 * @param resource $ch A cURL handle returned by curl_init()
	 * @param string $url The URL to be encoded
	 *
	 * @return string $url The encoded URL
	 */
	public function escape($ch, $url) {
		$url_parts = explode("?", $url);

		if (isset($url_parts[1])) {
			$queries = explode("&", $url_parts[1]);
			foreach ($queries as &$query) {
				$q_parts = explode("=", $query);
				if (isset($q_parts[1]))
					$q_parts[1] = curl_escape($ch, $q_parts[1]);
				$query = implode("=", $q_parts);
			}
			$url_parts[1] = implode("&", $queries);
		}
		$url = implode("?", $url_parts);

		return $url;
	}

	/**
	 * Connects asychronously to urls.
	 *
	 * It uses the asychronous curl library to fetch the content of multiple web pages.
	 *
	 * @param array $options The options for curl.
	 * @param array $urls An array containing the urls of the requested web pages.
	 * @param function $callback A callback function called when a single content has been fetched.
	 *
	 * @return integer The number of the given urls that have been fetched (due to memory limit).
	 */
	public function multi_connect($options, $urls, $callback) {
		$master = curl_multi_init();
		$size = sizeof($urls);
		$max_con = min($size, MAX_CON);
		$ch = array();
		for ($index = 0; $index < $max_con; $index++) {
			$ch[$index] = curl_init();
			$options[CURLOPT_URL] = !empty($this->base_url) ? $this->base_url . $urls[$index] : $urls[$index];
			curl_setopt_array($ch[$index], $options);
			curl_multi_add_handle($master, $ch[$index]);
		}

		$active = null;
		$stop = false;
		do {
			$stop = $this->memory();
			do {
				$mrc = curl_multi_exec($master, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			if ($mrc != CURLM_OK)
				break;
			while ($done = curl_multi_info_read($master)) {
				$info = curl_getinfo($done['handle']);
				$url = $urls[array_search($done['handle'], $ch)];
				if ($info['http_code'] == 200 && explode(";", $info['content_type'])[0] == 'text/html') {
					$html = curl_multi_getcontent($done['handle']);
					$callback($html, $url);
				}
				if ($info['http_code'] == 200 && explode(";", $info['content_type'])[0] == 'application/json') {
					$json = json_decode(curl_multi_getcontent($done['handle']));
					$callback($json);
				}
				if ($index < $size && !$stop) {
					$ch[$index] = curl_init();
					$options[CURLOPT_URL] = !empty($this->base_url) ? $this->base_url . $urls[$index] : $urls[$index];
					curl_setopt_array($ch[$index], $options);
					curl_multi_add_handle($master, $ch[$index]);
					$index++;
				}
				curl_multi_remove_handle($master, $done['handle']);
				curl_close($done['handle']);
			}
			if ($active)
				curl_multi_select($master);
		} while ($active);

		curl_multi_close($master);
		return $index;
	}

	/**
	 * Gets information about memory usage.
	 *
	 * @param void
	 *
	 * @return boolean Whether the memory is almost full or not.
	 */
	private function memory() {
		if (MEMORY_LIMIT == -1)
			return false;
		$mem = memory_get_usage(true);
		$stop = ($mem >= 0.9*MEMORY_LIMIT);

		return $stop;
	}

}
