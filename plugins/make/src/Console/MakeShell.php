<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Make\Console;

use Origin\Console\Shell;
use Origin\Model\ConnectionManager;
use Origin\Core\Inflector;
use Origin\Exception\Exception; // @todo a different exception?
use Origin\View\Templater;
use Make\Utils\MakeTemplater;

/**
*  Reference
*  [model] => BookmarksTag
*  [controller] => BookmarksTags
*  [singularName] => bookmarksTag
*  [pluralName] => bookmarksTags
*  [singularHuman] => Bookmarks Tag
*  [pluralHuman] => Bookmarks Tags
*  [singularHumanLower] => bookmarks tag
*  [pluralHumanLower] => bookmarks tags
*/


class MakeShell extends Shell
{
    protected $meta = [];

    protected function introspectDatabase()
    {
        $this->loadTask('Make.Make');
        $this->Make->introspectDatabase();
        $this->meta = $this->Make->build();
    }
    public function initialize(array $arguments)
    {
        if (!file_exists(CONFIG . DS . 'database.php')) {
            $this->out('<danger>No database configuration found. </danger>');
            $this->out('Create config/database.php using the template in the same directory.');
            return;
        }
        $this->introspectDatabase();
    }
    public function main()
    {
        $this->showUsage();
        /*
        $this->out('Interactive Make Shell');
        $this->out('[A] Makes All');
        $this->out('[M] Model');
        $this->out('[V] View');
        $this->out('[C] Controller');
        $this->out('[T] Test Case');
        $this->out('[F] Fixture');
        $selected = $this->in('Select an option', ['A','M','V','C','T','F']);
        switch (strtoupper($selected)) {

        }*/
    }
    public function showUsage()
    {
        $this->out('make all');
        $this->out('make model Lead');
        $this->out('make controller Leads');
        $this->out('make view Lead');
        //$this->out('make test Lead'); /**@todo test */
    }


    public function all()
    {
        if (empty($this->args)) {
            $models = $this->getAvailable();
            $this->out('Generate Model, View and Controller for each of the following models:');
            foreach ($models as $model) {
                $this->out($model);
            }
            $result = $this->in('Do you want to continue?', ['y','n'], 'n');
            if ($result === 'n') {
                return;
            }
        } else {
            $models = $this->args;
        }

        foreach ($models as $model) {
            $controller = Inflector::pluralize($model);
            $this->controller($controller);
            $this->model($model);
            $this->view($controller);
        }
    }

    public function controller(string $controller = null)
    {
        if (isset($this->args[0])) {
            $controller = $this->args[0];
        }
        if ($controller === null) {
            $this->showAvailable(true);
            return ;
        }
        $options = $this->getAvailable(true);
        
        if (in_array($controller, $options) === false) {
            throw new Exception(sprintf('Invalid controller %s', $controller));
        }
        $controller =$controller;

        $filename = SRC . DS . 'Controller' .DS . $controller .'Controller.php';
        if (file_exists($filename)) {
            $result = $this->in(sprintf('%sController already exist, overwrite?', $controller), ['y','n'], 'n');
            if ($result === 'n') {
                exit;
            }
        }

        $model = Inflector::singularize($controller);
        $data = $this->getData($model);
       
        $belongsTo = $this->meta['associations'][$model]['belongsTo'];

        // Create Block Data Controller
        $data['blocks'] = []; // Controller Blocks
        $compact = [ $data['singularName'] ];
        foreach ($belongsTo as $otherModel) {
            // foreignKey exists
            if (isset($this->meta['vars'][$otherModel])) {
                $vars = $this->meta['vars'][$otherModel];
                $vars['currentModel'] = $model;
                $compact[] = $vars['pluralName'];
                $data['blocks'][] = $vars;
            }
        }
        $data['compact'] = implode("','", $compact);

        $Templater = new MakeTemplater();
        $result = $Templater->generate('controller', $data);
        if (!file_put_contents($filename, $result)) {
            throw new Exception('Error writing file');
        }
        $this->out(sprintf('%sController created', $controller));
    }

    public function model(string $model = null)
    {
        if (isset($this->args[0])) {
            $model = $this->args[0];
        }
        if ($model === null) {
            $this->showAvailable();
            return ;
        }
        $options = $this->getAvailable();
        
        if (in_array($model, $options) === false) {
            throw new Exception(sprintf('Invalid model %s', $this->args[0]));
        }

        $filename = SRC . DS . 'Model' .DS .$model .'.php';
        if (file_exists($filename)) {
            $result = $this->in(sprintf('%s model already exist, overwrite?', $model), ['y','n'], 'n');
            if ($result === 'n') {
                exit;
            }
        }

        $data = $this->getData($model);
        // Wont use Record blocks since, we validation rules and assocations are two different things
        // Load Assocations
        $data['initialize'] = '';
        $associations = $this->meta['associations'][$model];
        foreach ($associations as $association => $models) {
            if ($models) {
                foreach ($models as $associatedModel) {
                    $data['initialize'] .= '$this->' . $association . "('{$associatedModel}');\n";
                }
            }
        }
        $validationRules = [];
        // Add Validation Rules
        $validate = $this->meta['validate'][$model];
        foreach ($validate as $field => $rules) {
            if ($rules) {
                $buffer = [];
                $validationRules[$field] = [];
                foreach ($rules as $rule) {
                    if (count($rules) === 1) {
                        $validationRules[$field] = [ 'rule' => $rule];
                    } else {
                        $validationRules[$field][$rule] = [ 'rule' => $rule];
                    }
                }
                $export = var_export($validationRules[$field], true);
                $data['initialize'] .= '$this->' . "validate('{$field}',{$export});\n";
            }
        }

        $Templater = new MakeTemplater();
        $result = $Templater->generate('model', $data);
        if (!file_put_contents($filename, $result)) {
            throw new Exception('Error writing file');
        }
        $this->out(sprintf('%s generated', $model));
    }

    public function view(string $controller = null)
    {
        if (isset($this->args[0])) {
            $controller = $this->args[0];
        }
        if ($controller === null) {
            $this->showAvailable(true);
            return ;
        }
        $options = $this->getAvailable(true);
        
        if (in_array($controller, $options) === false) {
            throw new Exception(sprintf('Invalid controller %s', $controller));
        }

        $folder = SRC . DS . 'View' . DS . $controller ;
        if (file_exists($folder)) {
            $result = $this->in(sprintf('%s views already exist, overwrite?', $controller), ['y','n'], 'n');
            if ($result === 'n') {
                exit;
            }
        } else {
            mkdir($folder, 0775);
        }

        $model = Inflector::singularize($controller);
        $data = $this->getData($model);
       
        $data += [
            'controllerUnderscored' => Inflector::underscore($controller)
        ];
        $Templater = new MakeTemplater();

        foreach (['add','edit','index','view'] as $view) {
            $result = $Templater->generate('View/'. $view, $data);
            if (!file_put_contents($folder . DS . $view . '.ctp', $result)) {
                throw new Exception('Error writing file');
            }
            $this->out(sprintf('View/%s generated', $view));
        }
    }


    protected function getData(string $model)
    {
        $data = $this->meta['vars'][$model];
        $data['primaryKey'] = $this->Make->primaryKey($model);
        $fields = array_keys($this->meta['schema'][$model]);
        $key = array_search($data['primaryKey'], $fields);
        if ($key !== false) {
            unset($fields[$key]);
        }
        /**
         * Create a block for each field
         */
        $blocks = [];
        foreach ($fields as $field) {
            $block = $data;
            $block['field'] = $field;
            $block['fieldName'] = Inflector::humanize(Inflector::underscore($field));
            $blocks[] = $block;
        }
        $data['blocks'] = $blocks;
        return $data;
    }

    protected function showAvailable($plural=false)
    {
        $this->out('<cyan>Available Choices:</cyan>');
        foreach ($this->getAvailable($plural) as $item) {
            $this->out('<white>' . $item  . '</white>');
        }
    }

    protected function getAvailable($isPlural=false)
    {
        $data = array_keys($this->meta['schema']);
        if ($isPlural) {
            array_walk($data, function (&$value, &$key) {
                $value = Inflector::pluralize(($value));
            });
        }
 
        return $data;
    }
}
