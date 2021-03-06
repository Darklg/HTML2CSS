<?php

class HTML2CSSTests extends PHPUnit_Framework_TestCase
{
    private $default_options = array(
        'css_format' => 'compressed',
        'comment_first_block' => 0,
        'create_first_last_child' => 0,
        'create_following_selector' => 0,
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

    /* ----------------------------------------------------------
      Testing class
    ---------------------------------------------------------- */

    function testGenerateEmptySelect() {

        // Testing if an invalid select is always empty
        $html2css = new html2css(false);
        $this->assertEquals('', $html2css->generateSelect('test'));
    }

    function testGenerateValidSelect() {

        // Testing if a valid option has a correct select generated.
        $html2css = new html2css(false);
        $this->assertEquals('<div class="option-block"><label for="option_css_format">CSS Format :</label><select name="options[css_format]" id="option_css_format"><option selected="selected" value="compressed">Compressed</option><option  value="expanded">Expanded</option></select></div>', $html2css->generateSelect('css_format'));
    }

    function testGettingInvalidOption() {
        $html2css = new html2css(false);
        $this->assertEquals(false, $html2css->getOption('test'));
    }

    /* ----------------------------------------------------------
      Testing conversion
    ---------------------------------------------------------- */

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

    function testIfGetRole() {

        // Test if field gets role
        $this->html2css->paths = array();
        $this->html2css->parse_html('<p role="banner">Banner</p>');
        $this->assertEquals('p[role="banner"] { }', $this->html2css->generateCSS());
    }

    function testIfGetRoleIfNotDataAttribute() {

        // Test if field gets role if not data attribute
        $this->html2css->paths = array();
        $this->html2css->parse_html('<p data-oups="test" role="banner">Banner</p>');
        $this->assertEquals('p[data-oups="test"] { }', $this->html2css->generateCSS());
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

    function testIDIsUsed() {

        // Test if ID attribute is used
        $this->html2css->paths = array();
        $this->html2css->parse_html('<strong id="test">test</strong>');
        $this->assertEquals('#test { }', $this->html2css->generateCSS());
    }

    function testIDIsUsedInLastResort() {

        // Test if ID attribute is used only if node identity is equal to tagName
        $this->html2css->paths = array();
        $this->html2css->parse_html('<strong class="test" id="test">test</strong>');
        $this->assertEquals('.test { }', $this->html2css->generateCSS());
    }

    function testDemoCode() {

        // Test if demo code always returns a good result
        $this->html2css->paths = array();

        $demo_code_html = file_get_contents(dirname(__FILE__) . '/../assets/html/demo-code.html');
        $demo_code_css = trim(file_get_contents(dirname(__FILE__) . '/../assets/html/demo-code.css'));

        $this->html2css->parse_html($demo_code_html);
        $this->assertEquals($demo_code_css, $this->html2css->generateCSS());
    }

    function testIgnoredAttributes() {

        // Test ignored attributes
        $this->html2css->paths = array();
        $this->html2css->parse_html('<a href="#" data-ng-repeat="n in [1,2,3]"></a>');
        $this->assertEquals("a { }", $this->html2css->generateCSS());
    }

    function testClearBeforeIDs() {

        // Test if the parent elements of an ID are ignored
        $this->html2css->paths = array();
        $this->html2css->parse_html('<p><span id="az">az</span></p>');
        $this->assertEquals("p { }\n#az { }", $this->html2css->generateCSS());
    }

    function testFollowingSelectors() {

        // Test for following ( * + * ) selectors
        $html2css = new html2css(false);
        $html2css->setOptions(array(
            'create_following_selector' => 1
        ));
        $html2css->parse_html('<ul><li>az</li><li>az</li><li>az</li></ul>');
        $this->assertEquals("ul { }\nul li { }\nul li + li { }", $html2css->generateCSS());
    }

    function testFirstLastChild() {

        // Test for :first-child & :last-child creation
        $html2css = new html2css(false);
        $html2css->setOptions(array(
            'create_first_last_child' => 1
        ));
        $html2css->parse_html('<ul><li>az</li><li>az</li><li>az</li></ul>');
        $this->assertEquals("ul { }\nul li { }\nul li:first-child { }\nul li:last-child { }", $html2css->generateCSS());
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
