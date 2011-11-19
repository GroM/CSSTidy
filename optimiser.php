<?php
if (PHP_VERSION < '5.3') {
    die("PHP version 5.3 and newer is required to run CSSTidy");
}

require_once __DIR__ . '/lib/CSSTidy.php';

$processed = false;

if (isset($_POST['input'])) {
    $processed = true;
    $inputCss = $_POST['input'];

    $cssTidy = new \CSSTidy\CSSTidy;
    $cssTidy->configuration->loadPredefinedTemplate($_POST['template']);
    $cssTidy->configuration->setCssLevel(\CSSTidy\Configuration::CSS3_0);
    $cssTidy->configuration->setMergeSelectors(\CSSTidy\Configuration::MERGE_SELECTORS);
    $cssTidy->configuration->setOptimiseShorthands(\CSSTidy\Configuration::BACKGROUND);
    $cssTidy->configuration->setDiscardInvalidSelectors();
    //$cssTidy->configuration->setSortProperties();
    //$cssTidy->configuration->setLowerCaseSelectors();
    //$cssTidy->configuration->setConvertUnit();
    //$cssTidy->configuration->setPreserveCss();

    try {
        $output = $cssTidy->parse($inputCss);
        $inputError = false;
    } catch (\Exception $e) {
        $inputError = true;
    }
}
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>CSSTidy - the best CSS minifier</title>
        <link rel="stylesheet" href="bootstrap.min.css">
        <script type="text/javascript" src="http://bowser.effectgames.com/~jhuckaby/zeroclipboard/ZeroClipboard.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js"></script>
        <script type="text/javascript">
        ZeroClipboard.setMoviePath('http://bowser.effectgames.com/~jhuckaby/zeroclipboard/ZeroClipboard.swf');
        $(function() {
			clip = new ZeroClipboard.Client();
			clip.addEventListener('mouseDown', function(client) {
                clip.setText($('#output').text());
            });
			clip.glue('copyToClipboard');
		});
        </script>
        <style type="text/css">
            form textarea {font-family: "Monaco", Courier New, monospace; font-size: 90%; width: 100%}
            form .submits {margin-top: 20px}
            form input[type=file] {background-color: #f5f5f5 !important; margin-top: 5px}
            pre {max-height: 200px; overflow-y: auto;}
            pre .selector {color: #195F91}
            pre .property {color: #CB4B16}
            pre .value {color: #268BD2}
            .size {text-align: center}
            .size .value {font-family: Georgia, 'New York CE', utopia, serif;font-size: 170%}
            .size .description {color: gray}
            .download {margin: 10px 0}
            dt {margin-top: 3px}
        </style>
    </head>
    <body>
        <h1>CSSTidy - the best CSS minifier</h1>

        <form method="post" class="row" enctype="multipart/form-data">

            <fieldset class="well span14 offset3">
                Paste our CSS<br>
                <textarea rows="20" cols="20" name="input"><?php if ($processed) echo htmlentities($inputCss, ENT_QUOTES, 'utf-8') ?></textarea><br>
                or <input type="file"><br>

                <div class="row submits">
                    <input type="submit" value="Minify!" class="btn large primary span5">

                    <div class="span3">
                        <label for="template">Compression:</label>
                        <div class="input">
                        <select name="template" id="template" class="span6">
                            <option value="<?php echo \CSSTidy\Configuration::HIGHEST_COMPRESSION ?>" selected>Highest (no readability, smallest size)</option>
                            <option value="<?php echo \CSSTidy\Configuration::HIGH_COMPRESSION ?>">High (moderate readability, smaller size)</option>
                            <option value="<?php echo \CSSTidy\Configuration::STANDARD_COMPRESSION ?>">Standard (balance between readability and size)</option>
                            <option value="<?php echo \CSSTidy\Configuration::LOW_COMPRESSION ?>">Low (higher readability)</option>
                        </select>
                        </div>
                    </div>
                </div>

            </fieldset>

        </form>

        <?php if ($processed): ?>
        <div class="row">
            <div class="span14 offset3">
                <h2>Result</h2>

                <?php if ($inputError): ?>
                <div class="alert-message error">
                    Cannot parse inserted CSS file
                </div>
                <?php else: ?>

                <div class="row sizes">

                    <div class="size span3 offset3">
                        <div class="value"><?php echo $output->size(\CSSTidy\Output::INPUT) ?>&nbsp;kB</div>
                        <span class="description">Input size</span>
                    </div>

                    <div class="size span3">
                        <div class="value"><?php echo $output->size(\CSSTidy\Output::OUTPUT) ?>&nbsp;kB</div>
                        <span class="description">Minified</span>
                    </div>

                    <div class="size span3">
                        <div class="value"><?php echo $output->getRatio() ?>&nbsp;%</div>
                        <span class="description">Compress ratio</span>
                    </div>
                </div>

                <a href="#" id="copyToClipboard" class="btn default">Copy to clipboard</a> <a href="#" class="btn success download">Download</a>

                <pre class="prettyprint" id="output"><?php echo $output->formatted() ?></pre>

                <?php endif; ?>
            </div>
        </div>

        <?php if (!$inputError): ?>
        <div class="row">
            <h3 class="span14 offset3">Console</h3>
            <dl class="span14 offset3">
            <?php foreach ($cssTidy->logger->getMessages() as $line => $messages): ?>
                <dt><?php echo $line ?></dt>
                <?php foreach ($messages as $message): ?>
                    <dd>
                    <?php switch ($message[\CSSTidy\Logger::TYPE]) {
                        case \CSSTidy\Logger::INFORMATION:
                            echo '<span class="label success">Optimization</span> ';
                            break;
                        case \CSSTidy\Logger::WARNING:
                            echo '<span class="label warning">Warning</span> ';
                            break;
                        case \CSSTidy\Logger::ERROR:
                            echo '<span class="label important">Important</span> ';
                    }
                    echo $message[\CSSTidy\Logger::MESSAGE] ?>
                    </dd>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </dl>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </body>
</html>