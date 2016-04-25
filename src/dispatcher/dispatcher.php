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
class Dispatcher extends Library\DispatcherAbstract implements Library\ObjectInstantiable
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
     * Resolve the request
     *
     * @param Library\DispatcherContextInterface $context A dispatcher context object
     */
    protected function _resolveRequest(Library\DispatcherContextInterface $context)
    {
        parent::_resolveRequest($context);

        $url = clone $context->getRequest()->getUrl();

        //Parse the route
        $this->getRouter()->parse($url);

        //Set the request
        $context->getRequest()->getQuery()->add($url->query);

        //Resolve the component
        if($context->getRequest()->getQuery()->has('component'))
        {
            $identifier  = $this->getIdentifier()->toArray();
            $identifier['package'] = $context->getRequest()->getQuery()->get('component', 'cmd');

            $this->setController($identifier);
        }
    }

    /**
     * Forward the request
     *
     * @param Library\DispatcherContextInterface $context A dispatcher context object
     * @return	mixed
     */
    protected function _actionDispatch(Library\DispatcherContextInterface $context)
    {
        $identifier = $this->_controller instanceof Library\ObjectIdentifier ? $this->_controller : $this->getController()->getIdentifier();
        if ($identifier->getPackage() === 'application') {
            throw new Library\HttpExceptionNotFound();
        }

        //Execute the component and pass along the context
        return $this->getController()->dispatch($context);
    }

    /**
     * Fail the request
     *
     * @param Library\DispatcherContextInterface $context	A dispatcher context object
     * @return	mixed
     */
    protected function _actionFail(Library\DispatcherContextInterface $context)
    {
        $identifier = $this->_controller instanceof Library\ObjectIdentifier ? $this->_controller : $this->getController()->getIdentifier();
        if ($identifier->getPackage() === 'application') {
            return parent::_actionFail($context);
        }

        //Execute the component and pass along the contex
        return $this->getController()->fail($context);
    }

    /**
     * Forward the request
     *
     * @param Library\DispatcherContextInterface $context	A dispatcher context object
     * @return	mixed
     */
    protected function _actionRedirect(Library\DispatcherContextInterface $context)
    {
        $identifier = $this->_controller instanceof Library\ObjectIdentifier ? $this->_controller : $this->getController()->getIdentifier();
        if ($identifier->getPackage() === 'application') {
            return parent::_actionRedirect($context);
        }

        //Execute the component and pass along the context
        return $this->getController()->redirect($context);
    }
}
