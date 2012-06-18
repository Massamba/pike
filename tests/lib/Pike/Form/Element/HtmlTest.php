<?php

class HtmlTestCase extends PHPUnit_Framework_TestCase
{

    public function testOutput()
    {
	$view = new Zend_View();
	$view->addHelperPath(LIBRARY_PATH . '/Pike/View/Helper', 'Pike_View_Helper'); 

        $element = new Pike_Form_Element_Html('test');
        $element->setLabel('pike');
        
	$expected = <<<EOS
<dt id="test-label"><label for="test" class="optional">pike</label></dt>
<dd id="test-element">
</dd>
EOS;

        $this->assertEquals($element->render($view), $expected);
    }

}