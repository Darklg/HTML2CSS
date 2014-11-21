<?php
$html2css = new html2css();
$generated_css = '';
$html_posted = '';
$options = array();
if (!empty($_POST) && isset($_POST['html'])) {
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        $options = $_POST['options'];
    }
    $html2css->setOptions($options);
    $html2css->generateCSS();
    $html_posted = trim($_POST['html']);
    if (!empty($html_posted)) {
        $html2css->parse_html($html_posted);
        $generated_css = $html2css->generateCSS();
        if (isset($_POST['api'])) {
            exit($generated_css);
        }
    }
}
