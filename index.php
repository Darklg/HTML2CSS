<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'inc/html2css.class.php';
include 'inc/app.php';
header("X-XSS-Protection: 0");
?>
<!DOCTYPE HTML>
<html lang="en-EN">
    <head>
        <meta charset="UTF-8" />
        <title>HTML 2 CSS - Transform a HTML Layout into CSS</title>
        <link rel="stylesheet" type="text/css" href="assets/css/main.css" />
    </head>
    <body>
        <div class="centered-container cc-main">
            <div class="main">
                <header>
                    <h1>HTML 2 CSS</h1>
                    <p>This tool will suggest CSS classes based on the provided HTML.</p>
                </header>
                <form class="main-form" action="" method="post">
                    <div class="cssc-grid fluid-grid">
                        <div class="col-50p">
                            <fieldset>
                                <legend>HTML to transform</legend>
                                <textarea required class="inputbase" name="html" rows="5" cols="100"><?php echo htmlentities($html_posted); ?></textarea><br />
                                <div id="display-options">
                                    <a href="#" onclick="document.getElementById('options').style.display='block';document.getElementById('display-options').style.display='none';return false;">Show options</a>
                                </div>
                                <div id="options" class="options" style="display: none;">
                                <?php echo $html2css->generateSelect('css_format'); ?>
                                <?php echo $html2css->generateSelect('comment_first_block'); ?>
                                </div><br />
                                <button class="cssc-button cssc-button--default cssc-button--medium" type="submit">Transform</button>
                            </fieldset>
                        </div>
                        <div class="col-50p">
                            <fieldset>
                                <legend>Generated CSS</legend>
                                <textarea class="inputbase" onclick="this.select()" rows="20" cols="100"><?php echo htmlentities($generated_css); ?></textarea>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <footer>
                    <strong>Fork <a target="_blank" href="https://github.com/Darklg/HTML2CSS">HTML2CSS on Github</a></strong>
                </footer>
            </div>
        </div>
    </body>
</html>