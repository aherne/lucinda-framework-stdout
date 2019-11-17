<?php
namespace Lucinda\Framework;

require("CachingPolicyFinder.php");

/**
 * Locates CachingPolicy in XML based on contents of http_caching tag. Binds route-based settings (if any) with
 * global caching settings into a CachingPolicy object.
 */
class CachingPolicyLocator
{
    private $policy;

    /**
     * CachingPolicyBinder constructor.
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is incorrect formatted.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    public function __construct(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response)
    {
        $this->setPolicy($application, $request, $response);
    }

    /**
     * Detects caching policy based on contents of http_caching tag and sets a CachingPolicy object in result
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    private function setPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response)
    {
        $this->policy = $this->getGlobalPolicy($application, $request, $response);
        $specificPolicy = $this->getSpecificPolicy($application, $request, $response);
        if ($specificPolicy) {
            if ($specificPolicy->getCachingDisabled()!==null) {
                $this->policy->setCachingDisabled($specificPolicy->getCachingDisabled());
            }
            if ($specificPolicy->getExpirationPeriod()!==null) {
                $this->policy->setExpirationPeriod($specificPolicy->getExpirationPeriod());
            }
            if ($specificPolicy->getCacheableDriver()!==null) {
                $this->policy->setCacheableDriver($specificPolicy->getCacheableDriver());
            }
        }
    }

    /**
     * Detects generic CachingPolicy (applying by default to all routes)
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is incorrect formatted.
     * @throws \Lucinda\MVC\STDOUT\ServletException If resources referenced in XML do not exist or do not extend/implement required blueprint.
     */
    private function getGlobalPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response)
    {
        $caching = $application->getTag("http_caching");
        if (!$caching) {
            throw new \Lucinda\MVC\STDOUT\XMLException("Tag 'http_caching' missing");
        }
        $finder = new CachingPolicyFinder($caching, $application, $request, $response);
        return $finder->getPolicy();
    }

    /**
     * Detects route-specific CachingPolicy (if any)
     *
     * @param \Lucinda\MVC\STDOUT\Application $application Encapsulates application settings @ ServletsAPI.
     * @param \Lucinda\MVC\STDOUT\Request $request Encapsulates request information.
     * @param \Lucinda\MVC\STDOUT\Response $response Encapsulates response information.
     * @throws \Lucinda\MVC\STDOUT\XMLException If XML is improperly configured.
     */
    private function getSpecificPolicy(\Lucinda\MVC\STDOUT\Application $application, \Lucinda\MVC\STDOUT\Request $request, \Lucinda\MVC\STDOUT\Response $response)
    {
        $page = $request->getValidator()->getPage();
        $tmp = (array) $application->getTag("http_caching");
        if (!empty($tmp["route"])) {
            foreach ($tmp["route"] as $info) {
                $route = $info["url"];
                if ($route === null) {
                    throw new \Lucinda\MVC\STDOUT\XMLException("Attribute 'url' is mandatory for 'route' subtag of 'http_caching' tag");
                }
                if ($route == $page) {
                    $finder = new CachingPolicyFinder($info, $application, $request, $response);
                    return $finder->getPolicy();
                }
            }
        }
    }

    /**
     * Gets detected caching policy
     *
     * @return CachingPolicy
     */
    public function getPolicy()
    {
        return $this->policy;
    }
}
