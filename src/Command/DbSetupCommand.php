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

class DbSetupCommand extends Command
{
    protected $name = 'db:setup';

    protected $description = 'Creates the database,loads schema and seeds the database';

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
        $name = $this->arguments('name') ?? 'schema';

        # Create arguments
        $schema = $name;
        $seed = 'seed';
        # Have to use seed here
        list($plugin, $null) = pluginSplit($name);
        if ($plugin) {
            $seed = "{$plugin}.seed";
        }
    
        $datasource = $this->options('datasource');
        $this->runCommand('db:create', [
            '--datasource' => $datasource,
        ]);
   
        $this->io->nl();
    
        $this->runCommand('db:schema:load', [
            '--datasource' => $datasource,
            '--type' => $this->options('type'),
            $schema,
        ]);
     
        $this->io->nl();

        $this->runCommand('db:seed', [
            '--datasource' => $datasource,
            '--type' => $this->options('type'),
            $seed,
        ]);
    }
}
