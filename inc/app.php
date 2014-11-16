<?php
$html2css = new html2css();
$html_posted = '';
if (!empty($_POST) && isset($_POST['html'])) {
    $html_posted = $_POST['html'];
    $html2css->parse_html($html_posted);
    if (isset($_POST['api'])) {
        echo $html2css->generateCSS();
        die;
    }
}
