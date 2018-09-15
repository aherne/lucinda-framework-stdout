<?php
namespace Lucinda\Framework;
require_once("AuthorizationWrapper.php");
/**
 * Binds XMLAuthorization @ SECURITY-API to settings from configuration.xml @ SERVLETS-API then performs request authorization via contents of configuration.xml.
 */
class XMLAuthorizationWrapper extends AuthorizationWrapper {
	const DEFAULT_LOGGED_IN_PAGE = "index";
	const DEFAULT_LOGGED_OUT_PAGE = "login";
	const REFRESH_TIME = 0;
		
	/**
	 * Creates an object.
	 * 
	 * @param \SimpleXMLElement $xml Contents of root @ configuration.xml
	 * @param string $currentPage Current page requested.
	 * @param integer $userID Unique user identifier
	 * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
	 */
	public function __construct(\SimpleXMLElement $xml, $currentPage, $userID) {		
		// move up in xml tree
		$xmlLocal = $xml->authorization->by_xml;
		
		$loggedInCallback = (string) $xmlLocal["logged_in_callback"];
		if(!$loggedInCallback) $loggedInCallback = self::DEFAULT_LOGGED_IN_PAGE;
		
		$loggedOutCallback = (string) $xmlLocal["logged_out_callback"];
		if(!$loggedOutCallback) $loggedOutCallback = self::DEFAULT_LOGGED_OUT_PAGE;
		
		// authorize and save result
		$authorization = new \Lucinda\WebSecurity\XMLAuthorization($loggedInCallback, $loggedOutCallback);
		$this->setResult($authorization->authorize($xml, $currentPage, $userID));
	}
}