<?php
require_once dirname(__FILE__) . '/../inc/html2css.class.php';

class HTML2CSSTests extends PHPUnit_Framework_TestCase
{
    private $default_options = array(
        'css_format' => 'compressed',
        'comment_first_block' => 0
    );
    private $html2css;

    function setUp() {
        $this->html2css = new html2css(false);
        $this->html2css->setOptions($this->default_options);
    }

    function testReturnNoCSSIfEmpty() {
        $html2css = new html2css(false);
        $this->assertEquals('', $html2css->generateCSS());
    }

    function testRenderCorrectCSS() {

        // Simple test
        $this->html2css->paths = array();
        $this->html2css->parse_html('<p>az</p>');
        $this->assertEquals('p { }', $this->html2css->generateCSS());
    }

    function testIsDIVIgnored() {

        // DIV is ignored
        $this->html2css->paths = array();
        $this->html2css->parse_html('<div>az</div>');
        $this->assertEquals('', $this->html2css->generateCSS());
    }

    function testTagWithAChildren() {

        // Tag with children
        $this->html2css->paths = array();
        $this->html2css->parse_html('<p><span class="test">az</span></p>');
        $this->assertEquals("p { }\np .test { }", $this->html2css->generateCSS());
    }

    function testSelectorsSimplification() {

        // Test selectors simplification
        $this->html2css->paths = array();
        $this->html2css->parse_html('<ul><li><a href="#">az</a></li></ul>');
        $this->assertEquals("ul { }\nul li { }\nul a { }", $this->html2css->generateCSS());
    }

    function testInputGetsType() {

        // Test if input gets type
        $this->html2css->paths = array();
        $this->html2css->parse_html('<input type="text" name="az" value="az" />');
        $this->assertEquals('input[type="text"] { }', $this->html2css->generateCSS());
    }

    function testLabelGetsFor() {

        // Test if label gets for
        $this->html2css->paths = array();
        $this->html2css->parse_html('<label for="az">Hello</label>');
        $this->assertEquals('label[for="az"] { }', $this->html2css->generateCSS());
    }

    function testDataAttributeIsUsed() {

        // Test if data attribute is used
        $this->html2css->paths = array();
        $this->html2css->parse_html('<a href="#" data-test="test"></a>');
        $this->assertEquals('a[data-test="test"] { }', $this->html2css->generateCSS());
    }

    function testDataAttributeIsOverridden() {

        // Test if data attribute is overridden by a class
        $this->html2css->paths = array();
        $this->html2css->parse_html('<a href="#" data-test="test" class="az"></a>');
        $this->assertEquals('.az { }', $this->html2css->generateCSS());
    }

    function testExpandedLayout() {
        $html2css = new html2css(false);
        $html2css->setOptions(array(
            'css_format' => 'expanded'
        ));
        $html2css->parse_html('<p class="test">az</p>');
        $this->assertEquals(".test {\n\n}", $html2css->generateCSS());
    }
}
