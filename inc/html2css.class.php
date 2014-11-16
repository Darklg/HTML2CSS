<?php
class html2css
{
    function __construct() {
        $this->paths = array();
        $this->ignored_nodes = array(
            'body',
            'br',
            'head',
            'html',
            'meta',
            'option',
            'title',
        );
        $this->bem_strings = array(
            '--',
            '__'
        );
    }

    function parse_html($content) {
        $content = trim($content);

        // Extract dom
        $doc = new DOMDocument();
        $doc->loadXML($content);

        // Parse nodes
        $_childPath = $doc->childNodes;
        foreach ($_childPath as $sNode) {
            $this->parseNode($sNode, 0);
        }
    }

    function parseNode($node, $hasParent) {

        // Kill if text node
        if ($node->nodeType != 1) {
            return 0;
        }

        // Dont touch some nodes
        if (!in_array($node->tagName, $this->ignored_nodes)) {

            // Get element path
            $_path = $this->extractNodePath($node);
            if (!in_array($_path, $this->paths)) {
                $this->paths[] = $_path;
            }
        }

        // Check child path
        $_childPath = $node->childNodes;
        foreach ($_childPath as $sNode) {
            $this->parseNode($sNode, 1);
        }
    }

    function extractNodePath($_parentNode) {

        $_rPathItems = array(
            $this->extractNodeIdentity($_parentNode)
        );

        // Construct path with parent nodes
        while (isset($_parentNode->parentNode, $_parentNode->parentNode->tagName)) {
            $_parentNode = $_parentNode->parentNode;
            if ($_parentNode->tagName == 'body') {
                break;
            }

            // Extract parent node identity
            $_rPathItems[] = $this->extractNodeIdentity($_parentNode);
        }
        $_pathItems = array_reverse($_rPathItems);

        /* Clean up path */

        // Reset path if BEM detected
        $_tmpItems = array();
        foreach ($_pathItems as $_item) {
            $isBem = false;
            foreach ($this->bem_strings as $string) {
                if (strpos($_item, $string) !== false) {
                    $isBem = true;
                }
            }
            if ($isBem) {
                $_tmpItems = array();
            }
            $_tmpItems[] = $_item;
        }
        $_pathItems = $_tmpItems;

        return implode(' ', $_pathItems);
    }

    function extractNodeIdentity($node) {

        // Default : tagName
        $_nodeIdentity = $node->tagName;

        // Last element classname if available
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

    function generateCSS() {
        $_generatedCSS = '';
        foreach ($this->paths as $_path) {
            $_generatedCSS.= $_path . " {\n\n}\n\n";
        }
        return trim($_generatedCSS);
    }
}
