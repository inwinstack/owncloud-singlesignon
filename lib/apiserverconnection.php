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
     * Server url
     *
     * @var string
     */
    private $serverUrl;
    
    /**
     * @param string
     */
    public function __construct($serverUrl) {
        $this->serverUrl = $serverUrl;
        $this->connection = curl_init();

        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, true);
    }
    
    /**
     * Get API server connextion
     *
     * @return connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Get API server url
     *
     * @return string
     */
    public function getServerUrl()
    {
        return $this->serverUrl;
    }
    
}   
