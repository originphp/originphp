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

namespace Origin\Test\Model;

use Origin\Model\Behavior\DelocalizeBehavior;
use Origin\Model\Model;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Entity;
use Origin\Utility\Date;
use Origin\Utility\Number;

class DelocalizeBehaviorTest extends OriginTestCase
{
    public $fixtures = ['Origin.Deal'];

    public function initialize()
    {
       $this->loadFixture('Origin.Deal');
       $this->Deal = new Model(['name' => 'Deal','datasource'=>'test']); // Create model Dynamically
       Date::locale(['date'=>'d/m/Y','time'=>'H:i','datetime'=>'d/m/Y H:i','timezone'=>'Europe/London']);
       Number::locale(['currency'=>'USD','thousands'=>',','decimals'=>'.']);
    }
 
    public function testBehavior()
    {
        
        $behavior = new DelocalizeBehavior($this->Deal);

        $deal = new Entity([
            'amount' => '1,234,567.89',
            'close_date' =>'11/06/2019',
            'created' => '11/06/2019 10:27',
            'confirmed' => '10:27'
        ]);
        $behavior->beforeValidate($deal);

        $this->assertEquals(1234567.89,$deal->amount);
        $this->assertEquals('2019-06-11',$deal->close_date);
        $this->assertEquals('2019-06-11 09:27:00',$deal->created);
       
        /**
         * This is correct. Without date, can't convert time due to DST
         */
        $this->assertEquals('10:27:00',$deal->confirmed);
       
    }

    public function shutdown(){
        Date::locale([
            'timezone' => 'UTC',
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i',
            'time' => 'H:i'
        ]);
    }
    
}