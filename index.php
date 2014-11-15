<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'inc/html2css.class.php';
include 'inc/app.php';
header("X-XSS-Protection: 0");

?>
<!DOCTYPE HTML>
<html lang="fr-FR">
    <head>
        <meta charset="UTF-8" />
        <title>HTML 2 CSS</title>
    </head>
    <body>
        <h1>HTML 2 CSS</h1>
        <form action="" method="post">
            <h2>HTML à transformer</h2>
            <textarea name="html" rows="5" cols="100"><?php
echo htmlentities($html_posted); ?></textarea><br />
            <button class="cssc-button" type="submit">Transformer</button>
            <hr />
            <h2>CSS Généré</h2>
            <textarea onclick="this.select()" rows="20" cols="100"><?php
echo $html2css->generateCSS(); ?></textarea>
        </form>
    </body>
</html>