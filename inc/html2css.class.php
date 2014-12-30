<?php
class html2css
{
    public $paths = array();
    private $conf = array(
        'cookie_version' => '',
        'cookie_name' => 'html2css_options',
        'cookie_duration' => 31536000,
        'cookie_version' => 20141230223230,
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
        'create_first_last_child' => array(
            'No',
            'Yes',
        ) ,
        'create_following_selector' => array(
            'No',
            'Yes',
        )
    );
    private $options = array(
        'css_format' => array(
            'name' => 'CSS Format',
            'type' => 'choice',
            'value' => 'compressed'
        ) ,
        'comment_first_block' => array(
            'name' => 'Comment first block',
            'type' => 'choice',
            'value' => 0
        ) ,
        'create_first_last_child' => array(
            'name' => 'Create first/last child if multiple items',
            'type' => 'choice',
            'value' => 0
        ) ,
        'create_following_selector' => array(
            'name' => 'Create *+* if multiple items',
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
        'ignored_attributes' => array(
            'type' => 'array',
            'value' => array(
                'data-ng-repeat'
            )
        ) ,
        'simplify_selectors_parts' => array(
            'type' => 'array_multi',
            'value' => array(
                array(
                    'before' => 'li a',
                    'after' => 'a'
                ) ,
                array(
                    'before' => 'ul li',
                    'after' => 'li'
                ) ,
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
        )
    );

    function __construct($cookie = true) {
        $this->cookie = is_bool($cookie) ? $cookie : true;
        $this->paths = array();
        $this->setCookieVersion();
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

        $this->parseNodes($doc);
    }

    function parseNodes($node) {

        /* Parse nodes */
        $_childPath = $node->childNodes;
        $_tmpPaths = array();
        $_path = '';

        foreach ($_childPath as $sNode) {

            /* Only if not text node */
            if ($sNode->nodeType != 1) {
                continue;
            }

            /* Dont touch some nodes */
            if (!in_array($sNode->tagName, $this->options['ignored_nodes']['value'])) {

                /* Get element path */
                $_path = $this->extractNodePath($sNode);
                if (!empty($_path)) {
                    $_tmpPaths[] = $_path;
                    if (!in_array($_path, $this->paths)) {
                        $this->paths[] = $_path;
                    }
                }
            }

            $this->parseNodes($sNode);
        }

        /* If multiple path, and latest terminated by a tagName */
        $_create_first_last_child = $this->getOption('create_first_last_child');
        $_create_following_selector = $this->getOption('create_following_selector');
        if (($_create_first_last_child || $_create_following_selector) && count($_tmpPaths) > 2 && !empty($_path) && preg_match('/\ ([a-z1-6]+)$/', $_path, $_matches)) {

            $_allEquals = true;
            $_value = $_path;

            /* If all child are similar */
            foreach ($_tmpPaths as $_tmpPath) {
                if ($_tmpPath != $_path) {
                    $_allEquals = false;
                }
            }

            /* Add more specified rules */
            if ($_allEquals) {
                $_newRules = array();
                if ($_create_first_last_child) {
                    $_newRules[] = $_path . ':first-child';
                    $_newRules[] = $_path . ':last-child';
                }
                if ($_create_following_selector) {
                    $_newRules[] = $_path . ' + ' . $_matches[1];
                }

                /* Insert them after the current path */

                $_pathPosition = array_search($_path, $this->paths) + 1;

                $this->paths = array_merge(array_slice($this->paths, 0, $_pathPosition, true) , $_newRules, array_slice($this->paths, $_pathPosition, count($this->paths) - $_pathPosition, true));
            }
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
        $_pathItems = $this->filter_clearBeforeIDs($_pathItems);
        $_pathItems = $this->filter_IgnoredNodes($_pathItems);
        $_pathItems = $this->filter_IgnoredSelectors($_pathItems);
        $_pathItems = $this->filter_ParentBEM($_pathItems);
        $_pathItems = $this->filter_ParentContainedClassname($_pathItems);

        $_path = implode(' ', $_pathItems);
        $_path = $this->filter_SimplifySelectorsParts($_path);

        return $_path;
    }

    function extractNodeIdentity($node) {

        /* Default : tagName */
        $_nodeIdentity = $node->tagName;

        /* Get element attributes */
        $_nodeAttributes = array();
        $_nodeDataAttributes = array();
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                if (in_array($attr->nodeName, $this->options['ignored_attributes']['value'])) {
                    continue;
                }
                $_nodeAttributes[$attr->nodeName] = $attr->nodeValue;
                if (substr($attr->nodeName, 0, 4) == 'data') {
                    $_nodeDataAttributes[$attr->nodeName] = $attr->nodeValue;
                }
            }
        }

        /* If one data-attribute exists, use it to qualify tagName */
        if (count($_nodeDataAttributes) == 1) {
            $name = key($_nodeDataAttributes);
            $value = trim(current($_nodeDataAttributes));
            if (!empty($value)) {
                $value = '="' . $value . '"';
            }
            $_nodeIdentity = $node->tagName . '[' . $name . $value . ']';
        }

        /* If role attribute exists, use it */
        if ($_nodeIdentity == $node->tagName && isset($_nodeAttributes['role'])) {
            $_nodeIdentity = $node->tagName . '[role="' . $_nodeAttributes['role'] . '"]';
        }

        /* Input : add type */
        if ($node->tagName == 'input' && isset($_nodeAttributes['type'])) {
            $_nodeIdentity = $node->tagName . '[type="' . $_nodeAttributes['type'] . '"]';
        }

        /* Label : add for */
        if ($node->tagName == 'label' && isset($_nodeAttributes['for'])) {
            $value = $_nodeAttributes['for'];
            $_nodeIdentity = $node->tagName . '[for="' . $value . '"]';
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

        /* Use ID only if node identity is equal to tagName */
        if ($_nodeIdentity == $node->tagName && isset($_nodeAttributes['id'])) {
            $_nodeIdentity = '#' . $_nodeAttributes['id'];
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
            $type = $_default_option['type'];
            switch ($type) {
                case 'array':
                case 'array_multi':

                    // option should be an array
                    if (!is_array($_option)) {
                        $_canImport = false;
                    } else {
                        foreach ($_option as $v) {

                            // Should only contains strings
                            if ($type == 'array' && !preg_match('/^[a-zA-Z0-9_\-\.]*$/', $v)) {
                                $_canImport = false;
                            }

                            // Should only contains arrays
                            if ($type == 'array_multi' && !is_array($v)) {
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

        // Save options in a cookie
        if ($this->cookie) {
            setcookie($this->conf['cookie_name'], json_encode($cookie_options) , time() + $this->conf['cookie_duration']);
        }
    }

    public function setCookieVersion() {
        if (!$this->cookie) {
            return false;
        }
        $option_name = $this->conf['cookie_name'] . '__version';

        // Unset cookie options if it corresponds to an invalid version
        if (isset($_COOKIE[$option_name]) && $_COOKIE[$option_name] != $this->conf['cookie_version']) {
            $_COOKIE[$this->conf['cookie_name']] = '';
            setcookie($this->conf['cookie_name'], '', time() + $this->conf['cookie_duration']);
        }
        setcookie($option_name, $this->conf['cookie_version'], time() + $this->conf['cookie_duration']);
    }

    /* ----------------------------------------------------------
      Filters
    ---------------------------------------------------------- */

    /* Clear before IDs */
    private function filter_clearBeforeIDs($pathItems) {
        $_tmpItems = array();
        foreach ($pathItems as $_item) {
            if ($_item[0] == '#') {
                $_tmpItems = array();
            }
            $_tmpItems[] = $_item;
        }
        return $_tmpItems;
    }

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

    /* Simplify selectors parts */
    public function filter_SimplifySelectorsParts($path) {
        foreach ($this->options['simplify_selectors_parts']['value'] as $var) {
            if (!isset($var['before'], $var['after'])) {
                continue;
            }
            $before = ' ' . $var['before'];
            $after = ' ' . $var['after'];
            $before_len = strlen($before);

            /* If node contains the "before" selector */
            if (strpos($path, $before) !== false) {

                /* Replace it by the "after" selector */
                $path = str_replace($before, $after, $path);
            }
        }

        return $path;
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
