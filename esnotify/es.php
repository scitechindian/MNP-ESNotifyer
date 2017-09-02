<?php

class elasticSearch
{
	private $search_host = '10.99.0.201';
//	private $search_host_main = '10.99.0.201';
	private $search_port = '9200';
	private $search_id   = 1;

	private function buildURL($config)
	{
//		if ($this->search_id === 1) {
		$host = $this->search_host;
//			$this->search_id++;
//		}
//		if ($this->search_id === 2) {
//			$host = $this->search_host_main;
//			$this->search_id--;
//		}
		$url = 'http://' . $host . ':' . $this->search_port . '/';
		if (isset($config['ES_coin'])) {
			$url .= $config['ES_coin'] . '/';
			if (isset($config['ES_type'])) {
				$url .= $config['ES_type'] . '/';
				if (isset($config['ES_id'])) {
					$url .= $config['ES_id'] . '';
				}
			}
		}
		return $url;
	}


	function esBULK($process, $config)
	{
		$b    = array();
		$sets = array();
		foreach ($process as $key => $value) {
			$params = array(
				'_id'    => $value['config']['ES_id'],
				'_index' => $value['config']['ES_coin'],
				'_type'  => $value['config']['ES_type']
			);
			$doc    = $value['post'];
			$set    = array(
				array('update' => $params),
				$doc
			);
			$sets[] = $set;
		}

		foreach ($sets as $set) {
			foreach ($set as $s) {
				$b[] = json_encode($s);
			}
		}
		$body = join("\n", $b) . "\n";
		$baseUri  = $this->buildURL($config) . '_bulk';

		$ci = curl_init();
		curl_setopt($ci, CURLOPT_URL, $baseUri);
		curl_setopt($ci, CURLOPT_PORT, $this->search_port);
		curl_setopt($ci, CURLOPT_TIMEOUT, 200);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
		if (is_array($body) && count($body) > 0) {
			curl_setopt($ci, CURLOPT_POSTFIELDS, json_encode($body));
		} else {
			curl_setopt($ci, CURLOPT_POSTFIELDS, $body);
		}
		$response = curl_exec($ci);
		return $response;
	}


	function esPUT($process, $config)
	{
		$json_doc = json_encode($process, JSON_PRETTY_PRINT);
		$baseUri  = $this->buildURL($config);

		$ci = curl_init();
		curl_setopt($ci, CURLOPT_URL, $baseUri);
		curl_setopt($ci, CURLOPT_PORT, $this->search_port);
		curl_setopt($ci, CURLOPT_TIMEOUT, 200);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ci, CURLOPT_POSTFIELDS, $json_doc);
		$response = curl_exec($ci);

		return $response;
	}

	function esSEARCH($process, $config, $type = 'source')
	{
		$json_doc = json_encode($process, JSON_PRETTY_PRINT);
		$baseUri  = $this->buildURL($config) . '_search';
		$ci       = curl_init();
		curl_setopt($ci, CURLOPT_URL, $baseUri);
		curl_setopt($ci, CURLOPT_PORT, $this->search_port);
		curl_setopt($ci, CURLOPT_TIMEOUT, 200);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ci, CURLOPT_POSTFIELDS, $json_doc);
		$response = curl_exec($ci);
		$res      = json_decode($response, true);
		if ($type === 'source' && isset($res['hits']) && isset($res['hits']['hits'])) {
			$return = json_encode($res['hits']['hits']);
			return $return;
		} else {
			return $response;
		}
	}

	function esGET($config)
	{
		$baseUri = $this->buildURL($config);

		$ci = curl_init();
		curl_setopt($ci, CURLOPT_URL, $baseUri);
		curl_setopt($ci, CURLOPT_PORT, $this->search_port);
		curl_setopt($ci, CURLOPT_TIMEOUT, 200);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
		$response = curl_exec($ci);
		$res      = json_decode($response, true);
		if (isset($res['_source'])) {
			$return = json_encode($res['_source']);
			return $return;
		}
	}

	function esUPDATE($process, $config)
	{
		$json_doc = json_encode($process, JSON_PRETTY_PRINT);
		$baseUri  = $this->buildURL($config) . '/_update';

		$ci = curl_init();
		curl_setopt($ci, CURLOPT_URL, $baseUri);
		curl_setopt($ci, CURLOPT_PORT, $this->search_port);
		curl_setopt($ci, CURLOPT_TIMEOUT, 200);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
		curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ci, CURLOPT_POSTFIELDS, $json_doc);
		$response = curl_exec($ci);

		return $response;
	}
}

$es = new elasticSearch;