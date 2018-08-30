<?php
class Autoloader
{
		public static function register()
		{
				spl_autoload_register(function ($class) {
						$file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
						if (file_exists($file)) {
								require $file;
								return true;
						}
						return false;
				});
		}
}
Autoloader::register();

class Harvester {
	public $args, $data, $curl, $list, $filename, $filetype, $file_handle, $curl_opts;

	public function __construct($args) {
		$this->args = $args;
		$this->curl = new CurlTools();
		$this->filename = $args->filename;
		$this->filetype = $args->filetype;
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
		switch ($this->filetype) {
			case 'csv':
				$this->file_handle = fopen($args->filename, 'w');
				break;

			case 'json':
				$this->data = array();
				break;
		}
	}

	public function __destruct() {
		$size = sizeof($this->data);
		switch ($this->filetype) {
			case 'csv':
				fclose($this->file_handle);
				break;

			case 'geojson':
			case 'json':
				echo $size . PHP_EOL;
				if ($this->filetype == 'geojson')
					$this->data = new \GeoJson\Feature\FeatureCollection($this->data);
				$data = json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
				file_put_contents($this->filename, $data);
				break;
		}
		$executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		$message = "The harvesting processes of {$this->args->base_url} just completed, after $executionTime s.\nThe output file '{$this->args->filename}' has been written on disk with $size results.";
		if (!empty($this->args->email)) {
			$success = mail ( $this->args->email , 'Harvesting completed' , $message );
		}
	}

	public function array_flatten(array $array, string $pre='') {
		$result = [];
		$pre = !empty($pre) ? "$pre." : '';
		foreach ($array as $key => $value) {
			if (is_object($value)) {
				$value = (array)$value;
				$result = array_merge($result, $this->array_flatten($value, $pre . $key));
			} else if (is_array($value)) {
				$result[$pre . $key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			} else {
				$result[$pre . $key] = $value;
			}
		}
		return $result;

		function isAssoc(array $arr) {
			if (array() === $arr) return false;
			return array_keys($arr) !== range(0, count($arr) - 1);
		}
	}

}