<?php
include_once('CoinStats.php');

class coin
{
	public  $coin     = 'pie';
	public  $ip       = '';
	public  $port     = '';
	public  $username = '';
	public  $password = '';
	public  $coinData = [];
	private $stats;

	function __construct()
	{
		$data                               = json_decode(file_get_contents(dirname(__FILE__) . '/coins/' . $this->coin . '.json'), true);
		$this->ip                           = $data['ip'];
		$this->port                         = $data['port'];
		$this->username                     = $data['username'];
		$this->password                     = $data['password'];
		$this->stats                        = new CoinStats();
		$cData['coin']                      = $this->coin;
		$cData['masterNodeRewardPercent']   = 50;
		$cData['masterNodeCoinRequired']    = 25000;
		$cData['walletCLI']                 = 'PieCoind';
		$cData['logo']                      = 'https://files.coinmarketcap.com/static/img/coins/32x32/piecoin.png';
		$cData['createAMasterNodeURL']      = 'http://www.piecoin.info/';
		$cData['createAMasterNodeURLTitle'] = 'piecoin.info';
		$cData['donate']                    = "icUZiVjSaSyJx4oz9oBD4RqmHfmFYoDSBF";
		$cData['blockSeconds']              = 60;
		$cData['google']                    = 90;

		$this->coinData = $cData;
	}

	private function getCoinMarketCapData($es)
	{
		$config['ES_coin']                    = $this->coin;
		$config['ES_type']                    = 'coinmarketcap';
		$search['size']                       = '1';
		$search['sort'][0]['height']['order'] = 'desc';
		$mnData                               = json_decode($es->esSEARCH($search, $config), true);
		return $mnData[0]['_source'];
	}

	private function reward($height)
	{
		if ($height <= 125146) {
			$ret['height']     = 125146;
			$ret['reward']     = 23;
			$ret['nextreward'] = 17;
		} elseif ($height <= 568622) {
			$ret['height']     = 568622;
			$ret['reward']     = 17;
			$ret['nextreward'] = 11.5;
		} elseif ($height <= 1012098) {
			$ret['height']     = 1012098;
			$ret['reward']     = 11.5;
			$ret['nextreward'] = 5.75;
		} elseif ($height <= 1455574) {
			$ret['height']     = 1455574;
			$ret['reward']     = 5.75;
			$ret['nextreward'] = 1.85;
		} elseif ($height <= 3675950) {
			$ret['height']     = 3675950;
			$ret['reward']     = 1.85;
			$ret['nextreward'] = 0.2;
		} else {
			$ret['height']     = 20000000;
			$ret['reward']     = 0.2;
			$ret['nextreward'] = "N/A";
		}
		$ret['currentHeight']              = $height;
		$ret['masterNodeReward']           = $ret['reward'] / (100 / $this->coinData['masterNodeRewardPercent']);
		$ret['stakeReward']                = $ret['reward'] - $ret['masterNodeReward'];
		$ret['masterNodeRewardNextReward'] = $ret['nextreward'] / (100 / $this->coinData['masterNodeRewardPercent']);
		$ret['stakeRewardNextReward']      = $ret['nextreward'] - $ret['masterNodeRewardNextReward'];
		return $ret;
	}

	private function statsUpdate($es, $height, $masterNodeCount, $fullMNLList)
	{
		$cmcData = $this->getCoinMarketCapData($es);
		$this->stats->runStats($es, $cmcData, $this->coinData, $this->reward($height), $masterNodeCount, $fullMNLList);
	}

	public function masternodelist($wallet)
	{
		return $wallet->masternodelist('full');
	}

	public function MNLParse($array, $argv, $es, $getInfo)
	{
		$new         = $enabled = $other = 0;
		$fullMNLList = $MNLBulk = [];
		if (count($array) > 0) {
			foreach ($array as $key => $value) {
				$data = $this->mnldata($key, $value);
//				if (substr($getInfo['blocks'], -1) === '5' || substr($getInfo['blocks'], -1) === '0') {
				$config['ES_coin'] = $argv[1];
				$config['ES_type'] = 'mn';
				$config['ES_id']   = $data['addr'];
				$mnData            = json_decode($es->esGET($config), true);
				if (!isset($mnData)) {
					try {
						$baseUri = 'http://freegeoip.net/json/' . $data['ip'];
						$ci      = curl_init();
						curl_setopt($ci, CURLOPT_URL, $baseUri);
						curl_setopt($ci, CURLOPT_TIMEOUT, 200);
						curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
						curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'GET');
						$response       = curl_exec($ci);
						$data['ipData'] = json_decode($response, true);
						$new++;
					}
					catch (\Exception $e) {
					}
				} else {
					$mnlData                 = [];
					$mnlData['addr']         = $mnData['addr'];
					$mnlData['status']       = $mnData['status'];
					$mnlData['latitude']     = $mnData['ipData']['latitude'];
					$mnlData['longitude']    = $mnData['ipData']['longitude'];
					$mnlData['country_code'] = $mnData['ipData']['country_code'];
					$mnlData['country_name'] = $mnData['ipData']['country_name'];
					$fullMNLList[]           = $mnlData;
				}
				$mnl['config']                                  = $config;
				$mnl['post']['script']['inline']                = 'ctx._source.status = params.status; ctx._source.lastchecked = params.lastchecked';
				$mnl['post']['script']['lang']                  = 'painless';
				$mnl['post']['script']['params']['status']      = $data['status'];
				$mnl['post']['script']['params']['lastchecked'] = $data['lastchecked'];
				$mnl['post']['upsert']                          = $data;
				if ($data['status'] === 'ENABLED') {
					$enabled++;
				} else {
					$other++;
				}
				$MNLBulk[] = $mnl;
			}
		}
//		if (substr($getInfo['blocks'], -1) === '5' || substr($getInfo['blocks'], -1) === '0') {
		$configBULK['ES_coin'] = $argv[1];
		$configBULK['ES_type'] = 'mn';
		$es->esBULK($MNLBulk, $configBULK);
		$configMNL['ES_coin'] = $argv[1];
		$configMNL['ES_type'] = 'mnl';
		$configMNL['ES_id']   = 1;
		$MNLData['list']      = $fullMNLList;
		$es->esPUT($MNLData, $configMNL);
		$configMNL['ES_coin'] = $argv[1];
		$configMNL['ES_type'] = 'mnlcountry';
		$configMNL['ES_id']   = 1;
		$es->esPUT($this->masterNodeListData($fullMNLList), $configMNL);
//		}
		$mnt['enabled'] = $enabled;
		$mnt['new']     = $new;
		$mnt['other']   = $other;
		$this->statsUpdate($es, $getInfo['blocks'], $mnt, $fullMNLList);
	}

	private function masterNodeListData($mnl)
	{
		$list = $nclist = $sortlist = [];
		foreach ($mnl as $eachmnl) {
			$list[] = $eachmnl;
		}
		foreach ($list as $value) {
			if (isset($value['country_code'])) {
				$nclist[$value['country_code']]['data'][] = $value;
			}
		}
		foreach ($nclist as $key => $value) {
			$nclist[$key]['count']                            = count($value['data']);
			$sortlist[$nclist[$key]['count']]['country_name'] = $value['data'][0]['country_name'];
			$sortlist[$nclist[$key]['count']]['count']        = number_format((($nclist[$key]['count'] / count($list)) * 100), '0', '.', '');
			$sortlist[$nclist[$key]['count']]['countb']       = 100 - $sortlist[$nclist[$key]['count']]['count'];
		}
		(count($sortlist) > 0) ? krsort($sortlist) : $sortlist;
		$data['sortlist'] = $sortlist;
		return $data;
	}

	public function mnldata($key, $value)
	{
		$split                = explode(" ", ltrim(rtrim(trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $value))))));
		$data['status']       = $split[0];
		$data['addr']         = $split[2];
		$data['lastchecked']  = time();
		$data['total']        = 0;
		$data['transactions'] = [];
		$splita               = explode(":", ltrim(rtrim($split[3])));
		if (count($splita) > 2) {
			$splita         = explode("]:", $split[3]);
			$data['iptype'] = 'ipv6';
			$data['ip']     = str_replace("]", "", str_replace("[", "", $splita[0]));
			$data['port']   = $splita[1];
		} else {
			$data['iptype'] = 'ipv4';
			$data['ip']     = $splita[0];
			$data['port']   = $splita[1];
		}
		return $data;
	}

	public function vin($process, $vout, $config, $es, $value, $mainProcess)
	{
		foreach ($process as $vinKey => $vin) {
			if (isset($vin['txid'])) {
				$config['ES_type'] = 'tx';
				$config['ES_id']   = $vin['txid'];
				$vinData           = json_decode($es->esGET($config), true);
				$vinOut            = $vinData['vout'][$vin['vout']];
				if (isset($vinOut['scriptPubKey']['addresses'])) {
					$vInUpdate                              = $txids = [];
					$config['ES_type']                      = 'address';
					$config['ES_id']                        = $vinOut['scriptPubKey']['addresses'][0];
					$txids['txid']                          = $value . "-out";
					$txids['value']                         = -$vinOut['value'];
					$txids['type']                          = 'out';
					$txids['vin']                           = $vinOut;
					$vInUpdate['script']['inline']          = 'ctx._source.txids.add(params.txids); ctx._source.total = ctx._source.total - params.value';
					$vInUpdate['script']['lang']            = 'painless';
					$vInUpdate['script']['params']['txids'] = $txids;
					$vInUpdate['script']['params']['value'] = $vinOut['value'];
					$vInUpdate['upsert']['address']         = $vinOut['scriptPubKey']['addresses'][0];
					$vInUpdate['upsert']['total']           = $vinOut['value'];
					$vInUpdate['upsert']['txids'][0]        = $txids;
					$checkForDuplicates                     = json_decode($es->esGET($config), true);
					$runUpdate                              = true;
					foreach ($checkForDuplicates['txids'] as $eachNode) {
						if ($eachNode['txid'] === $txids['txid']) {
							$runUpdate = false;
						}
					}
					$runUpdate ? $es->esUPDATE($vInUpdate, $config) : '';
				}
			}
		}
	}

	public function vout($vin, $process, $config, $es, $value, $mainProcess)
	{
		$mint      = false;
		$mintTotal = 0;
		foreach ($process as $voutKey => $vout) {
			if (isset($vout['scriptPubKey']['type']) && $vout['scriptPubKey']['type'] === 'nonstandard') {
				$mint      = true;
				$mintTotal = $mainProcess['mint']; // NOTE: Not Always True
			}
			if (isset($vout['scriptPubKey']['addresses'])) {
				$vOutUpdate         = $txids = [];
				$config['ES_type']  = 'address';
				$config['ES_id']    = $vout['scriptPubKey']['addresses'][0];
				$txids['txid']      = $value;
				$txids['timestamp'] = time();
				$txids['value']     = $vout['value'];
				$txids['type']      = 'in';
				$txids['subType']   = 'none';
				if ($mint) {
					if ($vout['scriptPubKey']['type'] === 'pubkey') {
						$txids['subType'] = 'stake';
					}
					if ($vout['scriptPubKey']['type'] === 'pubkeyhash') {
						$mnConfig            = $config;
						$mnConfig['ES_type'] = 'mn';
						$mnConfig['ES_id']   = $vout['scriptPubKey']['addresses'][0];
						$mnData              = json_decode($es->esGET($mnConfig), true);
						if (isset($mnData)) {
							$txids['subType']                  = 'MasterNode';
							$mnTx['script']['inline']          = 'ctx._source.transactions.add(params.txids); ctx._source.total = ctx._source.total + params.value';
							$mnTx['script']['lang']            = 'painless';
							$mnTx['script']['params']['txids'] = $txids;
							$mnTx['script']['params']['value'] = $vout['value'];
							$es->esUPDATE($mnTx, $mnConfig);
						}
					}
				}
				$txids['vout']                           = $vout;
				$vOutUpdate['script']['inline']          = 'ctx._source.txids.add(params.txids); ctx._source.total = ctx._source.total + params.value';
				$vOutUpdate['script']['lang']            = 'painless';
				$vOutUpdate['script']['params']['txids'] = $txids;
				$vOutUpdate['script']['params']['value'] = $vout['value'];
				$vOutUpdate['upsert']['address']         = $vout['scriptPubKey']['addresses'][0];
				$vOutUpdate['upsert']['total']           = (float)$vout['value'];
				$vOutUpdate['upsert']['txids'][0]        = $txids;
				$checkForDuplicates                      = json_decode($es->esGET($config), true);
				$runUpdate                               = true;
				if (isset($checkForDuplicates)) {
					foreach ($checkForDuplicates['txids'] as $eachNode) {
						if ($eachNode['txid'] === $txids['txid']) {
							$runUpdate = false;
						}
					}
				}
				$runUpdate ? $es->esUPDATE($vOutUpdate, $config) : '';
			}
		}
	}
}

$coin = new coin;