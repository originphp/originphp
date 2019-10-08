<?php

namespace Origin\Test\Console\Command;

use Origin\Utility\Folder;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class GenerateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    protected $fixtures = ['Origin.Bookmark','Origin.BookmarksTag','Origin.Tag','Origin.User'];

    public function testScaffoldUnkownModel()
    {
        $this->exec('generate --force --connection=test scaffold Foo');
        $this->assertExitError();
        $this->assertErrorContains('Unkown model Foo');
    }
    public function testGenerateScaffold()
    {
        $this->exec('generate --force --connection=test scaffold Bookmark');
        
        $this->assertExitSuccess();
        /**
         * Run the generator on the bookmarks app and test its all working as accepted before changing Hashes
         */
        $this->assertFileHash('06aafa6e7d30699ef9556987af8f34d3', SRC . DS . 'Model' . DS . 'Bookmark.php');
        unlink(SRC . DS . 'Model' . DS . 'Bookmark.php');
       
        $this->assertFileHash('ae4389221897c48b12d35b3755969059', SRC . DS .'Http'. DS . 'Controller' . DS . 'BookmarksController.php');
        unlink(SRC . DS .'Http'. DS .  'Controller' . DS . 'BookmarksController.php');
     
        $this->assertFileHash('9298725c00be555fb7b8751484f41780', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'add.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'add.ctp');

        $this->assertFileHash('11ed3ae60350bfc07c170aae750e02d1', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'edit.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'edit.ctp');

        $this->assertFileHash('c8d3d6cd1474cee688f8173d1a640b08', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'index.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'index.ctp');

        $this->assertFileHash('193fa37f0d96400e39d025b6a0f92a2d', SRC . DS . 'Http'. DS .'View' . DS . 'Bookmarks' . DS . 'view.ctp');
        unlink(SRC . DS . 'Http'. DS . 'View' . DS . 'Bookmarks' . DS . 'view.ctp');
    }

    public function testInvalidGenerator()
    {
        $this->exec('generate foo');
        $this->assertExitError();
        $this->assertErrorContains('Unkown generator foo');
    }

    public function testInvalidName()
    {
        $this->exec('generate command bar-foo');
        $this->assertExitError();
        $this->assertErrorContains('Invalid name format');
    }

    public function testNoName()
    {
        $this->exec('generate command');
        $this->assertExitError();
        $this->assertErrorContains('You must provide a name e.g. Single,DoubleWord');
    }

    public function testInvalidSchema()
    {
        $this->exec('generate model Foo foo bar');
        $this->assertExitError();
        $this->assertErrorContains('Invalid format for foo, should be name:type');
    }
    public function testGenerateConcernModel()
    {
        $this->exec('generate --force concern_model Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Concern'.DS.'Dummy.php';
        $this->assertOutputContains('app/Model/Concern/Dummy.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('c08bbe4ee37ea3434e44ed90dee7d274', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'DummyTest.php';
        $this->assertOutputContains('TestCase/Model/Concern/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('dde4a5754b77ce85246941246485f68d', $filename);
        unlink($filename);
    }

    public function testGenerateConcernController()
    {
        $this->exec('generate --force concern_controller Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS. 'Controller'.DS.'Concern'.DS.'Dummy.php';
        $this->assertOutputContains('app/Http/Controller/Concern/Dummy.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('dce76967472c9b05339258cd6136c6aa', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'Concern'.DS.'DummyTest.php';
        $this->assertOutputContains('TestCase/Http/Controller/Concern/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('5c7642139d28c8b50284b72c27cc5c96', $filename);
        unlink($filename);
    }

    public function testGenerateRepository()
    {
        $this->exec('generate --force repository Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Repository'.DS.'DummyRepository.php';
        $this->assertOutputContains('app/Model/Repository/DummyRepository.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('6de4e3286718202163357be3f120b041', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Repository'.DS.'DummyRepositoryTest.php';
        $this->assertOutputContains('TestCase/Model/Repository/DummyRepositoryTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('3f6a31b30cdea43b12b0167d06453dda', $filename);
        unlink($filename);
    }

    public function testInteractive()
    {
        // this can be annoying when changes are maded
        @unlink(SRC.DS.'Model'.DS.'Concern'.DS.'Fooable.php');
        @unlink(ESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'FooableTest.php');

        $this->exec('generate', ['concern_model','Fooable']);
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Concern'.DS.'Fooable.php';
        $this->assertOutputContains('app/Model/Concern/Fooable.php');

        $this->assertFileExists($filename);
        $this->assertFileHash('9172a9ddc39f69243191f44462b0aeac', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'Concern'.DS.'FooableTest.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('6c667e1835e429305d87b52dc7839ba3', $filename);
        unlink($filename);
    }

    public function testGenerateCommand()
    {
        $this->exec('generate --force command Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Console'.DS.'Command'.DS.'DummyCommand.php';
        $this->assertOutputContains('app/Console/Command/DummyCommand.php');
    
        $this->assertFileHash('4464c077e6e94306d1b8faf3d92b686f', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS.'Console'.DS.'Command'.DS.'DummyCommandTest.php';
        $this->assertOutputContains('tests/TestCase/Console/Command/DummyCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('4f55ac867d5e6be53caee7592d21da47', $filename);
        unlink($filename);
    }

    public function testGenerateCommandPlugin()
    {
        $this->exec('generate --force command ContactManager.Duck');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'contact_manager'.DS.'src'.DS.'Console'.DS.'Command'.DS.'DuckCommand.php';
 
        $this->assertOutputContains('contact_manager/src/Console/Command/DuckCommand.php');
        $this->assertFileHash('c4d86f0dd3c4a6c13d857cb6cb432e38', $filename);
        unlink($filename);

        $filename = APP.DS.'plugins'.DS.'contact_manager'.DS.'tests'.DS.'TestCase'.DS.'Console'.DS.'Command'.DS.'DuckCommandTest.php';
     
        $this->assertOutputContains('contact_manager/tests/TestCase/Console/Command/DuckCommandTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('3df2fbe6dad0388ccc8f61afba77cb2c', $filename);
        unlink($filename);

        Folder::delete(APP.DS.'plugins'.DS.'contact_manager', ['recursive' => true]);
    }

    public function testGenerateComponent()
    {
        $this->exec('generate --force component Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Http'.DS.'Controller'.DS.'Component'.DS.'DummyComponent.php';
        $this->assertOutputContains('app/Http/Controller/Component/DummyComponent.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('06f5bf7057d69dce96e35168fe419bfc', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'Component'.DS.'DummyComponentTest.php';
        $this->assertOutputContains('TestCase/Http/Controller/Component/DummyComponentTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('6de2a5f73aeda4bc8d1c48b00e9f7565', $filename);
        unlink($filename);
    }

    public function testGenerateController()
    {
        $this->exec('generate --force controller Dummies');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Http/Controller/DummiesController.php');
        $this->assertFileExists($filename);
  
        $this->assertFileHash('8163dbd3cce2ac75ac648f4ccf80a492', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Http/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('74722383d7ed6093342e2e9e1236c96c', $filename);
        unlink($filename);
    }

    public function testGenerateControllerWithActions()
    {
        $this->exec('generate --force controller Dummies index get_user');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS.'Controller'.DS.'DummiesController.php';
        $this->assertOutputContains('app/Http/Controller/DummiesController.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('f5639a37bf302e2f0446a4ee4d9e6d54', $filename);
        unlink($filename);

        $filename = SRC.DS.'Http'.DS.'View'.DS.'Dummies'.DS .'index.ctp';
        $this->assertOutputContains('app/Http/View/Dummies/index.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('af90a7a0bfcd3a6ff30c0aac82c94c16', $filename);
        unlink($filename);

        $filename = SRC.DS.'Http'.DS.'View'.DS.'Dummies'.DS .'get_user.ctp';
        $this->assertOutputContains('app/Http/View/Dummies/get_user.ctp');
        $this->assertFileExists($filename);
        $this->assertFileHash('9263ed82c0e1859690365808dcd719b0', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Http'.DS.'Controller'.DS.'DummiesControllerTest.php';
        $this->assertOutputContains('tests/TestCase/Http/Controller/DummiesControllerTest.php');
        $this->assertFileExists($filename);
      
        $this->assertFileHash('ff02f4dabb3f0d9b19fc218231da2e3e', $filename);
        unlink($filename);
    }

    public function testGenerateHelper()
    {
        $this->exec('generate --force helper Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Http'.DS.'View'.DS.'Helper'.DS.'DummyHelper.php';
        $this->assertOutputContains('app/Http/View/Helper/DummyHelper.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('353ab0a2fb93dd9dc3164477e9c0a8b5', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Http'.DS.'View'.DS.'Helper'.DS.'DummyHelperTest.php';
       
        $this->assertOutputContains('TestCase/Http/View/Helper/DummyHelperTest.php');
        $this->assertFileExists($filename);

        $this->assertFileHash('d2ba1741ffb089993ab18e758fcef68a', $filename);
        unlink($filename);
    }

    public function testGenerateMailer()
    {
        $this->exec('generate --force mailer Dummy');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Mailer'.DS.'DummyMailer.php';
        $this->assertOutputContains('app/Mailer/DummyMailer.php');
        $this->assertFileExists($filename);
     
        $this->assertFileHash('5eaf99189ea3784eaf93e888a72fa8ee', $filename);
        unlink($filename);
    
        $filename = TESTS.DS.'TestCase'.DS .'Mailer'.DS.'DummyMailerTest.php';
       
        $this->assertOutputContains('TestCase/Mailer/DummyMailerTest.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('1c6e4f0024603465e14301d9348ea494', $filename);
        unlink($filename);

        $filename = SRC.DS.'Mailer'.DS.'Template'.DS .'dummy.html.ctp';
        $this->assertOutputContains('app/Mailer/Template/dummy.html.ctp');
        $this->assertFileHash('dcd7e3b40d5e4d840e8e2ba0a9721a81', $filename);
        unlink($filename);

        $filename = SRC.DS.'Mailer'.DS.'Template'.DS .'dummy.text.ctp';
        $this->assertOutputContains('app/Mailer/Template/dummy.text.ctp');
        $this->assertFileHash('b336631ad91ce8c22975f1bea7c0da4e', $filename);
        unlink($filename);
    }

    public function testGenerateModel()
    {
        $this->exec('generate --force model Dummy name:string description:text');
        $this->assertExitSuccess();

        $filename = SRC.DS.'Model'.DS.'Dummy.php';
        $this->assertOutputContains('app/Model/Dummy.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('cd97ee7d7e95e8860a034c2902b77413', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS.'Model'.DS.'DummyTest.php';
        $this->assertOutputContains('tests/TestCase/Model/DummyTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('2159636286b47b5afa876dc6a643003d', $filename);
   
        unlink($filename);

        $filename = TESTS.DS.'Fixture'.DS.'DummyFixture.php';
        $this->assertOutputContains('tests/Fixture/DummyFixture.php');
        $this->assertFileExists($filename);
        
        $this->assertFileHash('d3cd54bd8aa2d6b22560b51b16d04e56', $filename);
        unlink($filename);

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];
        $filename = APP.DS.'database'.DS.'migrations'.DS.$version.'CreateDummyTable.php';
        
        $this->assertOutputContains("database/migrations/{$version}CreateDummyTable.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('c1ac45eb671fb571e313b97e4acf93d1', $filename);
        unlink($filename);
    }

    public function testGenerateMiddleware()
    {
        $this->exec('generate --force middleware Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Http'.DS.'Middleware'.DS.'DummyMiddleware.php';
        $this->assertOutputContains('app/Http/Middleware/DummyMiddleware.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('7e322d4bf23c2ec92858b3e35a2e7be5', $filename);
        unlink($filename);

        $filename = TESTS.DS.'TestCase'.DS .'Http'.DS . 'Middleware'.DS.'DummyMiddlewareTest.php';
        $this->assertOutputContains('TestCase/Http/Middleware/DummyMiddlewareTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('9c94bd4e7ad86b311200d4bbd48c476f', $filename);
        unlink($filename);
    }

    public function testGenerateJob()
    {
        $this->exec('generate --force job Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Job'.DS.'DummyJob.php';
        $this->assertOutputContains('app/Job/DummyJob.php');
        $this->assertFileExists($filename);
       
        $this->assertFileHash('83f3b5f8283867b52cc6b2eb3a238435', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Job'.DS.'DummyJobTest.php';
        
        $this->assertOutputContains('TestCase/Job/DummyJobTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('27e6b98f491d555a056ce929b30df5d6', $filename);
        unlink($filename);
    }

    public function testGenerateService()
    {
        $this->exec('generate --force service Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Service'.DS.'DummyService.php';
        $this->assertOutputContains('app/Service/DummyService.php');
        $this->assertFileExists($filename);
    
        $this->assertFileHash('015514584db8b0f72cbcc743df38f474', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Service'.DS.'DummyServiceTest.php';
        $this->assertOutputContains('TestCase/Service/DummyServiceTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('f646e791a1a6d9c38cf371a3ccaf22d9', $filename);
        unlink($filename);
    }

    public function testGenerateListener()
    {
        $this->exec('generate --force listener Dummy');
        $this->assertExitSuccess();
        $filename = SRC.DS.'Listener'.DS.'DummyListener.php';
        $this->assertOutputContains('app/Listener/DummyListener.php');
        $this->assertFileExists($filename);
    
        $this->assertFileHash('1bd53a0cdd0f3e549ba321967d9587b4', $filename);
        unlink($filename);
        
        $filename = TESTS.DS.'TestCase'.DS .'Listener'.DS.'DummyListenerTest.php';
        $this->assertOutputContains('TestCase/Listener/DummyListenerTest.php');
        $this->assertFileExists($filename);
        $this->assertFileHash('9fe80488ae506a109468569f76598a76', $filename);
        unlink($filename);
    }

    public function testGenerateMigration()
    {
        $this->exec('generate --force migration Dummy');
        $this->assertExitSuccess();

        preg_match('/[0-9]{14}/', $this->output(), $match);
        $version = $match[0];

        $filename = APP.DS.'database'.DS.'migrations'.DS.$version.'Dummy.php';
        
        $this->assertOutputContains("database/migrations/{$version}Dummy.php");
        $this->assertFileExists($filename);
        $this->assertFileHash('7ae46d1b60868d54f09ccce43ca35068', $filename);
        unlink($filename);
    }

    public function testPlugin()
    {
        $this->exec('generate --force plugin Dummy');
        $this->assertExitSuccess();

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Http'.DS.'Controller'.DS.'DummyApplicationController.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('b056004d9383d8b6cc982dbd17a1cb60', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'src'.DS.'Model'.DS.'DummyApplicationModel.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('bfe134b484d8f8e9446191c76cc2c5b0', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'config'.DS.'routes.php';
        $this->assertFileExists($filename);
        $this->assertFileHash('6f107423fcdde9f10e7b099f8149b3cf', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'phpunit.xml';
        $this->assertFileExists($filename);
        $this->assertFileHash('8cb27d99afeb20945a7ad5e0babebb27', $filename);

        $filename = APP.DS.'plugins'.DS.'dummy'.DS.'composer.json';
        $this->assertFileExists($filename);
        $this->assertFileHash('3aac15995b02c9505537ccdb85130f31', $filename);

        Folder::delete(APP.DS.'plugins'.DS.'dummy', ['recursive' => true]);
    }

    /*

        'plugin' => 'Generates a plugin skeleton',
        */

    protected function assertFileHash(string $hash, String $filename)
    {
        $this->assertEquals($hash, md5(file_get_contents($filename)));
    }
}
