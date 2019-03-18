<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\View\Helper;

use Origin\View\View;
use Origin\Core\ConfigTrait;
use Origin\Core\Logger;

class Helper
{
    use ConfigTrait;
    /**
       * View Object
       *
       * @var \Origin\View\View
       */
    protected $_view = null;

    /**
     * Array of helpers and config. This poupulated by loadHelper
     *
     * @var array
     */
    protected $_helpers = [];

    public function __construct(View $view, array $config = [])
    {
        $this->_view = $view;
        
        $this->config($config);

        $this->initialize($config);
    }

    /**
     * Loads a helper -  the helper is not returned, but when you call it will be
     * lazy loaded
     *
     * @param string $name
     * @param array $config
     * @return void
     */
    public function loadHelper(string $name, array $config=[])
    {
        list($plugin, $helper) = pluginSplit($name);
        if (!isset($this->_helpers[$helper])) {
            $this->_helpers[$helper] =  array_merge(['className' => $name . 'Helper'], $config);
        }
    }

    /**
     * Handles the lazyloading
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->_helpers[$name])) {
            $this->{$name} = $this->view()->helperRegistry()->load($name, $this->_helpers[$name]);
            
            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }

    /**
     * This is called when helper is loaded for the first time from the
     * controller.
     */
    public function initialize(array $config)
    {
    }

    /**
     * Creates a DOM id from field
     * Should be used by helpers to generate dom ids for fields.
     *
     * @param string $field
     * @return string id
     */
    protected function domId(string $field)
    {
        return preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($field));
    }

    /**
     * Converts an array of attributes to string format e.g
     * becomes.
     *
     * @param array $attributes ['class'=>'form-control']
     *
     * @return string class="form-control"
     */
    protected function attributesToString(array $attributes = [])
    {
        $result = [];
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $result[] = $key;
            } elseif (is_scalar($value)) {
                $result[] = "{$key}=\"{$value}\"";
            }
        }

        if ($result) {
            return ' '.implode(' ', $result);
        }

        return '';
    }

    /**
     * Returns the View
    *
    * @return \Origin\View\View
    */
    public function view()
    {
        return $this->_view;
    }

    /**
     * Returns the request object
     *
     * @return \Origin\Controller\Request
     */
    public function request()
    {
        return $this->_view->request;
    }

    /**
     * Returns the response object
     *
     * @return \Origin\Controller\Response
     */
    public function response()
    {
        return $this->_view->response;
    }


    /**
     * Returns a Logger Object
     *
     * @param string $channel
     * @return \Origin\Core\Logger
     */
    public function logger(string $channel = 'Helper')
    {
        return new Logger($channel);
    }
}
