<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console\Task;

use Origin\Console\Shell;
use Origin\Core\ConfigTrait;

class Task
{
    use ConfigTrait;

    /**
     * Holds the shell object
     *
     * @var Shell
     */
    protected $shell = null;
    /**
     * Holds the Task Registry
     *
     * @var TaskRegistry
     */
    protected $taskRegistry = null;
    
    /**
     * Array of tasks and config. This built during construct using $tasks
     *
     * @var array
     */
    protected $_tasks = [];

    public function __construct(Shell $shell, array $config =[])
    {
        $this->taskRegistry = $shell->taskRegistry();

        $this->config($config);
        $this->initialize($config);
    }

    /**
     * Handle lazy loading
     */
    public function __get($name)
    {
        if (isset($this->_tasks[$name])) {
            $this->{$name} = $this->taskRegistry()->load($name, $this->_tasks[$name]);
       
            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }
    /**
    * Sets another Task to be loaded within this Task
     *
     * @param string $task
     * @param array $config
     * @return void
     */
    public function loadTask(string $task, array $config = [])
    {
        $config = array_merge(['className' => $task.'Task'], $config);
        $this->_tasks[$task] = $config;
    }

    /**
     * Loads Multiple Tasks through the loadTask method
     *
     * @param array $tasks
     * @return void
     */
    public function loadTasks(array $tasks)
    {
        foreach ($tasks as $task => $config) {
            if (is_int($task)) {
                $task = $config;
                $config = [];
            }
            $this->loadTask($task, $config);
        }
    }

    /**
     * This is called when task is loaded for the first time
     */
    public function initialize(array $config)
    {
    }

    /**
     * This called after the shell startup but before the shell method.
     */
    public function startup()
    {
    }

    /**
     * This is called after the shell method but before the shell shutdown
     */
    public function shutdown()
    {
    }

    /**
     * Returns the current shell where the task is loaded
     *
     * @return void
     */
    public function shell()
    {
        return $this->taskRegistry()->shell();
    }

    /**
    * Gets the componentRegistry
    *
    * @return void
    */
    public function taskRegistry()
    {
        return $this->taskRegistry;
    }


    /**
         * Outputs to the console text
         *
         * @param string $data
         * @param boolean $newLine
         * @return void
         */
    public function out(string $data, $newLine = true)
    {
        $this->shell()->out($data, $newLine);
    }
}
