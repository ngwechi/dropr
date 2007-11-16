<?php
class pmq_Client
{
	
	/**
	 * @var	pmq_Client_Storage
	 */
    private $storage;
	
	/**
	 * @var pmq_Client_Peer
	 */
    private $peer;
	
	public function __construct(pmq_Client_Storage_Abstract $storage, pmq_Client_Peer_Abstract $peer)
	{
		$this->storage = $storage;
		$this->peer = $peer;
	}
	
	public function sendMessage($message)
	{
		// notify queue via ipc ?!
		$this->storage->put($this->peer, $message);
	}
	
}