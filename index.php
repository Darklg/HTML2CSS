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
        <script src="assets/js/jquery/jquery.min.js"></script>
        <script src="assets/js/events.js"></script>
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
                                <textarea id="html_to_transform" required class="inputbase" name="html" rows="5" cols="100"><?php echo htmlentities($html_posted); ?></textarea><br />
                            </fieldset>
                        </div>
                        <div class="col-50p">
                            <fieldset>
                                <legend>Generated CSS</legend>
                                <textarea id="generated_css" class="inputbase" rows="20" cols="100"><?php echo htmlentities($generated_css); ?></textarea>
                            </fieldset>
                        </div>
                    </div>
                    <div class="form-submit">
                        <div id="display-options" class="options">
                            <a href="#">Show options</a>
                        </div>
                        <div id="options" class="options" style="display: none;">
                            <?php echo $html2css->generateSelect('css_format'); ?>
                            <?php echo $html2css->generateSelect('comment_first_block'); ?>
                        </div>
                        <button class="cssc-button cssc-button--default cssc-button--medium cssc-button--clean" id="demo-code" type="button">Demo code</button>
                        <button class="cssc-button cssc-button--default cssc-button--medium" type="submit">Transform</button>
                    </div>
                </form>
                <footer>
                    <strong>Fork <a target="_blank" href="https://github.com/Darklg/HTML2CSS">HTML2CSS on Github</a></strong>
                </footer>
            </div>
        </div>
    </body>
</html>