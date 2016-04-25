<?php
/**
 * Kodekit Platform - http://www.timble.net/kodekit
 *
 * @copyright	Copyright (C) 2011 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		MPL v2.0 <https://www.mozilla.org/en-US/MPL/2.0>
 * @link		https://github.com/timble/kodekit-platform for the canonical source repository
 */

return array(

    'aliases'  => array(
        'application'       => 'com:application.dispatcher',
        'translator'        => 'com:application.translator',

        'lib:template.locator.component'    => 'com:application.template.locator.component',
        'lib:template.locator.file'         => 'com:application.template.locator.file',
    ),

    'identifiers' => array(
        'application' => array(
            'behaviors' => array('com:application.dispatcher.behavior.documentable')
        ),
    )
);
