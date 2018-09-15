<?php
namespace Lucinda\Framework;
/**
 * Implements an abstract converter from an XML line (child of loggers.{environment}) to a Logger instance @ LoggingAPI
 */
abstract class AbstractLoggerWrapper {
    protected $logger;
    
    /**
     * @param \SimpleXMLElement $xml XML tag that is child of loggers.(environment)
     */
    public function __construct(\SimpleXMLElement $xml) {
        $this->setLogger($xml);
    }
    
    /**
     * Detects Logger instance based on XML tag supplied
     * 
     * @param \SimpleXMLElement $xml XML tag that is child of loggers.(environment)
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is invalid.
     * @throws \Lucinda\MVC\STDOUT\ServletException If referenced resources do not exist.
     */
    abstract protected function setLogger(\SimpleXMLElement $xml);
    
    /**
     * Gets detected logger
     * 
     * @return \Lucinda\Logging\Logger
     */
    public function getLogger() {
        return $this->logger;
    }
}