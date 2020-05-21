<?php
namespace vendor\base;

use vendor\exceptions\InvalidConfigException;

class IpWall extends Component
{
    const IPV4_LEN = 32;
    
    /**
     * @var IpListInterface
     */
    protected $ipList = null;
    
    public function __construct($config = [])
    {
    	if (!isset($config['ipList']) || !is_subclass_of($config['ipList'], IpListInterface::class)) {
    		throw new InvalidConfigException('property `ipList` is absent or invalid.');
    	}
    	$this->ipList = $config['ipList'];
    }
    
    public function getIpList()
    {
    	if (!is_object($this->ipList)) {
    		$ipList = $this->ipList;
    		$this->ipList = new $ipList();
    	}
    	return $this->ipList;
    }

    public function notAllowedIp()
    {
    	$ip = $this->request->getUserIP();
    	
    	$inBlack = $this->getIpList()->black($ip);
    	
    	return $inBlack;
    }
    
}