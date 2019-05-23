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

namespace Origin\Command;
use Origin\Command\Command;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;

class DbDropCommand extends Command
{
    protected $name = 'db:drop';

    protected $description = 'Drops the database for the datasource';

    public function initialize(){
        $this->addOption('datasource', [
            'description' => 'Use a different datasource',
            'short' => 'ds',
            'default' => 'default'
            ]);
    }
 
    public function execute(){
        $datasource = $this->options('datasource');
        $config = ConnectionManager::config($datasource);
        if(!$config){
            $this->throwError("{$datasource} datasource not found");
        }

        $database = $config['database'];
        $config['database'] = null;
        $connection = ConnectionManager::create('tmp', $config); //
        
        try {
            $result = $connection->execute("DROP DATABASE {$database};");
            ConnectionManager::drop('tmp');
            $this->io->status('ok', sprintf('Database `%s` dropped',$database));
        } catch (DatasourceException $ex) {
            $this->throwError($ex->getMessage());
        }
    }

}