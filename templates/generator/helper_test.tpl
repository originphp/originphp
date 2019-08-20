<?php
namespace %namespace%\View\Helper;

use %namespace%\View\Helper\AppHelper;

use Origin\TestSuite\OriginTestCase;
use Origin\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\View\View;
use App\View\Helper\%class%Helper;

class %class%HelperTest extends OriginTestCase
{
    /**
    * @var \App\View\Helper\%class%Helper
    */
    protected $%class% = null;

    public function startup()
    {
        parent::startup();
        $controller = new Controller(new Request(),new Response());
        $view = new View($controller);
        $this->%class% = new %class%Helper($view);
    }
}