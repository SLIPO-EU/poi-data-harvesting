<?php

require_once "Harvester.php";

class WebHarvester extends Harvester {

	public function __construct($args) {
		parent::__construct($args);
		if ($this->filetype == 'csv')
			fputcsv($this->file_handle, array_merge(['name'], array_keys((array)$args->tags), ['referer', 'coordinates']));
	}

	public function fetchData($list) {
		$urls = array_keys($list);
		$this->list = $list;
		return $this->curl->multi_connect($this->curl_opts, $urls, [$this, 'getInfo']);
	}

	public function getInfo($html, $url) {
		$title = $this->list[$url]->title;
		$html = $this->fixEncoding($html);
		$coordinates = isset($this->list[$url]->coordinates) ? $this->list[$url]->coordinates : $this->getCoordinates($html);
		$dom = new DOMTools($html);
		$data = $dom->harvestData($this->args->tags);
		$data['referer'] = [$url];
		if ($coordinates !== false)
		$data['coordinates'] = $coordinates!==false ? [$coordinates] : [];
		switch ($this->filetype) {
			case 'csv':
				fputcsv($this->file_handle, array_merge([$title], array_map([$this, 'implode'], $data)));
				break;

			case 'json':
				$this->data[$title] = $data;
				break;
		}
	}

	protected function implode($elem) {
		return implode(" | ", $elem);
	}

	protected function getCoordinates($str) {
		$reg = '/-?(?:[0-9]{1,2})\.(?:[0-9]{6,})\,\+?-?(?:[0-1]{1})?(?:[0-9]{1,2})\.(?:[0-9]{6,})/';
		$found = preg_match($reg, $str, $matches);
		return ($found==false) ? false : $matches[0];
	}

	protected function fixEncoding($str) {
		$encoding = mb_detect_encoding($str, mb_detect_order(), true);
		if ($encoding != "UTF-8")
			$str = iconv($encoding, "UTF-8", $str);
		return $str;
	}

	public function collect() {
		$fields = explode(';', $this->args->fields);
		$url = $this->args->url;
		$pagination = true;
		$page = 1;
		if (isset($this->args->page_id)) {
			$pos = strpos($this->args->url, "?");
			$sym = ($pos === false) ? "?" : "&";
			$url .= "$sym{$this->args->page_id}=$page";
		}
		$out = array();
		while (($reply = filter_var(str_replace(' ', '%20', $url), FILTER_VALIDATE_URL) ? $this->curl->xmlConnect($url) : file_get_contents($url)) !== false && $pagination) {
			$reply = json_decode($reply);
			foreach ($fields as $field) {
				if (!isset($reply->$field)) {
					$reply = false;
					break;
				}
				$reply = $reply->$field;
			}
			if ($reply === false)
				break;
			foreach ($reply as $entry) {
				$link_id = is_array($entry) ? $entry[$this->args->link_id] : $entry->{$this->args->link_id};
				$name_id = is_array($entry) ? $entry[$this->args->name_id] : $entry->{$this->args->name_id};
				if (isset($this->args->coordinates_id))
					$coordinates_id = is_array($entry) ? $entry[$this->args->coordinates_id] : $entry->{$this->args->coordinates_id};
				$link = $this->args->base_url . '/' . $link_id;
				$out[$link] = new stdClass;
				$out[$link]->title = $name_id;
				if (isset($this->args->coordinates_id) && isset($coordinates_id))
					$out[$link]->coordinates = $coordinates_id;
			}
			$page++;
			$pagination = isset($this->args->page_id);
			$url = $pagination ? $this->args->url . "&{$this->args->page_id}=$page" : $this->args->url;
		}
		return $out;
	}

	/**
	 * Reads the memory limit allocated by php and converts it to bytes.
	 *
	 * @param void
	 *
	 * @return integer The memory limit in bytes.
	 */
	public function memory_limit() {
		$size_str = ini_get('memory_limit');
		switch (substr ($size_str, -1)) {
			case 'M': case 'm': return (int)$size_str * 1048576;
			case 'K': case 'k': return (int)$size_str * 1024;
			case 'G': case 'g': return (int)$size_str * 1073741824;
			default: return $size_str;
		}
	}
}