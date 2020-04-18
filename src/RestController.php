<?php
namespace Lucinda\Framework;

/**
 * Defines an abstract RESTful controller. Classes extending it must have methods whose name is identical to request methods they are expecting.
 */
abstract class RestController extends \Lucinda\STDOUT\Controller
{
    /**
     * {@inheritDoc}
     * @see \Lucinda\STDOUT\Runnable::run()
     */
    public function run(): void
    {
        $methodName = strtoupper($this->request->getMethod());
        if (method_exists($this, $methodName)) {
            $this->$methodName();
        } else {
            throw new \Lucinda\STDOUT\MethodNotAllowedException();
        }
    }
}
