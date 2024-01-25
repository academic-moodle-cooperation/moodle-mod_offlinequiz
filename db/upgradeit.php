<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require(__DIR__ . '/upgradelib.php');

offlinequiz_fix_question_versions();

