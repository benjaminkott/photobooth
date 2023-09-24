<?php

use Photobooth\Service\ConfigurationService;
use Photobooth\Service\LanguageService;

session_start();

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Shared instances
//
// We are assigning shared instances to $GLOBALS
// to avoid needing to construct them multiple times
// through the runtime and provide an easy way
// to use them as Singleton.
//
// Instances assigned to $GLOBALS should implement
// a getInstance method to recieve the shared state
// again.
//
// public static function getInstance(): self
// {
//     if (!isset($GLOBALS[self::class])) {
//         throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
//     }
//
//     return $GLOBALS[self::class];
// }
//
// Example:
// $languageService = LanguageService::getInstance();
// $languageService->translate('abort');
//
$GLOBALS[ConfigurationService::class] = new ConfigurationService();
$GLOBALS[LanguageService::class] = new LanguageService();

// Config
require_once dirname(__DIR__) . '/lib/config.php';

$config = $configurationManager->getConfiguration();

$cm = ConfigurationService::getInstance();
if ($cm->getByPath('dev/loglevel') > 0) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

define('DB_FILE', $cm->getByPath('foldersAbs/data') . DIRECTORY_SEPARATOR . $cm->getByPath('database/file') . '.txt');
define('MAIL_FILE', $cm->getByPath('foldersAbs/data') . DIRECTORY_SEPARATOR . $config['mail']['file'] . '.txt');
define('IMG_DIR', $cm->getByPath('foldersAbs/images'));

// Collage Config
define('COLLAGE_LAYOUT', $cm->getByPath('collage/layout'));
define('COLLAGE_RESOLUTION', (int) substr($cm->getByPath('collage/resolution'), 0, -3));
define('COLLAGE_BACKGROUND_COLOR', $cm->getByPath('collage/background_color'));
define('COLLAGE_FRAME', str_starts_with($cm->getByPath('collage/frame'), 'http') ? $cm->getByPath('collage/frame') : $_SERVER['DOCUMENT_ROOT'] . $cm->getByPath('collage/frame'));
define(
    'COLLAGE_BACKGROUND',
    (empty($cm->getByPath('collage/background'))
            ? ''
            : str_starts_with($cm->getByPath('collage/background'), 'http'))
        ? $cm->getByPath('collage/background')
        : $_SERVER['DOCUMENT_ROOT'] . $cm->getByPath('collage/background')
);
define('COLLAGE_TAKE_FRAME', $cm->getByPath('collage/take_frame'));
define('COLLAGE_PLACEHOLDER', $cm->getByPath('collage/placeholder'));
// If a placeholder is set, decrease the value by 1 in order to reflect array counting at 0
define('COLLAGE_PLACEHOLDER_POSITION', (int) $cm->getByPath('collage/placeholderposition') - 1);
define(
    'COLLAGE_PLACEHOLDER_PATH',
    str_starts_with($cm->getByPath('collage/placeholderpath'), 'http') ? $cm->getByPath('collage/placeholderpath') : $_SERVER['DOCUMENT_ROOT'] . $cm->getByPath('collage/placeholderpath')
);
define('COLLAGE_DASHEDLINE_COLOR', $cm->getByPath('collage/dashedline_color'));
// If a placholder image should be used, we need to increase the limit here in order to count the images correct
define('COLLAGE_LIMIT', $cm->getByPath('textoncollage/placeholder') ? $cm->getByPath('textoncollage/limit') + 1 : $cm->getByPath('textoncollage/limit'));
define('PICTURE_FLIP', $cm->getByPath('textoncollage/flip'));
define('PICTURE_ROTATION', $cm->getByPath('textoncollage/rotation'));
define('PICTURE_POLAROID_EFFECT', $cm->getByPath('textoncollage/polaroid_effect') === true ? 'enabled' : 'disabled');
define('PICTURE_POLAROID_ROTATION', $cm->getByPath('textoncollage/polaroid_rotation'));
define('TEXTONCOLLAGE_ENABLED', $cm->getByPath('textoncollage/enabled') === true ? 'enabled' : 'disabled');
define('TEXTONCOLLAGE_LINE1', $cm->getByPath('textoncollage/line1'));
define('TEXTONCOLLAGE_LINE2', $cm->getByPath('textoncollage/line2'));
define('TEXTONCOLLAGE_LINE3', $cm->getByPath('textoncollage/line3'));
define('TEXTONCOLLAGE_LOCATIONX', $cm->getByPath('textoncollage/locationx'));
define('TEXTONCOLLAGE_LOCATIONY', $cm->getByPath('textoncollage/locationy'));
define('TEXTONCOLLAGE_ROTATION', $cm->getByPath('textoncollage/rotation'));
define('TEXTONCOLLAGE_FONT', $cm->getByPath('textoncollage/font'));
define('TEXTONCOLLAGE_FONT_COLOR', $cm->getByPath('textoncollage/font_color'));
define('TEXTONCOLLAGE_FONT_SIZE', $cm->getByPath('textoncollage/font_size'));
define('TEXTONCOLLAGE_LINESPACE', $cm->getByPath('textoncollage/linespace'));

// Filter
define('FILTER_PLAIN', 'plain');
define('FILTER_ANTIQUE', 'antique');
define('FILTER_AQUA', 'aqua');
define('FILTER_BLUE', 'blue');
define('FILTER_BLUR', 'blur');
define('FILTER_COLOR', 'color');
define('FILTER_COOL', 'cool');
define('FILTER_EDGE', 'edge');
define('FILTER_EMBOSS', 'emboss');
define('FILTER_EVERGLOW', 'everglow');
define('FILTER_GRAYSCALE', 'grayscale');
define('FILTER_GREEN', 'green');
define('FILTER_MEAN', 'mean');
define('FILTER_NEGATE', 'negate');
define('FILTER_PINK', 'pink');
define('FILTER_PIXELATE', 'pixelate');
define('FILTER_RED', 'red');
define('FILTER_RETRO', 'retro');
define('FILTER_SELECTIVE_BLUR', 'selective-blur');
define('FILTER_SEPIA_LIGHT', 'sepia-light');
define('FILTER_SEPIA_DARK', 'sepia-dark');
define('FILTER_SMOOTH', 'smooth');
define('FILTER_SUMMER', 'summer');
define('FILTER_VINTAGE', 'vintage');
define('FILTER_WASHED', 'washed');
define('FILTER_YELLOW', 'yellow');
define('AVAILABLE_FILTERS', [
    FILTER_PLAIN => 'None',
    FILTER_ANTIQUE => 'Antique',
    FILTER_AQUA => 'Aqua',
    FILTER_BLUE => 'Blue',
    FILTER_BLUR => 'Blur',
    FILTER_COLOR => 'Color',
    FILTER_COOL => 'Cool',
    FILTER_EDGE => 'Edge',
    FILTER_EMBOSS => 'Emboss',
    FILTER_EVERGLOW => 'Everglow',
    FILTER_GRAYSCALE => 'Grayscale',
    FILTER_GREEN => 'Green',
    FILTER_MEAN => 'Mean',
    FILTER_NEGATE => 'Negate',
    FILTER_PINK => 'Pink',
    FILTER_PIXELATE => 'Pixelate',
    FILTER_RED => 'Red',
    FILTER_RETRO => 'Retro',
    FILTER_SELECTIVE_BLUR => 'Selective blur',
    FILTER_SEPIA_LIGHT => 'Sepia-light',
    FILTER_SEPIA_DARK => 'Sepia-dark',
    FILTER_SMOOTH => 'Smooth',
    FILTER_SUMMER => 'Summer',
    FILTER_VINTAGE => 'Vintage',
    FILTER_WASHED => 'Washed',
    FILTER_YELLOW => 'Yellow',
]);
