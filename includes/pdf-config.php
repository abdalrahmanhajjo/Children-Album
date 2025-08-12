<?php
// Override TCPDF configuration safely
// In your config.php or before TCPDF initialization:
if (!defined('PDF_FONT_NAME_MAIN')) {
    define('PDF_FONT_NAME_MAIN', 'helvetica');
}
if (!defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', __DIR__.'/../uploads/');
}