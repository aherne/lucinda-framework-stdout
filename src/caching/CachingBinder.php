<?php
namespace Lucinda\Framework;

require("vendor/lucinda/http-caching/loader.php");
require("CachingPolicyLocator.php");

/**
 * Binds HTTP Caching API with MVC STDOUT API (aka Servlets API) in order to perform cache validation to a HTTP GET request and produce a response accordingly
 */
class CachingBinder
{
    /**
     * Binds APIs to XML for HTTP cache validation
     *
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @param \Lucinda\MVC\STDOUT\Response $response
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response)
    {
        $policy = $this->getPolicy($application, $request, $response);
        $this->validate($policy, $response);
    }
    
    /**
     * Gets caching policy that will be used for cache validation
     *
     * @param \Lucinda\MVC\STDOUT\Application $application
     * @param \Lucinda\MVC\STDOUT\Request $request
     * @param \Lucinda\MVC\STDOUT\Response $response
     * @return CachingPolicy
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    private function getPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response)
    {
        // detects caching_policy
        $cpb = new CachingPolicyLocator($application, $request, $response);
        $policy = $cpb->getPolicy();
        
        // create and inject driver object
        $driverClass = $policy->getCacheableDriver();
        $policy->setCacheableDriver(new $driverClass($application, $request, $response));
        
        return $policy;
    }
    
    /**
     * Performs cache validation and modifies response accordingly
     *
     * @param CachingPolicy $policy
     * @param \Lucinda\MVC\STDOUT\Response $response
     */
    private function validate(CachingPolicy $policy, \Lucinda\MVC\STDOUT\Response $response)
    {
        if (!$policy->getCachingDisabled() && $policy->getCacheableDriver()) {
            $cacheRequest = new \Lucinda\Caching\CacheRequest();
            if ($cacheRequest->isValidatable()) {
                $validator = new \Lucinda\Caching\CacheValidator($cacheRequest);
                $httpStatusCode = $validator->validate($policy->getCacheableDriver());
                if ($httpStatusCode==304) {
                    $response->setStatus(304);
                    $response->getOutputStream()->clear();
                } elseif ($httpStatusCode==412) {
                    $response->setStatus(412);
                }
            }
            $this->appendHeaders($policy, $response);
        }
    }
    
    /**
     * Append caching headers to response.
     *
     * @param CachingPolicy $policy
     * @param \Lucinda\MVC\STDOUT\Response $response
     */
    private function appendHeaders(CachingPolicy $policy, \Lucinda\MVC\STDOUT\Response $response)
    {
        $cacheable = $policy->getCacheableDriver();
        
        $cacheResponse = new \Lucinda\Caching\CacheResponse();
        $cacheResponse->setPublic(); // fix against session usage
        if ($cacheable->getEtag()) {
            $cacheResponse->setEtag($cacheable->getEtag());
        }
        if ($cacheable->getTime()) {
            $cacheResponse->setLastModified($cacheable->getTime());
        }
        if ($policy->getExpirationPeriod()) {
            $cacheResponse->setMaxAge($policy->getExpirationPeriod());
        }
        $headers = $cacheResponse->getHeaders();
        foreach ($headers as $name=>$value) {
            $response->headers($name, $value);
        }
    }
}
