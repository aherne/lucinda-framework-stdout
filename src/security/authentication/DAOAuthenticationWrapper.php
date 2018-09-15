<?php
namespace Lucinda\Framework;
require_once("AuthenticationWrapper.php");
require_once("FormRequestValidator.php");

/**
 * Binds DAOAuthentication @ SECURITY-API to settings from configuration.xml @ SERVLETS-API then performs login/logout if it matches paths @ xml via database.
 */
class DAOAuthenticationWrapper extends AuthenticationWrapper {
	private $driver;

	/**
	 * Creates an object.
	 * 
	 * @param \SimpleXMLElement $xml Contents of security.authentication.form tag @ configuration.xml.
	 * @param string $currentPage Current page requested.
	 * @param \Lucinda\WebSecurity\PersistenceDriver[] $persistenceDrivers List of drivers to persist information across requests.
	 * @param CsrfTokenDetector $csrf Object that performs CSRF token checks.
	 * @throws \Lucinda\MVC\STDOUT\XMLException If XML is malformed.
	 * @throws \Lucinda\WebSecurity\AuthenticationException If one or more persistence drivers are not instanceof PersistenceDriver
	 * @throws \Lucinda\WebSecurity\TokenException If CSRF checks fail
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 */
	public function __construct(\SimpleXMLElement $xml, $currentPage, $persistenceDrivers, CsrfTokenDetector $csrf) {
		// loads and instances DAO object
	    $className = (string) $xml->authentication->form["dao"];
	    load_class((string) $xml["dao_path"], $className);
		$daoObject = new $className();
		if(!($daoObject instanceof \Lucinda\WebSecurity\UserAuthenticationDAO)) throw new  \Lucinda\MVC\STDOUT\ServletException("Class must be instance of UserAuthenticationDAO!");

		// starts dao-based form authentication
		$this->driver = new \Lucinda\WebSecurity\DAOAuthentication($daoObject, $persistenceDrivers);

		// setup class properties
		$validator = new FormRequestValidator($xml);
		
		// checks if a login action was requested, in which case it forwards object to driver
		if($request = $validator->login($currentPage)) {
			// check csrf token
			if(empty($_POST['csrf']) || !$csrf->isValid($_POST['csrf'], 0)) {
			    throw new \Lucinda\WebSecurity\TokenException("CSRF token is invalid or missing!");
			}
			$this->login($request);
		}
		
		// checks if a logout action was requested, in which case it forwards object to driver
		if($request = $validator->logout($currentPage)) {
			$this->logout($request);
		}
	}

	/**
	 * Logs user in authentication driver.
	 * 
	 * @param LoginRequest $request Encapsulates login request data.
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 */
	private function login(LoginRequest $request) {		
		// set result
		$result = $this->driver->login(
				$request->getUsername(),
				$request->getPassword(),
				$request->getRememberMe()
				);
		$this->setResult($result, $request->getSourcePage(), $request->getDestinationPage());
	}

	/**
	 * Logs user out authentication driver.
	 * 
	 * @param LogoutRequest $request Encapsulates logout request data.
	 * @throws \Lucinda\SQL\ConnectionException If connection to database server fails.
	 * @throws \Lucinda\SQL\StatementException If query to database server fails.
	 */
	private function logout(LogoutRequest $request) {
		// set result
		$result = $this->driver->logout();
		$this->setResult($result, $request->getDestinationPage(), $request->getDestinationPage());
	}
}