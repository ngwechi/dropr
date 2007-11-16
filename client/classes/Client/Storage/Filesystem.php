<?php
/**
 * Filesystem Storage Driver
 * 
 * @author Soenke Ruempler
 * @author Boris Erdmann
 *
 */
class pmq_Client_Storage_Filesystem extends pmq_Client_Storage_Abstract
{
    
    const SPOOLDIR_TYPE_IN = 'in';
    
    const SPOOLDIR_TYPE_SPOOL = 'proc';
    
    const SPOOLDIR_TYPE_SENT = 'sent';
    
    private $path;
	
	protected function __construct($path)
	{
	    
	    if (!is_string($path)) {
	        throw new pmq_Client_Exception("No valid path given");
	    }
	    
	    if (!is_dir($path)) {
	        if (!@mkdir($path, 0755)) {
	            throw new Pmq_Client_Exception("Could not create Queue Directory $path");
	        }
	    }
	    
	    if (!is_writeable($path)) {
	        throw new pmq_Client_Exception("$path is not writeable!");
	    }
	    
	    $this->path = realpath($path);
	}
	
    public function put(pmq_Client_Message $message)
    {
        // Check the peer-dir
        
        $inPath = $this->getPeerSpoolPath($message->getPeer(), self::SPOOLDIR_TYPE_IN) . DIRECTORY_SEPARATOR;
        $spoolPath = $this->getPeerSpoolPath($message->getPeer(), self::SPOOLDIR_TYPE_SPOOL) . DIRECTORY_SEPARATOR;

        $badName = true;
        while ($badName) {
            $msgId = $this->getMsgId();
            $fName = $inPath . $msgId;
            echo $fName;
            $fh = @fopen($fName, 'x');
            $badName = ($fh === false);
            if ($badName) {
                echo '*';
            }
        }
        $messageArray = array(
            "mId" => $msgId,
            "msg" => $message->getMessage()
        );
    
        fwrite($fh, serialize($messageArray));
        fclose($fh);
    
        if (!rename($inPath . $msgId, $spoolPath . $msgId)) {
            throw new pmq_Client_Exception("Could not move spoolfile!");
        }
    }
		
    /**
     * returns the most recent messages  out of the storage ordered by 
     * 
     * 
     * @return array	An array of pmq_Client_Message objects 
     */
    public function getQueuedHandles($limit = null)
    {
        $spoolDir = $this->getSpoolPath(self::SPOOLDIR_TYPE_SPOOL);
        $peers = scandir($spoolDir);
        unset($peers[0]);
        unset($peers[1]);
        
        foreach($peers as $peer) {
            $messages[$this->decodePeerDirectory($peer)] = scandir($spoolDir.DIRECTORY_SEPARATOR.$peer);
            unset($messages[0]);
            unset($messages[1]);
        }
        return $messages;
    }
    
    /**
     * 
     */
    public function getMessage($messageId, pmq_Client_Peer $peer)
    {
        return $this->getPeerSpoolPath($peer, self::SPOOLDIR_TYPE_SPOOL) . DIRECTORY_SEPARATOR . $messageId;
    }
    
    private function getPeerSpoolPath(pmq_Client_Peer_abstract $peer, $type = self::SPOOLDIR_TYPE_IN)
    {
        $path = $this->getSpoolPath($type) . DIRECTORY_SEPARATOR .
            $this->encodePeerDirectory($peer->getTransportMethod().';'.$peer->getUrl());

        if (!is_dir($path)) {
            if (!mkdir($path, 0775)) {
                throw new pmq_Client_Exception("Could not created directory $path!");
            }
        }
        
        return $path;    
    }
    
    private function encodePeerDirectory($url)
    {
        return base64_encode($url);
    }

    private function decodePeerDirectory($url)
    {
        return base64_encode($url);
    }
    
    private function getSpoolPath($type = self::SPOOLDIR_TYPE_IN)
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $type;
        
        if (!is_dir($path)) {
            if (!mkdir($path, 0775)) {
                throw new pmq_Client_Exception("Could not created directory $path!");
            }
        }
        
        return $path;
        
    }
    
    private function getMsgId()
    {
        $tName = (string)microtime();
        $spPos = strpos($tName, ' ');
        return substr($tName, $spPos+1).'-'.substr($tName, 2, $spPos-2);
    }
    
    public function getType() {
        return TYPE_FILE;
    }
    
    public function checkSentHandles($handles, $peerDsn) {
         
    }
}