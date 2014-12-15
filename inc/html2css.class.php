<?php
class html2css
{
    public $paths = array();
    private $conf = array(
        'cookie_name' => 'html2css_options',
        'cookie_duration' => 31536000,
    );
    public $choices = array(
        'css_format' => array(
            'compressed' => 'Compressed',
            'expanded' => 'Expanded',
        ) ,
        'comment_first_block' => array(
            'No',
            'Yes',
        ) ,
        'save_options_cookie' => array(
            'No',
            'Yes',
        )
    );
    private $options = array(
        'css_format' => array(
            'name' => 'CSS Format',
            'type' => 'choice',
            'value' => 'expanded'
        ) ,
        'comment_first_block' => array(
            'name' => 'Comment first block',
            'type' => 'choice',
            'value' => 0
        ) ,
        'ignored_selectors' => array(
            'type' => 'array',
            'value' => array(
                'div'
            )
        ) ,
        'ignored_nodes' => array(
            'type' => 'array',
            'value' => array(
                'body',
                'br',
                'head',
                'html',
                'link',
                'meta',
                'option',
                'response',
                'script',
                'style',
                'title',
            )
        ) ,
        'bem_strings' => array(
            'type' => 'array',
            'value' => array(
                '--',
                '__'
            )
        ) ,
        'parent_strings' => array(
            'type' => 'array',
            'value' => array(
                '___'
            )
        ) ,
        'save_options_cookie' => array(
            'name' => 'Save options in a cookie',
            'type' => 'choice',
            'value' => 1
        )
    );

    function __construct() {
        $this->paths = array();
        $this->setOptions();
    }

    /* ----------------------------------------------------------
      Parsing
    ---------------------------------------------------------- */

    function parse_html($content) {
        $content = trim($content);

        /* Extract dom */
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($content);
        libxml_clear_errors();

        /* Parse nodes */
        $_childPath = $doc->childNodes;
        foreach ($_childPath as $sNode) {
            $this->parseNode($sNode, 0);
        }
    }

    function parseNode($node, $hasParent) {

        /* Kill if text node */
        if ($node->nodeType != 1) {
            return 0;
        }

        /* Dont touch some nodes */
        if (!in_array($node->tagName, $this->options['ignored_nodes'])) {

            /* Get element path */
            $_path = $this->extractNodePath($node);
            if (!in_array($_path, $this->paths) && !empty($_path)) {
                $this->paths[] = $_path;
            }
        }

        /* Check child path */
        $_childPath = $node->childNodes;
        foreach ($_childPath as $sNode) {
            $this->parseNode($sNode, 1);
        }
    }

    function extractNodePath($_parentNode) {

        $_rPathItems = array(
            $this->extractNodeIdentity($_parentNode)
        );

        /* Construct path with parent nodes */
        while (isset($_parentNode->parentNode, $_parentNode->parentNode->tagName)) {
            $_parentNode = $_parentNode->parentNode;
            if ($_parentNode->tagName == 'body') {
                break;
            }

            /* Extract parent node identity */
            $_rPathItems[] = $this->extractNodeIdentity($_parentNode);
        }
        $_pathItems = array_reverse($_rPathItems);

        /* Clean up path Items */
        $_pathItems = $this->filter_IgnoredNodes($_pathItems);
        $_pathItems = $this->filter_IgnoredSelectors($_pathItems);
        $_pathItems = $this->filter_ParentBEM($_pathItems);
        $_pathItems = $this->filter_ParentContainedClassname($_pathItems);

        $_path = implode(' ', $_pathItems);

        return $_path;
    }

    function extractNodeIdentity($node) {

        /* Default : tagName */
        $_nodeIdentity = $node->tagName;

        /* Input : add type */
        if ($node->tagName == 'input') {
            $type = $node->getAttribute('type');
            if (!empty($type)) {
                $_nodeIdentity = 'input[type="' . $type . '"]';
            }
        }

        /* Last element classname if available */
        $_attrClass = trim($node->getAttribute('class'));
        $_cleanedClassNames = array();
        if (!empty($_attrClass)) {
            $_className = explode(' ', $_attrClass);
            foreach ($_className as $_pClass) {
                if (!empty($_pClass)) {
                    $_cleanedClassNames[] = $_pClass;
                }
            }
            $_nodeIdentity = '.' . end($_cleanedClassNames);
        }
        return $_nodeIdentity;
    }

    /* ----------------------------------------------------------
      Generation
    ---------------------------------------------------------- */

    function generateCSS() {
        if (empty($this->paths)) {
            return '';
        }

        // Options
        $_generatedCSS = '';
        $_css_format = $this->getOption('css_format');
        $_comment_first_block = $this->getOption('comment_first_block');

        // Set comment
        if ($_comment_first_block == 1) {
            $_blockname = str_replace(array(
                '.',
                '-',
                '_'
            ) , ' ', $this->paths[0]);
            $_blockname = ucwords($_blockname);
            $_generatedCSS.= "/* " . trim($_blockname) . "\n-------------------------- */\n\n";
        }

        // Add rules
        foreach ($this->paths as $_path) {
            $_generatedCSS.= $_path;
            switch ($_css_format) {
                case 'compressed':
                    $_generatedCSS.= " { }\n";
                    break;

                default:
                    $_generatedCSS.= " {\n\n}\n\n";
            }
        }
        return trim($_generatedCSS);
    }

    /* ----------------------------------------------------------
      Options
    ---------------------------------------------------------- */

    /**
     * Return an option
     * @param  string $id   option id
     * @return mixed        option value
     */
    private function getOption($id) {
        if (isset($this->options[$id])) {
            return $this->options[$id]['value'];
        }
        return false;
    }

    public function setOptions($options = false) {

        if ($options === false && isset($_COOKIE[$this->conf['cookie_name']])) {
            $options = json_decode($_COOKIE[$this->conf['cookie_name']], 1);
        }

        if (!is_array($options)) {
            return;
        }

        $default_options = $this->options;
        $cookie_options = array();

        foreach ($this->options as $_option_id => $_default_option) {

            // Force to default option value
            $_option = $_default_option['value'];
            if (isset($options[$_option_id])) {
                $_option = $options[$_option_id];
            }
            $_canImport = true;
            switch ($_default_option['type']) {
                case 'array':

                    // option should be an array
                    if (!is_array($_option)) {
                        $_canImport = false;
                    } else {
                        foreach ($_option as $v) {

                            // Should only contains strings
                            if (!preg_match('/^[a-zA-Z0-9_\-\.]*$/', $v)) {
                                $_canImport = false;
                            }
                        }
                    }
                    break;

                case 'choice':

                    // option should be in corresponding array
                    if (!array_key_exists($_option, $this->choices[$_option_id])) {
                        $_canImport = false;
                    }

                    break;
            }

            if ($_canImport) {
                $this->options[$_option_id]['value'] = $_option;
                $cookie_options[$_option_id] = $_option;
            }
        }

        // Save options in a  cookie
        if ($this->getOption('save_options_cookie')) {
            setcookie($this->conf['cookie_name'], json_encode($cookie_options) , time() + $this->conf['cookie_duration']);
        }
    }

    /* ----------------------------------------------------------
      Filters
    ---------------------------------------------------------- */

    /* Remove ignored nodes */
    private function filter_IgnoredNodes($pathItems) {
        $_tmpItems = array();
        $_ignored_nodes = $this->getOption('ignored_nodes');
        foreach ($pathItems as $_item) {
            if (!in_array($_item, $_ignored_nodes)) {
                $_tmpItems[] = $_item;
            }
        }
        return $_tmpItems;
    }

    /* Remove ignored selectors */
    private function filter_IgnoredSelectors($pathItems) {
        $_tmpItems = array();
        $_ignored_selectors = $this->getOption('ignored_selectors');
        foreach ($pathItems as $_item) {
            if (!in_array($_item, $_ignored_selectors)) {
                $_tmpItems[] = $_item;
            }
        }
        return $_tmpItems;
    }

    /* Reset path if BEM or Parent detected on a parent item */
    private function filter_ParentBEM($pathItems) {
        $_tmpItems = array();
        $_parentStrings = $this->getOption('bem_strings') + $this->getOption('parent_strings');
        foreach ($pathItems as $i => $_item) {
            if (isset($pathItems[$i + 1])) {
                $isParent = false;
                foreach ($_parentStrings as $string) {
                    if (strpos($_item, $string) !== false) {
                        $isParent = true;
                    }
                }
                if ($isParent) {
                    $_tmpItems = array();
                }
            }
            $_tmpItems[] = $_item;
        }
        return $_tmpItems;
    }

    /* Do not use parent if contained in item and is a classname */
    private function filter_ParentContainedClassname($pathItems) {
        $_tmpItems = array();
        foreach ($pathItems as $i => $_item) {
            $_keepItem = true;
            if (isset($pathItems[$i + 1])) {
                $_childItem = $pathItems[$i + 1];
                $_itemIsClass = ($_item[0] == '.');
                $_parentContained = (strpos($_childItem, $_item) !== false);
                if ($_itemIsClass && $_parentContained) {
                    $_keepItem = false;
                }
            }
            if ($_keepItem) {
                $_tmpItems[] = $_item;
            }
        }
        return $_tmpItems;
    }

    /* ----------------------------------------------------------
      HTML Helpers
    ---------------------------------------------------------- */

    public function generateSelect($id) {

        // Check values
        if (!isset($this->choices[$id])) {
            return '';
        }
        $option_value = $this->options[$id]['value'];
        $html_id = 'option_' . $id;
        $values = $this->choices[$id];

        // Set HTML
        $return = '<div class="option-block">';
        $return.= '<label for="' . $html_id . '">' . $this->options[$id]['name'] . ' :</label>';
        $return.= '<select name="options[' . $id . ']" id="' . $html_id . '">';
        foreach ($values as $key => $value) {
            $return.= '<option ' . ($option_value == $key ? 'selected="selected"' : '') . ' value="' . $key . '">' . $value . '</option>';
        }
        $return.= '</select>';
        $return.= '</div>';
        return $return;
    }
}
