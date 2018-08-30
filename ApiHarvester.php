<?php

require_once "Harvester.php";

/**
 * A generic API harvester class
 */
class ApiHarvester extends Harvester
{

	public function fetchData($list) {
		if (empty($this->args->container)) {
			$keys = array_keys($list);
		} else {
			$keys = $list[$this->args->container];
		}
		$urls = array_map([$this, 'key2url'], $keys);
		$this->list = $list;
		// $this->data = $urls;
		return $this->curl->multi_connect($this->curl->xml_opts, $urls, [$this, 'getInfo']);
	}

	public function key2url($key) {
		$url = $this->args->base_url . '?' . $this->args->identifier . '=' . $key;
		if (isset($this->args->other_params))
			$url .= $this->args->other_params;
		return $url;
	}

	public function getInfo($content) {
		if (!empty($content)) {
			if (is_array($content)) {
				switch ($this->filetype) {
					case 'geojson':
						foreach ($content as $array) {
							if (is_object($array))
								$array = (array)$array;
							$properties = $this->array_flatten($array);
							$point = new \GeoJson\Geometry\Point([$properties[$this->args->lon], $properties[$this->args->lat]]);
							$feature = new \GeoJson\Feature\Feature($point, $properties);
							$this->data[] = $feature;
						}
						break;
					case 'csv':
						foreach ($content as $array) {
							if (is_object($array))
								$array = (array)$array;
							$flattened = $this->array_flatten($array);
							$this->data[] = $flattened;
							if (!isset($this->headers)) {
								fputcsv($this->file_handle, array_keys($flattened));
								$this->headers = true;
							}
							fputcsv($this->file_handle, $flattened);
						}
						break;

					default:
						foreach ($content as $obj) {
							$this->data[] = $obj;
						}
						break;
				}
			} else {
				$this->data[] = $content;
			}
		}
	}

	public function collect() {
		if (is_object($this->args->list_url)) {
			$list = (array)$this->args->list_url;
		} else {
			$list = $this->curl->xmlConnect($this->args->list_url);
			$list = json_decode($list, true);
		}
		return $list;
	}
}