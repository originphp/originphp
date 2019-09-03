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

use ReflectionClass;
use App\View\AppView;
use ReflectionMethod;
use Origin\Http\Router;

use Origin\Http\Request;
use Origin\View\XmlView;
use Origin\Http\Response;
use Origin\View\JsonView;
use Origin\Utility\Inflector;
use Origin\Exception\Exception;
use Origin\Concern\ConcernRegistry;
use Origin\Model\Traits\ModelTrait;
use Origin\Controller\Component\Component;
use Origin\Controller\Component\ComponentRegistry;

class Controller
{
    use ModelTrait;
    /**
     * Controller name.
     *
     * @var string
     */
    public $name = null;

    /**
     * Model name for this controller.
     *
     * @var string
     */
    public $modelName = null;

    /**
     * These are vars passed to view.
     *
     * @var array
     */
    public $viewVars = [];

    /**
     * Automatically renders view.
     */
    public $autoRender = true;

    /**
     * Default layout, set to false to not use a layout.
     *
     * @var string
     */
    public $layout = 'default';

    /**
     * Holds the request object.
     *
     * @var \Origin\Http\Request
     */
    public $request = null;

    /**
     * Holds the response object.
     *
     * @var \Origin\Http\Response
     */
    public $response = null;

    /**
     * Helpers to load.
     */
    public $viewHelpers = [];

    /**
       * Holds the componentregistry object.
       *
       * @var \Origin\Controller\Component\ComponentRegistry
       */
    protected $componentRegistry = null;

    /**
     * Array keys to be serialized
     *
     * @var array
     */
    protected $serialize = [];

    /**
     * Paginator Settings.
     *
     * @var array (limit,order,fields,conditions)
     */
    public $paginate = [];

    public function __construct(Request $request = null, Response $response = null)
    {
        list($namespace, $name) = namespaceSplit(get_class($this));
        $this->name = substr($name, 0, -10);

        $this->modelName = Inflector::singular($this->name);

        $this->request = $request ?  $request : new Request();
        $this->response = $response ? $response : new Response();

        $this->componentRegistry = new ComponentRegistry($this);
        $this->concernRegistry = new ConcernRegistry($this, 'Controller/Concern');

        $this->initialize();
    }

    /**
    * Magic method it call the first loaded behavior method if its available
    *
    * @param string $method
    * @param array $arguments
    * @return void
    */
    public function __call(string $method, array $arguments)
    {
        $concern = $this->concernRegistry->hasMethod($method);
        if ($concern) {
            return call_user_func_array([$concern, $method], $arguments);
        }
   
        throw new Exception('Call to undefined method '  . get_class($this) . '\\' .  $method . '()');
    }

    /**
     * Loads a Component for use with the controller.
     *
     * @param string $name   Component name e.g. Auth
     * @param array  $config array of config to be passed to component. Class name
     * @return \Origin\Controller\Component\Component
     */
    public function loadComponent(string $name, array $config = []) : Component
    {
        list($plugin, $component) = pluginSplit($name);
        $config = array_merge(['className' => $name.'Component'], $config);

        return $this->$component = $this->componentRegistry->load($name, $config);
    }

    /**
     * Loads a concern
     *
     * @param string $name
     * @param array $config
     * @return void
     */
    public function loadConcern(string $name, array $config = []) //no return type cause of mocking
    {
        list($plugin, $concern) = pluginSplit($name);
        $config = array_merge(['className' => $name . 'Concern'], $config);

        return $this->$concern = $this->concernRegistry->load($name, $config);
    }

    /**
     * Loads a helper to be used in the View.
     *
     * @param string $name   Helper name e.g. Form
     * @param array  $config array of config to be passed to helper
     * @return void
     */
    public function loadHelper(string $name, array $config = []) : void
    {
        list($plugin, $helper) = pluginSplit($name);
        $config = array_merge(['className' => $name.'Helper'], $config);
        $this->viewHelpers[$helper] = $config;
    }

    /**
     * Lazy load the model for this controler.
     */
    public function __get($name)
    {
        if ($name === $this->modelName) {
            return $this->loadModel($name);
        }

        return null;
    }

    /**
     * Checks if an action on this controller is accessible.
     *
     * @param string $action
     * @return bool
     */
    public function isAccessible(string $action) : bool
    {
        $controller = new ReflectionClass(Controller::class);
        if ($controller->hasMethod($action)) {
            return false;
        }
        if (! method_exists($this, $action)) {
            return false;
        }
        $reflection = new ReflectionMethod($this, $action);

        return $reflection->isPublic();
    }

    /**
     * This is immediately after construct method. Use this Hook load components,
     * helpers or anything that needs to be done when a new controller is created.
     */
    public function initialize()
    {
    }

    /**
     * Sends a value or array of values to the view array.
     *
     * @param string|array $name key name or array
     * @param $value if key is a string set the value for this
     * @return void
     */
    public function set($name, $value = null) : void
    {
        if (is_array($name)) {
            $data = $name;
        } else {
            $data = [$name => $value];
        }

        $this->viewVars = array_merge($this->viewVars, $data);
    }

    /**
    * Callback before the action in the controller is called.
    */
    public function beforeFilter()
    {
    }

    /**
     * Callback just prior to redirecting
     */
    public function beforeRedirect()
    {
    }
    /**
     * This is called after the startup, before shutdown
     */
    public function beforeRender()
    {
    }

    /**
     * Called after the controller action and the component shutdown function.
     * Remember to call parent
     *
     * @return \Origin\Http\Response|void
     */
    public function afterFilter()
    {
    }

    /**
     * The controller startup process
     *
     * @return mixed
     */
    public function startupProcess()
    {
        $result = $this->beforeFilter();
        if (! $this->isResponseOrRedirect($result)) {
            $result = $this->componentRegistry()->call('startup');
        }
        if ($this->isResponseOrRedirect($result)) {
            return $this->response;
        }
    }
   
    /**
     * The controller shutdown process
     *
     * @return mixed
     */
    public function shutdownProcess()
    {
        $result = $this->componentRegistry()->call('shutdown');
        if (! $this->isResponseOrRedirect($result)) {
            $result = $this->afterFilter();
        }
        if ($this->isResponseOrRedirect($result)) {
            return $this->response;
        }
        //# Free Mem for no longer used items
        $this->componentRegistry()->destroy();
        unset($this->componentRegistry);
    }

    /**
    * Checks if the result is a response object or redirect was called
    *
    * @param mixed $result
    * @return boolean
    */
    protected function isResponseOrRedirect($result) : bool
    {
        return ($result instanceof Response or $this->response->headers('Location'));
    }

    /**
     * Loads the PaginatorComponent and passes the settings to it.
     *
     * @param string $model name of the model
     * @param array $settings the settings used by PaginatorComponent these are the same settings as in
     * find query (fields, joins, order,limit, group, callbacks,contain)
     * @return mixed
     */
    public function paginate(string $model = null, array $settings = [])
    {
        if ($model === null) {
            $model = $this->modelName;
        }
    
        $object = $this->loadModel($model);

        $this->loadComponent('Paginator');
        if (! isset($this->viewHelpers['Paginator'])) {
            $this->loadHelper('Paginator');
        }
        
        $defaults = $this->paginate;
        if (isset($this->paginate[$model])) {
            $defaults = $this->paginate[$model];
        }

        return $this->Paginator->paginate($object, $defaults + $settings);
    }

    /**
     * Renders a view. This is called automatically by the dispatcher.
     *
     * If the argument is a string it will assume you want to load a standard
     * view using a template.
     *
     * $this->render('action');
     * $this->render('Controller/action')
     * $this->render('Plugin.Controller/action');
     *
     * // Set the type to render with data
     * $this->render(['xml'=>$array)
     * $this->render(['json'=>$array,'status'=>403]);
     * $this->render(['text'=>'OK']);
     *
     * ### View Types
     * - template - standard view
     * - xml:takes an array and coverts to xml
     * - json: takes an array of data and converts to json
     * - text: sends a txt response, this can be handy when dealing with ajax
     * - file: this loads an external file with file_get_contents. This does not send the file.
     *
     * ### Options
     * type: this is the content type that will be used (default:html). If use the xml or json options then the type will
     * be changed automatically. If you use file that is anything other than html then change the type
     * status: the status code to send. Most API providers use only a small subset of huge amount of
     * http error codes.
     *
     *  Here is set which should cover everything.
     *
     *  200 - OK (Success)
     *  400 - Bad Request (Failure - client side problem)
     *  500 - Internal Error (Failure - server side problem)
     *  401 - Unauthorized
     *  404 - Not Found
     *  403 - Forbidden (For application level permisions)
     *
     * @param string|array $options you can pass a string which is the template name this is the same
     * as the current action, if it starts with / then it will look in a different folder. If you pass
     * an array options, you can do so as follows:
     *   Types:
     *   - json: a string, array or object will be converted to json. E.g. $this->render(['json'=>$result]);
     *   - xml: an xml string or an array which will be converted to XML
     *   - text: For rendering plain text
     *   - file: a filename, and file get contents will be used.
     *   - template: default option, this will render the html template from the views folder.
     *   Other
     *   - status: the status code to return, e.g. 404
     * @return void
     */
    public function render($options = [])
    {
        $template = $this->request->params('action');
        if (empty($options)) {
            $options = $template;
        }
        if (is_string($options)) {
            $options = ['template' => $options];
        }
      
        $options += [
            'status' => $this->response->statusCode(),
            'type' => 'html',
        ];

        $body = null;

        /**
         * When working with json sometimes values can empty, for example autocomplete
         * so array key exists better than isset.
         */
        if (array_key_exists('json', $options)) {
            return $this->renderJson($options['json'], $options['status']);
        }

        if ($this->autoRender and $this->serialize and $this->request->type() === 'json') {
            return $this->renderJson(null, $options['status']);
        }
        
        if (array_key_exists('xml', $options)) {
            return $this->renderXml($options['xml'], $options['status']);
        }

        if ($this->autoRender and $this->serialize and $this->request->type() === 'xml') {
            return $this->renderXml(null, $options['status']);
        }
        
        if (array_key_exists('text', $options)) {
            $options['type'] = 'txt';
            $body = $options['text'];
        } elseif (array_key_exists('file', $options)) {
            $body = file_get_contents($options['file']);
            $options['type'] = mime_content_type($options['file']);
        }
        if ($body === null) {
            if (isset($options['template'])) {
                $template = $options['template'];
            }
            $view = new AppView($this);
            $body = $view->render(
                $template,
                $options['type'] === 'html'?$this->layout:false
            );
            unset($view);
        }
    
        $this->response->type($options['type']);   // 'json' or application/json
        $this->response->statusCode($options['status']); // 200
        $this->response->body($body); //
    }

    /**
     * Sets the key or keys of the viewVars to be serialized
     *
     * @param string|array $keyOrKeys
     * @return string|void
     */
    public function serialize($keyOrKeys = null)
    {
        if ($keyOrKeys === null) {
            return $this->serialize;
        }
        $this->serialize = $keyOrKeys;
    }

    /**
     * Renders a json view
     *
     *  $this->renderJson([
     *     'data' => [
     *         'id' => 1234,'name'=>'James'
     *      ]
     *    ]);
     *
     *  $this->renderJson([
     *      'error' =>[
     *          'message' => 'Not Found','code' => 404
     *       ]
     *     ],404);
     *
     *  Most API providers use only a small subset of massiave amount of http error codes
     *
     *  These are the most important ones if you don't want to overcomplicate
     *
     *  200 - OK (Success)
     *  400 - Bad Request (Failure - client side problem)
     *  500 - Internal Error (Failure - server side problem)
     *  401 - Unauthorized
     *  404 - Not Found
     *  403 - Forbidden (For application level permisions)
     *
     * @param array|string $data data which will be json encoded
     * @return void
     */
    public function renderJson($data, int $status = 200) : void
    {
        $this->autoRender = false; // Only render once
        $this->beforeRender();
        
        $view = new JsonView($this);
        
        $this->response->type('json');   // 'json' or application/json
        $this->response->statusCode($status); // 200
        $this->response->body($view->render($data, $status)); //
    }

    /**
     * Renders an XML view using an array.
     *
     *  $this->renderXml([
     *       'post' => [
     *           '@category' => 'how tos', // to set attribute use @
     *           'id' => 12345,
     *           'title' => 'How to create an XML block',
     *           'body' =>  Xml::cdata('A quick brown fox jumps of a lazy dog.'),
     *           'author' => [
     *              'name' => 'James'
     *            ]
     *          ]
     *     ]);
     *
     * @param array $data
     * @param integer $status
     * @return void
     */
    public function renderXml($data, int $status = 200) : void
    {
        $this->autoRender = false; // Disable for dispatcher
        $this->beforeRender();
        
        $view = new XmlView($this);
        
        $this->response->type('xml');
        $this->response->statusCode($status); // 200
        $this->response->body($view->render($data, $status)); //
    }

    /**
     * Redirects to a url, will disable autoRender but you should always
     * return $this->redirect to prevent code from running during tests etc
     *
     * # Options
     * - controller
     * - action
     * - ? : query
     * - # : fragment
     *
     * @param string|array $url
     * @param int status code default 302
     * @return \Origin\Http\Response
     */
    public function redirect($url, int $code = 302) : Response
    {
        $this->autoRender = false;
        $this->beforeRedirect();

        $this->response->statusCode($code);
        $this->response->header('Location', Router::url($url));
        $this->response->send();
        $this->response->stop();

        // Return the response object once called
        return $this->response;
    }

    /**
     * Returns the component registry
     *
     * @return \Origin\Controller\Component\ComponentRegistry
     */
    public function componentRegistry()
    {
        return $this->componentRegistry;
    }
}
