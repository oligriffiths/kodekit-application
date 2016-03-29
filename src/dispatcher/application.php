<?php
/**
 * Kodekit Component - http://www.timble.net/kodekit
 *
 * @copyright	Copyright (C) 2011 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		MPL v2.0 <https://www.mozilla.org/en-US/MPL/2.0>
 * @link		https://github.com/timble/kodekit-application for the canonical source repository
 */

namespace Kodekit\Component\Application;

use Kodekit\Library;

/**
 * Application Dispatcher
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Component\Application
 */
class DispatcherApplication extends Library\DispatcherAbstract implements Library\ObjectInstantiable
{
    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param 	Library\ObjectConfig    $config  An optional Library\ObjectConfig object with configuration options.
     * @return 	void
     */
    protected function _initialize(Library\ObjectConfig $config)
    {
        $config->append(array(
            'dispatched' => true,
            'controller' => '',
            'request'    => array(
                'base_url'   => '/'
            ),
        ));

        parent::_initialize($config);
    }

    /**
     * Force creation of a singleton
     *
     * @param 	Library\ObjectConfig            $config	  A ObjectConfig object with configuration options
     * @param 	Library\ObjectManagerInterface	$manager  A ObjectInterface object
     * @return  Dispatcher
     */
    public static function getInstance(Library\ObjectConfig $config, Library\ObjectManagerInterface $manager)
    {
        // Check if an instance with this identifier already exists
        if (!$manager->isRegistered('application'))
        {
            $instance  = new static($config);
            $manager->setObject($config->object_identifier, $instance);

            //Add the object alias to allow easy access to the singleton
            $manager->registerAlias($config->object_identifier, 'application');
        }

        return $manager->getObject('application');
    }

    /**
     * Ensure dispatcher is dispatchable
     * @return bool
     */
    public function canDispatch()
    {
        return true;
    }

    /**
     * Get the application router.
     *
     * @param  array $options 	An optional associative array of configuration options.
     * @return	Dispatcher
     */
    public function getRouter(array $options = array())
    {
        return $this->getObject('com:application.dispatcher.router', $options);
    }

    /**
     * Method to set a controller dispatcher object attached to the application dispatcher
     *
     * @param	mixed	$controller An object that implements ControllerInterface, ObjectIdentifier object
     * 					            or valid identifier string
     * @param  array  $config  An optional associative array of configuration options
     * @return	DispatcherApplication
     */
    public function setController($controller, $config = array())
    {
        if(!($controller instanceof Library\ControllerInterface))
        {
            if(is_string($controller) && strpos($controller, '.') === false )
            {
                // Controller names are always singular
                if(Library\StringInflector::isPlural($controller)) {
                    $controller = Library\StringInflector::singularize($controller);
                }

                $identifier			= $this->getIdentifier()->toArray();
                $identifier['name']	= $controller;

                $identifier = $this->getIdentifier($identifier);
            }
            else $identifier = $this->getIdentifier($controller);

            //Set the configuration
            $identifier->getConfig()->append($config);

            $controller = $identifier;
        }

        $this->_controller = $controller;

        return $this;
    }

    /**
     * Fetches the controller object
     *
     * @throws Library\HttpExceptionNotFound if no controller found and no fallback defined
     * @param null|string $fallback A fallback controller name
     * @return Library\ControllerAbstract
     */
    public function getController($fallback = null)
    {
        // Fetch controller identifier
        $identifier = $this->_controller instanceof Library\ObjectIdentifier ? $this->_controller : parent::getController()->getIdentifier();
        if (!$identifier->getName()) {

            if (!$fallback) {
                throw new Library\HttpExceptionNotFound();
            }

            $identifier = $this->getIdentifier()->toArray();
            $identifier['name'] = $fallback;
            $this->setController($identifier);
        }

        return parent::getController();
    }

    /**
     * Resolve the request
     *
     * @param Library\DispatcherContextInterface $context A dispatcher context object
     */
    protected function _resolveRequest(Library\DispatcherContextInterface $context)
    {
        parent::_resolveRequest($context);

        $url = clone $context->request->getUrl();

        //Parse the route
        $this->getRouter()->parse($url);

        //Set the request
        $context->request->query->add($url->query);

        //Resolve the component
        if($context->request->query->has('component'))
        {
            $identifier  = $this->getIdentifier()->toArray();
            $identifier['package'] = $context->request->query->get('component', 'cmd');;

            $this->setController($identifier);
        }
    }

    /**
     * Forward the request
     *
     * @param Library\DispatcherContextInterface $context A dispatcher context object
     */
    protected function _actionDispatch(Library\DispatcherContextInterface $context)
    {
        //Execute the component and pass along the context
        $this->getController()->dispatch($context);
    }

    /**
     * Fail the request
     *
     * @param Library\DispatcherContextInterface $context	A dispatcher context object
     */
    protected function _actionFail(Library\DispatcherContextInterface $context)
    {
        //Execute the component and pass along the contex
        $this->getController('error')->fail($context);
    }

    /**
     * Forward the request
     *
     * @param Library\DispatcherContextInterface $context	A dispatcher context object
     */
    protected function _actionRedirect(Library\DispatcherContextInterface $context)
    {
        //Execute the component and pass along the context
        $this->getController('redirect')->redirect($context);
    }
}
