<?php
declare(strict_types = 1);
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

namespace Origin\Command;

use Origin\Core\Config;

class DbResetCommand extends Command
{
    protected $name = 'db:reset';

    protected $description = 'Drops the database and then runs setup';

    public function initialize()
    {
        $this->addOption('datasource', [
            'description' => 'Use a different datasource','short' => 'ds','default' => 'default',
        ]);
        $this->addArgument('name', [
            'description' => 'schema_name or Plugin.schema_name',
        ]);

        $this->addOption('type', [
            'description' => 'Use sql or php file',
            'default' => Config::read('Schema.format'),
        ]);
    }
 
    public function execute()
    {
        $datasource = $this->options('datasource');
        $name = $this->arguments('name') ?? 'schema';

        $this->runCommand('db:drop', [
            '--datasource' => $datasource,
        ]);
       
        $this->runCommand('db:setup', [
            '--datasource' => $datasource,
            '--type' => $this->options('type'),
            $name,
        ]);
    }
}
