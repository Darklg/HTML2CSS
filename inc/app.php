<?php
$html_posted = '';
$options = array();
if (!empty($_POST) && isset($_POST['html'])) {
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        $options = $_POST['options'];
    }
    $html2css = new html2css($options);
    $html_posted = $_POST['html'];
    $html2css->parse_html($html_posted);
    if (isset($_POST['api'])) {
        echo $html2css->generateCSS();
        die;
    }
}
