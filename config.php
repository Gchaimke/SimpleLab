<?php
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Direct access not allowed');
    exit();
};
define("SITE_ROOT", "/");
define("DOC_ROOT", $_SERVER['DOCUMENT_ROOT'] . SITE_ROOT);
define("PASS", "SimpleLab10X");
define("FOLDER_KEY","a2qwwujhgf111bbnMMmmlfsyvbx33bvvvvfwkklg"); //Change this one time for security
