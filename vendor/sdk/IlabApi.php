<?php
namespace vendor\sdk;

class IlabApi
{
	protected static $appName = '基于症状学的临床技能和临床思维虚拟实训教程';
	protected static $issuerId = 100033;
	
	public static function log($ilabUserName, $case, $isFinished, $score, $started_at, $ended_at, $time)
	{
		$data = [
				'username' => $ilabUserName,
				'projectTitle' => self::$appName,
				'childProjectTitle' => $case,	//id-name-alias
				'status' => $isFinished ? 1 : 2,
				'score' => (int)$score,
				'startDate' => $started_at * 1000,
				'endDate' => $ended_at * 1000,
				'timeUsed' => round($time / 60) ?: 1,
				'issuerId' => (string)self::$issuerId,
		];
		
		# 1
		$params = [
				'xjwt' => IlabJwt::getJwt($data),
		];
		
		$result = IlabClient::sendRequest('POST', 'project/log/upload', $params, [], '');
		
		//log info
		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		$params = json_encode($params, JSON_UNESCAPED_UNICODE);
		if (!$result) {
			IlabJwt::log("POST project/log/upload failed. Data(json):{$data}, Params(json):{$params}");
			return false;
		} else {
			IlabJwt::log("POST project/log/upload succeeded. Data(json):{$data}, Params:{$params}, Result:{$result}");
			$result = json_decode($result, true);
			return $result['code'] == 0;
		}
		
// 		# 2
// 		$params = [
// 				'xjwt' => urlencode(IlabJwt::getJwt($data)),
// 		];
		
// 		$result = IlabClient::sendRequest('POST', 'project/log/upload', $params, [], '');
		
// 		//log info
// 		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
// 		$params = json_encode($params, JSON_UNESCAPED_UNICODE);
// 		if (!$result) {
// 			IlabJwt::log("POST project/log/upload failed. Data(json):{$data}, Params(json):{$params}");
// 			// 			return false;
// 		} else {
// 			IlabJwt::log("POST project/log/upload succeeded. Data(json):{$data}, Params:{$params}, Result:{$result}");
// 			$result = json_decode($result, true);
// 			// 			return $result['code'] == 0;
// 		}
		
// 		# 3
// 		$params = [
// 				'xjwt' => urlencode(urlencode(IlabJwt::getJwt($data))),
// 		];
		
// 		$result = IlabClient::sendRequest('POST', 'project/log/upload', $params, [], '');
		
// 		//log info
// 		$data = json_encode($data, JSON_UNESCAPED_UNICODE);
// 		$params = json_encode($params, JSON_UNESCAPED_UNICODE);
// 		if (!$result) {
// 			IlabJwt::log("POST project/log/upload failed. Data(json):{$data}, Params(json):{$params}");
// 			// 			return false;
// 		} else {
// 			IlabJwt::log("POST project/log/upload succeeded. Data(json):{$data}, Params:{$params}, Result:{$result}");
// 			$result = json_decode($result, true);
// 			// 			return $result['code'] == 0;
// 		}
	}
}