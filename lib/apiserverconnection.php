<?php

namespace OCA\SingleSignOn;

/**
 * Class APIServerConnection
 * @author Dauba
 */
class APIServerConnection implements IAPIServerConnection {

    /**
     * API server connection
     *
     * @var connection
     */
    private $connection;
    
    /**
     * @param mixed 
     */
    public function __construct($serverUrl) {
        $this->connection = new \SoapClient(NULL, array("location" => $serverUrl . "api/server.php", "uri" => $serverUrl));
    }
    
    /**
     * Get API server connextion
     *
     * @return connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
}   
