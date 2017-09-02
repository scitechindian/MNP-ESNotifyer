<?php

class CoinStats
{
	public function runStats($es, $cmcData, $coinData, $rewardData, $masterNodeCount, $fullMNLList)
	{
		$data                          = [];
		$data['time']                  = time();
		$data['coinData']              = $coinData;
		$data['cmcData']               = $cmcData;
		$price['price_btc']            = (float)$cmcData['price_btc'];
		$price['price_usd']            = (float)$cmcData['price_usd'];
		$price['price_gbp']            = (float)$cmcData['price_gbp'];
		$price['price_aud']            = (float)$cmcData['price_aud'];
		$price['price_cad']            = (float)$cmcData['price_cad'];
		$price['price_cny']            = (float)$cmcData['price_cny'];
		$price['price_rub']            = (float)$cmcData['price_rub'];
		$data['price']                 = $price;
		$data['blocksToday']           = $this->blocksDay($es, $coinData['coin'], 0);
		$last24Hours['blocks']         = $this->blocksLast24Hours($es, strtolower($coinData['coin']));
		$last24Hours['rewards']        = $last24Hours['blocks'] * $rewardData['masterNodeReward'];
		$last24Hours['values']         = $this->getValues($data['price'], $last24Hours['rewards'], $coinData);
		$last24Hours['blockTimes']     = ($last24Hours['blocks'] > 0 || $last24Hours['blocks'] > 0) ? 86400 / $last24Hours['blocks'] : 0;
		$perNode['blocks']             = ($last24Hours['blocks'] > 0 || $masterNodeCount['enabled'] > 0) ? $last24Hours['blocks'] / $masterNodeCount['enabled'] : 0;
		$perNode['rewards']            = ($last24Hours['blocks'] > 0 || $masterNodeCount['enabled'] > 0) ? $last24Hours['rewards'] / $masterNodeCount['enabled'] : 0;
		$perNode['values']             = $this->getValues($data['price'], ($last24Hours['rewards'] / $masterNodeCount['enabled']), $coinData);
		$last24Hours['rewardFreq']     = ($perNode['blocks'] > 0) ? 24 / $perNode['blocks'] : 0;
		$last24Hours['perNode']        = $perNode;
		$data['last24Hours']           = $last24Hours;
		$data['coinLocked']['total']   = $masterNodeCount['enabled'] * $coinData['masterNodeCoinRequired'];
		$data['coinLocked']['percent'] = 0.00;
		$data['masterNodeCount']       = $masterNodeCount;
		$data['lastUpdated']           = date('F j, Y, g:i a T', $data['time']);
		$rewardData['timeTillDrop']    = $this->rewardDropDays($rewardData, $last24Hours['blockTimes']);
		$data['rewardData']            = $rewardData;
		$bd                            = 0;
		$bspec                         = (86400 / $coinData['blockSeconds']);
		while ($bd <= 6) {
			$count                                = 0;
			$count                                = $this->blocksDay($es, $coinData['coin'], $bd);
			$data['blockdetails'][$bd]['count']   = $count;
			$data['blockdetails'][$bd]['percent'] = number_format((($count / $bspec) * 100), '0', '.', '');
			$bd++;
		}
		$config['ES_coin'] = strtolower($coinData['coin']);
		$config['ES_type'] = 'basestats';
		$config['ES_id']   = $data['time'];
		$es->esPUT($data, $config);
	}

	private function getValues($price, $coindaily, $coinData)
	{
		$data = [];
		foreach ($price as $key => $value) {
			$total                        = ($coindaily * $value);
			$data[$key]['masterNodeCost'] = (float)$value * $coinData['masterNodeCoinRequired'];
			$data[$key]['daily']          = (float)$total;
			$data[$key]['weekly']         = (float)$total * 7;
			$data[$key]['monthly']        = (float)$total * 30.42;
			$data[$key]['yearly']         = (float)$total * 365;
			$data[$key]['roi']            = (float)($data[$key]['yearly'] / $data[$key]['masterNodeCost']) * 100;
		}
		return $data;
	}

	private function rewardDropDays($rewardData, $avgBlockTime)
	{
		$blockleft   = $rewardData['height'] - $rewardData['currentHeight'];
		$sectilldrop = $blockleft * $avgBlockTime;
		$total       = $this->calculate_time_span($sectilldrop);
		if ($total['num'] < 0) {
			$total['num']  = 'N/A';
			$total['name'] = 'TIME';
		}
		return $total;
	}

	private function calculate_time_span($seconds)
	{
		$years  = floor($seconds / (3600 * 24 * 30));
		$months = floor($seconds / (3600 * 24 * 30));
		$day    = floor($seconds / (3600 * 24));
		$hours  = floor($seconds / 3600);
		$mins   = floor(($seconds - ($hours * 3600)) / 60);
		$secs   = floor($seconds % 60);
		if ($seconds < 60) {
			$ret['num']  = $secs;
			$ret['name'] = 'sec';
		} else if ($seconds < 60 * 60) {
			$ret['num']  = $mins;
			$ret['name'] = 'min';
		} else if ($seconds < 24 * 60 * 60) {
			$ret['num']  = $hours;
			$ret['name'] = 'hours';
		} else if ($seconds < 24 * 60 * 60) {
			$ret['num']  = $day;
			$ret['name'] = 'days';
		} else {
			$ret['num']  = $months;
			$ret['name'] = 'months';
		}
		return $ret;
	}

	private function blocksDay($es, $coin, $day)
	{
		$searchConfig['ES_coin']                 = $coin;
		$searchConfig['ES_type']                 = 'block';
		$search['sort'][0]['height']['order']    = 'desc';
		$search['query']['range']['time']['gte'] = strtotime('-' . $day . ' day midnight');
		$search['query']['range']['time']['lte'] = strtotime('tomorrow', $search['query']['range']['time']['gte']);
		$mnData                                  = json_decode($es->esSEARCH($search, $searchConfig, 'full'), true);
		return $mnData['hits']['total'];
	}

	private function blocksLast24Hours($es, $coin)
	{
		$searchConfig['ES_coin']                 = $coin;
		$searchConfig['ES_type']                 = 'block';
		$search['sort'][0]['height']['order']    = 'desc';
		$search['query']['range']['time']['gte'] = strtotime('-1 day');
		$mnData                                  = json_decode($es->esSEARCH($search, $searchConfig, 'full'), true);
		return $mnData['hits']['total'];
	}
}