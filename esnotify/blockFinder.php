<?php
//error_reporting(0);
$coin_config = $argv[1] . '.php';
require_once('jsonRPCClient.php');
require_once('es.php');
require_once($coin_config);

$jsonrpcurl = 'http://' . $coin->username . ':' . $coin->password . '@' . $coin->ip . ':' . $coin->port . '/';
$wallet     = new jsonRPCClient($jsonrpcurl);

if (isset($wallet)) {
	$config['ES_coin'] = $argv[1];
	$getInfo           = $wallet->getinfo();
	$config['ES_type'] = 'getinfo';
	$config['ES_id']   = '1';
	$es->esPUT($getInfo, $config);
	$searchConfig['ES_coin'] = $argv[1];
	$searchConfig['ES_type'] = 'block';
	if (is_numeric($argv[2])) {
		$hash                  = $wallet->getblockhash((int)$argv[2]);
		$searchConfig['ES_id'] = (int)$argv[2];
		$gotItData             = json_decode($es->esGET($searchConfig), true);
	}
	$processBlock = true;
	if ($gotItData['height'] === (int)$argv[2]) {
		$processBlock = false;
	}
	if ($processBlock === true) {
		$process           = $wallet->getblock($hash);
		$config['ES_type'] = 'block';
		$config['ES_id']   = $process['height'];
		echo "Block Height: " . $process['height'] . "\r\n";
		$es->esPUT($process, $config);
	} else {
		echo "Got It " . $argv[2] . "\r\n";
	}
	if (isset($process['tx'])) {
		foreach ($process['tx'] as $key => $value) {
			$tcProcess = [];
			try {
				$transactionHash   = $wallet->getrawtransaction($value);
				$tcProcess         = $wallet->decoderawtransaction($transactionHash);
				$config['ES_type'] = 'tx';
				$config['ES_id']   = $value;
				$es->esPUT($tcProcess, $config);
				if (isset($tcProcess['vin'])) {
					$coin->vin($tcProcess['vin'],$tcProcess['vout'], $config, $es, $value, $process);
				}
				if (isset($tcProcess['vout'])) {
					$coin->vout($tcProcess['vin'],$tcProcess['vout'], $config, $es, $value, $process);
				}
			}
			catch (Exception $e) {
			}
		}
	}
	if (isset($process['height']) && substr($process['height'], -4) == '0000') {
		try {
			$masternodelist = $coin->masternodelist($wallet, $es);
			echo "MasterNodes Check COUNT: " . count($masternodelist) . "\r\n";
			$coin->MNLParse($masternodelist, $argv, $es);
		}
		catch (Exception $e) {
		}
	}
}
