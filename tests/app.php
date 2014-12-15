<?php
require_once dirname(__FILE__) . '/../inc/html2css.class.php';

class HTML2CSSTests extends PHPUnit_Framework_TestCase
{
    private $default_options = array(
        'css_format' => 'compressed',
        'save_options_cookie' => 0,
    );
    private $html2css;

    function setUp() {
        $this->html2css = new html2css();
        $this->html2css->setOptions($this->default_options);
    }

    function testReturnNoCSSIfEmpty() {
        $html2css = new html2css();
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

    function testExpandedLayout() {
        $html2css = new html2css();
        $this->html2css->setOptions(array(
            'css_format' => 'expanded',
            'save_options_cookie' => 0,
        ));
        $html2css->parse_html('<p class="test">az</p>');
        $this->assertEquals(".test {\n\n}", $html2css->generateCSS());
    }
}
