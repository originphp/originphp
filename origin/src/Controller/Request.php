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

namespace Origin\Controller;

use Origin\Core\Router;
use Origin\Core\Session;
use Origin\Core\Cookie;

use Origin\Exception\MethodNotAllowedException;

class Request
{
    /**
     * Request params.
     *
     * @var array
     */
    public $params = array(
        'controller' => null,
        'action' => null,
        'pass' => array(),
        'named' => array(),
        'plugin' => null,
        'route' => null
    );

    /**
     * Holds the query data.
     * @var array
     */
    public $query = [];

    /**
     * Will contain form post data.
     *
     * @var array
     */
    public $data = [];

    /**
     * Address of request including base folder without Query params.
     *
     * @example /subfolder/controller/action
     */
    public $url = null;

    /**
     * Base.
     *
     * @todo subfolder
     */
    public $base = null;

    /**
     * Session
     *
     * @return \Origin\Core\Session
     */
    protected $session = null;

    /**
     * Cookie
     *
     * @return \Origin\Core\Cookie
     */
    protected $cookie = null;

    
    /**
     * This makes it easy for testing e.g  $request = new Request('articles/edit/2048');
     *
     * @param string $url articles/edit/2048
     */
    public function __construct($url = null)
    {
        $this->initialize($url);
    }

    /**
     * Initializes the request
     * @params string $url articles/edit/2048
     */
    public function initialize($url = null)
    {
        if ($url === null) {
            $url = $this->uri();
        }
        if (strlen($url) and $url[0] === '/') {
            $url = substr($url, 1);
        }

        $this->params = Router::parse($url);

        $this->processGet($url);
        $this->processPost();

        Router::setRequest($this);
    }

    /**
     * Gets the URL for request
     * uri: /controller/action/100.
     *
     * @return string uri
     */
    protected function uri()
    {
        if ($uri = $this->env('REQUEST_URI')) {
            return $uri;
        }
        return '';
    }

    /**
     * This will return the url of the request
     * @example /contacts/view/100?page=1
     * @param boolean $includeQuery
     * @return string
     */
    public function url(bool $includeQuery = true)
    {
        $url = $this->url;
        if ($includeQuery and $this->query) {
            $url .= '?' . http_build_query($this->query);
        }
    
        return $url;
    }

    protected function processGet($url)
    {
        // Build Query
        $query = [];
        if (strpos($url, '?') !== false) {
            list($url, $queryString) = explode('?', $url);
            parse_str($queryString, $query);
        }

        $this->url = '/'.$url;
        $this->query = $query;
    }

    /**
     * curl -i -X POST -H 'Content-Type: application/json' -d '{"title":"CNBC","url":"https://www.cnbc.com"}' http://localhost:8000/bookmarks/add
     *
     * @return void
     */
    protected function processPost()
    {
        $data = [];
        if ($this->is(['put', 'patch', 'delete'])) {
            parse_str($this->readInput(), $data);
        }
        if ($this->is(['post'])) {
            /**
             * curl -i -X POST -H 'Content-Type: application/json' -d '{"title":"CNBC","url":"https://www.cnbc.com"}' http://localhost:8000/bookmarks/test
             */
            if ($this->env('CONTENT_TYPE') === 'application/json') {
                $data = json_decode($this->readInput(), true);
                if (!is_array($data)) {
                    $data = [];
                }
            }
            if (!empty($_POST)) {
                $data = $_POST;
            }
        }
        $this->data = $data;

        return $data;
    }

    /**
     * Checks the server request method.
     *
     * @param string|array $method get|post|put|delete
     *
     * @return bool true or false
     */
    public function is($type)
    {
        $method = $this->env('REQUEST_METHOD');
        if (!$method) {
            return false;
        }
        if (!is_array($type)) {
            $type = [$type];
        }

        return in_array(strtolower($method), $type);
    }

    /**
     * Returns the server request method
     * example get|post|put|delete
     * @return string
     */
    public function method()
    {
        return $this->env('REQUEST_METHOD');
    }

    /**
     * Run this from the controller to only allow certian methods, if the
     * method is not of a certain type e..g post/get/put then it will throw
     * and exception
     *
     * @param string|array $type e.g. post or get
     * @return bool
     */
    public function allowMethod($type)
    {
        if ($this->is($type)) {
            return true;
        }
        throw new MethodNotAllowedException();
    }
    protected $accepts = [
        'json' => 'application/json',
        'xml' => 'application/xml'
    ];

    /**
     * Checks if the request accepts, this will search the HTTP accept, extension
     * being called.
     *
     * $request->accepts('application/json');
     * $request->accepts(['application/xml','application/json]);
     *
     * @todo in future maybe something routing maybe without complicating things.
     * @param string|array $type
     * @return bool
     */
    public function accepts($type=null) : bool
    {
        $path = parse_url($this->url(), PHP_URL_PATH);
      
        $acceptHeaders = $this->parseAcceptWith($this->env('HTTP_ACCEPT'));
        if ($type === null) {
            return $acceptHeaders;
        }

        foreach ((array) $type as $needle) {
            if (in_array($needle, $acceptHeaders)) { // does not find application/xml;q=0.9
                return true;
            }
            $parts = explode('/', $needle);
            $extensionNeedle =  end($parts);
            if (strpos(strtolower($path), ".{$extensionNeedle}") !== false) {
                return true;
            }
            if (isset($this->params[$extensionNeedle])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets a list of accepted languages, checks if a specific language is accepted
     *
     * @param string $language
     * @return array|bool
     */
    public function acceptLanguage(string $language = null)
    {
        $acceptedLanguages = [];
        $languages = $this->parseAcceptWith($this->env('HTTP_ACCEPT_LANGUAGE'));
        foreach ($languages as $lang) {
            $acceptedLanguages[] = str_replace('-', '_', $lang);
        }
        
        if ($language === null) {
            return $acceptedLanguages;
        }
    
        return in_array($language, $acceptedLanguages);
    }

    /**
     * Parse accept headers into arrays
     * example: en-GB,en;q=0.9,es;q=0.8 becomes [en-GB,en,es]
     *
     * @param string $header
     * @return array
     */
    protected function parseAcceptWith(string $header) : array
    {
        $accepts = [];
        $values = explode(',', $header);
        foreach ($values as $value) {
            $value = trim($value);
            $pos = strpos($value, ';');
            if ($pos !== false) {
                $value = substr($value, 0, $pos);
            }
            $accepts[] = $value;
        }
        return $accepts;
    }

    /**
     * Gets an enviroment variable from $_SERVER.
     *
     * @param string $key
     * @return string|null
     */
    public function env(string $key) : ?string
    {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return null;
    }

    /**
     * Returns the session
     *
     * @return \Origin\Core\Session
     */
    public function session()
    {
        if ($this->session === null) {
            $this->session = new Session();
        }
        return $this->session;
    }

    /**
     * Reads a cookie value from the request. Cookies set
     * using the response::cookie would not of been sent yet.
     *
     * @return string|null
     */
    public function cookie(string $key) : ?string
    {
        if ($this->cookie === null) {
            $this->cookie = new Cookie();
        }
        return $this->cookie->read($key);
    }

    /**
     * Reads the php://input stream
     *
     * @return string
     */
    protected function readInput() : ?string
    {
        $fh = fopen('php://input', 'r');
        $contents = stream_get_contents($fh);
        fclose($fh);
        return $contents;
    }
}
