<?php
/**
 * All rights reserved
 */
namespace vendor\sdk;

class ApiClient
{
	const API_VERSION = '1.0';
	const USER_AGENT = 'php-sdk-v-1.0';
	const HEADER_PREFIX = 'x-mg-';
	
	const URL_PREFIX = 'http://';
	
	/**
	 * @var string accessKey
	 */
	protected $accessKey;
	
	/**
	 * @var string accessKeyId
	 */
	protected $accessSecret;
	
	/**
	 * @var string sever host.
	 */
	protected $serverHost;
	
	public function __construct($accessKey, $accessSecret, $serverHost) 
	{
		date_default_timezone_set('Asia/Shanghai');
		
		$this->accessKey = $accessKey;
		$this->accessSecret = $accessSecret;
		$this->setServerHost($serverHost);
	}
	
	/**
	 * @param string $server
	 */
	protected function setServerHost($serverHost)
	{
		$pos = strpos($serverHost, "://");
		if ($pos !== false) {
			$pos += 3;
			$serverHost = substr($serverHost, $pos);
		}
		$pos = strpos($serverHost, "/");
		if ($pos !== false) {
			$serverHost = substr($serverHost, 0, $pos);
		}
		
		$this->serverHost = $serverHost;
	}
	
	/**
	 * GMT format time string, 
	 * use strtotime($str) can get timestamp integer from this string
	 * @return string
	 */
	protected function getGMT()
	{
		return gmdate('D, d M Y H:i:s') . ' GMT';
	}
	
	/**
	 * @return array
	 * @throws \Exception
	 */
	public function sendRequest($method, $path, $params, $headers, $body, $files = [])
	{
		//处理文件
	    if ($files) {
	        foreach ($files as &$file) {
	            $file = curl_file_create($file['name'], isset($file['mime']) ? $file['mime'] : null);
	        }
	        $body = (array)$body;
	        $body += $files;
	    }
	    
	    if (is_string($body)) {
	        $headers['Content-Md5'] = SdkUtil::md5($body);
	        $headers['Content-Length'] = strlen($body);
	        isset($headers['Content-Type']) ?: $headers['Content-Type'] = 'application/json; charset=UTF-8';
	    }
	    
	    $headers['Host'] = $this->serverHost;
	    $headers['Date'] = $this->getGMT();
		$headers['x-mg-apiversion'] = self::API_VERSION;
		$headers['x-mg-signaturemethod'] = 'hmac-sha256';
		
		$signature = SdkUtil::getAuthorizationSignature($this->accessSecret, $method, $path, $params, $headers);
		$headers['Authorization'] = "$this->accessKey:$signature";
	
		$url = self::URL_PREFIX . $this->serverHost . '/' . $path;
        $url .= $params ? SdkUtil::buildQuery($params) : '';
		
		return $this->request($method, $url, $body, $headers);
	}

	/**
	 * @return array
	 */
	protected function request($method, $url, $body, $headers)
	{
		$request = new RequestCore($url);
	
		$request->set_method($method);
		$request->set_useragent(self::USER_AGENT);
	
		foreach ($headers as $key => $value) {
			$request->add_header($key, $value);
		}
	
		if ($request->method === "POST" || $request->method === "PUT") {
			$request->set_body($body);
		}
	
		$request->send_request();
	
		$response = [];
		$response[] = (int)$request->get_response_code();
		$response[] = $request->get_response_header();
		$response[] = $request->get_response_body();
	
		return $response;
	}
}