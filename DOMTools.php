<?php

class DOMTools {
	public $dom;

	public function __construct($html) {
		$config_tidy = array(
			'indent' => true,
			'output-xhtml' => true,
			'wrap' => 200,
		);

		if (class_exists('tidy')) {
			$tidy = new tidy;
			$tidy->parseString($html, $config_tidy, 'utf8');
			$tidy->cleanRepair();
		} else {
			$tidy = $html;
		}

		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML('<?xml encoding="utf-8"?>' . $tidy);
		libxml_clear_errors();
		$this->dom = $dom;
	}

	protected function findTag($tags, $node=null) {
		$xpath = new DOMXPath($this->dom);
		$nodes = array();
		foreach ($tags as $tag) {
			$times_back = $this->mb_str_first($tag, ':');
			$subtag = mb_substr($tag, $times_back);
			$queries = array(
				'.//text()[contains(.,"'.$subtag.'")]/parent::*[1]',
				'.//*[contains(concat(" ", normalize-space(@class), " "), " '.$subtag.' ")]',
				'.//*[contains(concat(" ", normalize-space(@id), " "), " '.$subtag.' ")]',
				'.//@*[contains(.,"'.$subtag.'")]/parent::*[1]',
			);
			$found = false;
			foreach ($queries as $query) {
				$elements = !isset($node) ? $xpath->query($query) : $xpath->query($query, $node);
				$nodes[$tag] = array();
				foreach ($elements as $elem) {
					$nodes[$tag][] = $elem;
				}
				if ($elements !== false && $elements->length != 0) {
					$found = true;
					break;
				}
			}
			if (!$found)
				$nodes[$tag] = false;
		}
		return $nodes;
	}

	public function constructList($tags) {
		$tags = explode(";", $tags);
		$parents = $this->findTag($tags);
		$values = array();
		foreach ($parents as $tag => $parents) {
			foreach ($parents as $elem) {
				$link = trim($elem->getAttribute("href"));
				if (empty($link))
					continue;
				$values[$link] = new stdClass;
				$values[$link]->url = $link;
				$name = $this->findTag(['name'], $elem)['name'][0];
				$values[$link]->name = is_object($name) ? trim($name->nodeValue) : '';
			}
		}
		return $values;
	}

	public function harvestData($tags) {
		$parents = $this->findTag($tags);

		$data = array();
		foreach ($parents as $tag => $elem) {
			$times_back = $this->mb_str_first($tag, ':');
			for ($i =0; $i < $times_back; $i++) {
				$elem = $elem->previousSibling;
			}
			$subtag = mb_substr($tag, $times_back);
			$data[$tag] = array();
			if ($elem === false) {
				$data[$tag][] = '';
				continue;
			}
			if (is_array($elem))
				$elem = $elem[0];
			if (!is_object($elem)) {
				$data[$tag][] = $value;
				continue;
			}
			$value = $this->clearStr($elem->nodeValue);
			$parent_name = $elem->nodeName;
			if (!empty($value) && mb_strpos($value, $subtag) === false) {
				$data[$tag][] = $value;
				continue;
			}
			$child = $elem;
			while ($child) {
				$value = $this->clearStr($child->nodeValue);
				// if (mb_strpos($value, $subtag) === false)
				// 	break;
				if (!empty($value) && $value != $subtag)
					$data[$tag][] = $value;
				$child = $child->nextSibling;
				if (
					$child
					&& (
						($child->nodeName == $parent_name)
						|| ($child->hasChildNodes() && $child->firstChild->nodeName == $parent_name)
					)
				)
					break;
			}
		}
		return $data;
	}

	private function clearStr($str) {
		$symbols = [',', ':', '?', ';', ':', '-'];
		$str = trim($str);
		while (array_search(mb_substr($str, 0, 1), $symbols)!==false) {
			$str = mb_substr($str, 1);
			$str = trim($str);
		}
		$str = str_replace("\n", ' | ', $str);
		$str = preg_replace('/(?:[\ ]{2,})+/', " ", $str);
		$str = preg_replace('/(?:(?:[\ ])?\|(?:[\ ])?){2,}/', " | ", $str);
		return $str;
	}

	private function mb_str_first($haystack, $needle) {
		$counter = 0;
		while ((mb_substr($haystack, 0, 1)) === $needle) {
			$haystack = mb_substr($haystack, mb_strlen($needle));
			$counter++;
		}
		return $counter;
	}

}