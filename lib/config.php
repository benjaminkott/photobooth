<?php

if (is_file(__DIR__ . '/../private/lib/polyfill.php')) {
    require_once __DIR__ . '/../private/lib/polyfill.php';
}

use Photobooth\Service\ConfigurationService;
use Photobooth\Environment;
use Photobooth\Photobooth;
use Photobooth\Helper;
use Photobooth\Utility\PathUtility;

$photobooth = new Photobooth();

$cmds = [
    'windows' => [
        'take_picture' => [
            'cmd' => 'digicamcontrol\CameraControlCmd.exe /capture /filename %s',
        ],
        'take_video' => [
            'cmd' => '',
        ],
        'take_custom' => [
            'cmd' => '',
        ],
        'print' => [
            'cmd' => 'rundll32 C:\WINDOWS\system32\shimgvw.dll,ImageView_PrintTo %s Printer_Name',
        ],
        'exiftool' => [
            'cmd' => '',
        ],
        'nodebin' => [
            'cmd' => '',
        ],
        'reboot' => [
            'cmd' => '',
        ],
        'shutdown' => [
            'cmd' => '',
        ],
    ],
    'linux' => [
        'take_picture' => [
            'cmd' => 'gphoto2 --capture-image-and-download --filename=%s',
        ],
        'take_video' => [
            'cmd' => 'python3 cameracontrol.py -v %s --vlen 3 --vframes 4',
        ],
        'take_custom' => [
            'cmd' =>
                'python3 cameracontrol.py --chromaImage=/var/www/html/resources/img/bg.jpg --chromaColor 00ff00 --chromaSensitivity 0.4 --chromaBlend 0.1 --capture-image-and-download --filename=%s',
        ],
        'print' => [
            'cmd' => 'lp -o landscape -o fit-to-page %s',
        ],
        'exiftool' => [
            'cmd' => 'exiftool -overwrite_original -TagsFromFile %s %s',
        ],
        'nodebin' => [
            'cmd' => '/usr/bin/node',
        ],
        'reboot' => [
            'cmd' => '/sbin/shutdown -r now',
        ],
        'shutdown' => [
            'cmd' => '/sbin/shutdown -h now',
        ],
    ],
];

$mailTemplates = [
    'de' => [
        'mail' => [
            'subject' => 'Hier ist dein Bild',
            'text' => 'Hey, dein Bild ist angehangen.',
        ],
    ],
    'en' => [
        'mail' => [
            'subject' => 'Here is your picture',
            'text' => 'Hey, your picture is attached.',
        ],
    ],
    'es' => [
        'mail' => [
            'subject' => 'Aquí está tu foto',
            'text' => 'Hola, tu foto está adjunta.',
        ],
    ],
    'fr' => [
        'mail' => [
            'subject' => 'Voici votre photo',
            'text' => 'Hé, ta photo est attachée.',
        ],
    ],
];

$environment = new Environment();
$configurationManager = ConfigurationService::getInstance();
$configurationManager
    ->setByPath('take_picture/cmd', $cmds[$environment->getOperatingSystem()]['take_picture']['cmd'])
    ->setByPath('take_video/cmd', $cmds[$environment->getOperatingSystem()]['take_video']['cmd'])
    ->setByPath('print/cmd', $cmds[$environment->getOperatingSystem()]['print']['cmd'])
    ->setByPath('exiftool', $cmds[$environment->getOperatingSystem()]['exiftool']['cmd'])
    ->setByPath('nodebin', $cmds[$environment->getOperatingSystem()]['nodebin']['cmd'])
    ->setByPath('reboot', $cmds[$environment->getOperatingSystem()]['reboot']['cmd'])
    ->setByPath('shutdown', $cmds[$environment->getOperatingSystem()]['shutdown']['cmd'])
    ->setByPath('remotebuzzer/logfile', 'remotebuzzer_server.log')
    ->setByPath('synctodrive/logfile', 'synctodrive_server.log')
    ->setByPath('dev/logfile', 'error.log')
;

if ($configurationManager->getByPath('mail/subject') === '') {
    $configurationManager->setByPath('mail/subject', $mailTemplates[$configurationManager->getByPath('ui/language')]['mail']['subject']);
}
if ($configurationManager->getByPath('mail/text') === '') {
    $configurationManager->setByPath('mail/text', $mailTemplates[$configurationManager->getByPath('ui/language')]['mail']['text']);
}

if ($configurationManager->getByPath('ui/folders_lang') === '') {
    $configurationManager->setByPath('ui/folders_lang', PathUtility::getPublicPath('resources/lang'));
}
$configurationManager->setByPath('ui/folders_lang', PathUtility::getPublicPath($configurationManager->getByPath('ui/folders_lang')));

foreach ($configurationManager->getByPath('folders') as $key => $folder) {
    if ($folder === 'data' || $folder === 'archives' || $folder === 'config' || $folder === 'private') {
        $path = PathUtility::getAbsolutePath($folder);
    } else {
        $path = PathUtility::getAbsolutePath($configurationManager->getByPath('folders/data') . DIRECTORY_SEPARATOR . $folder);
        $configurationManager->setByPath('foldersRoot/' . $key, $configurationManager->getByPath('folders/data') . DIRECTORY_SEPARATOR . $folder);
        $configurationManager->setByPath('foldersJS/' . $key, PathUtility::getPublicPath($path));
    }

    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            die("Abort. Could not create $folder.");
        }
    } elseif (!is_writable($path)) {
        die("Abort. The folder $folder is not writable.");
    }

    $configurationManager->setByPath('foldersAbs/' . $key, PathUtility::getAbsolutePath($path));
}

$configurationManager->setByPath('foldersJS/api', PathUtility::getPublicPath('api'));
$configurationManager->setByPath('foldersJS/chroma', PathUtility::getPublicPath('chroma'));

define('PRINT_DB', $configurationManager->getByPath('foldersAbs/data') . DIRECTORY_SEPARATOR . 'printed.csv');
define('PRINT_LOCKFILE', $configurationManager->getByPath('foldersAbs/data') . DIRECTORY_SEPARATOR . 'print.lock');
define('PRINT_COUNTER', $configurationManager->getByPath('foldersAbs/data') . DIRECTORY_SEPARATOR . 'print.count');
define('PHOTOBOOTH_LOG', $configurationManager->getByPath('foldersAbs/tmp') . DIRECTORY_SEPARATOR . $configurationManager->getByPath('dev/logfile'));

if ($configurationManager->getByPath('preview/mode') === 'gphoto') {
    $configurationManager->setByPath('preview/mode', 'device_cam');
}

// Preview need to be stopped before we can take an image
if ($configurationManager->getByPath('preview/killcmd') !== '' && $configurationManager->getByPath('preview/stop_time') < $configurationManager->getByPath('picture/cntdwn_offset')) {
    $configurationManager->setByPath('preview/stop_time', $configurationManager->getByPath('picture/cntdwn_offset') + 1);
}

$default_font = PathUtility::getPublicPath('resources/fonts/GreatVibes-Regular.ttf');
$default_frame = PathUtility::getPublicPath('resources/img/frames/frame.png');
$random_frame = PathUtility::getPublicPath('api/randomImg.php?dir=demoframes');
$default_template = realpath(PathUtility::getRootPath() . DIRECTORY_SEPARATOR . 'resources/template/index.php');

if ($configurationManager->getByPath('picture/frame') === '') {
    $configurationManager->setByPath('picture/frame', $random_frame);
}
if ($configurationManager->getByPath('textonpicture/font') === '') {
    $configurationManager->setByPath('textonpicture/font', $default_font);
}
if ($configurationManager->getByPath('collage/frame') === '') {
    $configurationManager->setByPath('collage/frame', $default_frame);
}

if ($configurationManager->getByPath('collage/placeholderpath') === '') {
    $configurationManager->setByPath('collage/placeholderpath', PathUtility::getPublicPath('resources/img/background/01.jpg'));
}
if ($configurationManager->getByPath('textoncollage/font') === '') {
    $configurationManager->setByPath('textoncollage/font', $default_font);
}
if ($configurationManager->getByPath('print/frame') === '') {
    $configurationManager->setByPath('print/frame', $default_frame);
}
if ($configurationManager->getByPath('textonprint/font') === '') {
    $configurationManager->setByPath('textonprint/font', $default_font);
}
if ($configurationManager->getByPath('collage/limit') === '') {
    $configurationManager->setByPath('collage/limit', 4);
}

$bg_url = PathUtility::getPublicPath('resources/img/background.png');
$logo_url = PathUtility::getPublicPath('resources/img/logo/logo-qrcode-text.png');

if ($configurationManager->getByPath('logo/path') === '') {
    $configurationManager->setByPath('logo/path', $logo_url);
}
if ($configurationManager->getByPath('background/defaults') === '') {
    $configurationManager->setByPath('background/defaults', 'url(' . $bg_url . ')');
}
if ($configurationManager->getByPath('background/admin') === '') {
    $configurationManager->setByPath('background/admin', 'url(' . $bg_url . ')');
}
if ($configurationManager->getByPath('background/chroma') === '') {
    $configurationManager->setByPath('background/chroma', 'url(' . $bg_url . ')');
}

if ($configurationManager->getByPath('preview/showFrame') !== '' && $configurationManager->getByPath('picture/frame') !== '') {
    $configurationManager->setByPath('preview/htmlframe', $configurationManager->getByPath('picture/frame'));
}
if ($configurationManager->getByPath('preview/showFrame') !== '' && $configurationManager->getByPath('collage/frame') !== '') {
    $configurationManager->setByPath('collage/htmlframe', $configurationManager->getByPath('collage/frame'));
}

if ($configurationManager->getByPath('webserver/ip') === '') {
    $configurationManager->setByPath('webserver/ip', $photobooth->getIp());
}
if ($configurationManager->getByPath('remotebuzzer/serverip') === '') {
    $configurationManager->setByPath('remotebuzzer/serverip', $photobooth->getIp());
}
if ($configurationManager->getByPath('qr/url') === '') {
    $configurationManager->setByPath('qr/url', PathUtility::getPublicPath('api/download.php?image='));
}

if ($configurationManager->getByPath('ftp/template_location') === '' || !Helper::testFile($configurationManager->getByPath('ftp/template_location'))) {
    $configurationManager->setByPath('ftp/template_location', $default_template);
}

if ($configurationManager->getByPath('ftp/urlTemplate') !== '') {
    try {
        $parameters = [
            '%website' => $config['ftp']['website'],
            '%baseFolder' => $config['ftp']['baseFolder'],
            '%folder' => $config['ftp']['folder'],
            '%title' => Helper::slugify($config['ftp']['title']),
            '%date' => date('Y/m/d'),
        ];
    } catch (\Exception $e) {
        $parameters = [
            '%website' => $config['ftp']['website'],
            '%baseFolder' => $config['ftp']['baseFolder'],
            '%folder' => $config['ftp']['folder'],
            '%title' => 'Example',
            '%date' => date('Y/m/d'),
        ];
    }
    $configurationManager->setByPath('ftp/processedTemplate', str_replace(array_keys($parameters), array_values($parameters), $configurationManager->getByPath('ftp/urlTemplate')));
}

$configurationManager->setByPath('cheese_img', $configurationManager->getByPath('ui/shutter_cheese_img'));
if ($configurationManager->getByPath('cheese_img') !== '') {
    $configurationManager->setByPath('cheese_img', PathUtility::getPublicPath($configurationManager->getByPath('ui/shutter_cheese_img')));
}

$configurationManager->setByPath('photobooth/version', $photobooth->getVersion());
$configurationManager->setByPath('photobooth/basePath', PathUtility::getPublicPath());

$configurationManager->writeConfiguration();
$config = $configurationManager->getConfiguration();
