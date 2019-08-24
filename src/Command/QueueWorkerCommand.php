<?php
namespace Origin\Command;

use Origin\Job\Job;
use Origin\Job\Queue;

class QueueWorkerCommand extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'queue:worker';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Proccesses jobs in the queue';

    /**
     * Holds the Queue Connection
     *
     * @var \Origin\Queue\Engine\BaseEngine;
     */
    protected $connection = null;

    /**
     * Flag
     *
     * @var boolean
     */
    protected $stopped = false;

    /**
     * The initialize method (called after construct)
     *
     * @return void
     */
    public function initialize()
    {
        $this->addArgument('queue', [
            'description' => 'a queue name or a list of queues seperated by spaces',
            'type' => 'array',
        ]);
      
        $this->configureOptions();
        $this->configureStyles();
    }

    /**
     * The main command
     *
     * @return void
     */
    public function execute()
    {
        if ($this->supportsSignals()) {
            $this->setupSignalHandler();
        }

        $this->connection = Queue::connection($this->options('connection'));
        $queues = $this->arguments('queue') ?: ['default'];

        if ($this->options('daemon')) {
            $this->daemon($queues);
        } else {
            $this->worker($queues);
        }
    }

    /**
     * Checks that worker is running
     *
     * @param integer $iteration
     * @return boolean
     */
    protected function isRunning() : bool
    {
        return ($this->stopped === false);
    }

    /**
     * The daemon process, will run one job in each queue, then circle again to create distributed
     * effect (Round Robbin). This prevents one queue getting all the attention if its slow running
     * e.g sending emails
     *
     * @param array $queues
     * @return void
     */
    protected function daemon(array $queues) : void
    {
        $sleep = $this->options('sleep');

        while ($this->isRunning()) {
            $this->worker($queues, $sleep);
        }
    }

    /**
     * Works the queue runs 1 job for each queue
     *
     * @param array $queues
     * @param integer $sleep time to send worker to sleep when there were no jobs
     * @return void
     */
    protected function worker(array $queues, int $sleep = null) : void
    {
        $ranJobs = false;
        foreach ($queues as $queue) {
            if ($this->processQueue($queue)) {
                $ranJobs = true;
            }
            $this->checkMemoryUsage();
        }

        if (! $ranJobs and $sleep) {
            sleep($sleep);
        }
    }

    /**
     * Proceses the next job in the queue
     *
     * @param string $queue
     * @return bool
     */
    protected function processQueue(string $queue) : bool
    {
        $job = $this->connection->fetch($queue);

        if (! $job) {
            return false;
        }

        $this->dispatchJob($job);

        return true;
    }

    /**
     * Handles the actual dispatching of the job
     *
     * @param \Origin\Queue\Job $job
     * @return void
     */
    protected function dispatchJob(Job $job) : void
    {
        $result = true;
       
        if ($this->supportsSignals()) {
            $this->setTimeout($job->timeout);
        }

        $this->writeOutput('<text>[{date}]</text> <cyan>{type}</cyan> <text>{name}</text> <green>{id}</green>', [
            'date' => date('Y-m-d G:i:s'),
            'type' => $job->attempts() === 0?'Run':'Retry #' . $job->attempts(),
            'name' => $job->name,
            'id' => $job->id(),
        ], false);

        $start = time();
        $result = $job->dispatchNow();
        $end = time();

        $this->writeOutput('<text> ({took}s)</text> {status}', [
            'took' => $end - $start,
            'status' => $result ? '<pass> OK </pass>':'<fail> FAILED </fail>',
        ]);

        if ($this->supportsSignals()) {
            $this->unsetTimeout();
        }
    }

    /**
     * Slightly modified output with option add to newline
     *
     * @param string|array $message
     * @param array $context
     * @param boolean $newLine
     * @return void
     */
    public function writeOutput($message, array $context = [], bool $newLine = true) : void
    {
        $message = $this->interpolate($message, $context);
        if ($newLine) {
            $message = implode("\n", $message) . "\n";
        }
        $this->io->write($message);
    }

    /**
       * Checks that PCNTL is supported
       *
       * @return boolean
       */
    protected function supportsSignals() : bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * Installs the signal for capturing timeout
     *
     * @param integer $seconds
     * @return void
     */
    protected function setTimeout(int $seconds) : void
    {
        pcntl_alarm($seconds);
        pcntl_signal(SIGALRM, [$this, 'timedout']);
    }

    /**
     * Removes the timeout alarm
     *
     * @return void
     */
    protected function unsetTimeout() : void
    {
        pcntl_alarm(0);
    }

    /**
    * Timeout
    *
    * @return void
    */
    protected function timedout() : void
    {
        $seconds = $this->options('timeout');
       
        if (extension_loaded('posix')) {
            $this->io->warning('Killing process');
            posix_kill(getmypid(), SIGKILL);
        }

        $this->throwError("Maximum timeout {$seconds} reached");
    }

    /**
     * Setup signal capture
     *
     * @return void
     */
    protected function setupSignalHandler() : void
    {
        /**
         * declare ticks captures CTRL-c after job finished, pcntl_async_signals(true) only works
         * after all jobs finished.
         */
        declare(ticks = 1);
        pcntl_signal(SIGTERM, [$this, 'cancelJob']);
        pcntl_signal(SIGINT, [$this, 'cancelJob']);
    }

    /**
     * Cancels job
     *
     * @return void
     */
    protected function cancelJob() : void
    {
        $this->warning('Shutting down.....');
        $this->stopped = true;
    }

    /**
     * Checks that maximum memory is not reached
     *
     * @return void
     */
    protected function checkMemoryUsage()
    {
        $maximum = $this->options('memory');
        if (memory_get_usage(true) / 1024 / 1024 >= $maximum) {
            $this->throwError("Maximum memory {$maximum} mb reached");
        }
    }

    /**
     * Configures command line options
     *
     * @return void
     */
    protected function configureOptions()
    {
        $this->addOption('connection', [
            'description' => 'The connection to use',
            'default' => 'default',
            'type' => 'string',
        ]);

        $this->addOption('daemon', [
            'description' => 'Starts the worker as daemon',
            'type' => 'boolean',
            'short' => 'd',
        ]);

        $this->addOption('sleep', [
            'description' => 'Number of seconds to sleep when no jobs are available',
            'type' => 'integer',
            'default' => 5,
        ]);

        $this->addOption('memory', [
            'description' => 'Set a memory limit to be used',
            'default' => 128,
            'type' => 'integer',
        ]);
    }

    /**
     * CLI CSS
     *
     * @return void
     */
    protected function configureStyles()
    {
        # Setup
        $this->io->styles('pass', [
            'background' => 'green','color' => 'white',
        ]);

        $this->io->styles('fail', [
            'background' => 'lightRed','color' => 'white',
        ]);
    }
}