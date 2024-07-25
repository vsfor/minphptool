<?php
/**
 * 默认参数配置
 * @see \mt\core\lib\JConfig::getEnv()
 */
return [
    'pack_dir' => BASE_PATH,
    'output_dir' => BASE_PATH . DS . 'build',
    'output_file' => 'mpt.phar',
    'sign_algorithm' => Phar::SHA256,
    //set the signature algorithm for a phar and apply it. The signature algorithm must be one of Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, or Phar::OPENSSL.
    'private_key' => '',
    // The file path for certificate or OpenSSL private key file.
    /** @see \mt\app\cmd\phar\PackAction line 66 */
    'exclude_dirs' => [ //相对路径 pack_dir
        '/build/',
        '/config/',
        '/runtime/',
        '/test/',
        '.gitignore',
        '.idea',
        '.git',
    ], //脚本会前后自动补全/,eg /runtime/; 正常目录必须以/结尾，否则与目录名一致的文件也会被忽略
    'exclude_files' => [
        '.env',
//        'lib/.env',
        'README.md',
        'composer.json',
        'composer.lock',
    ], //不同层级的文件 需要单独配置

    //'filter_pattern'   => '#^(?!.*(config/plugin/webman/console/app.php|webman/console/src/Commands/(PharPackCommand.php|ReloadCommand.php)|LICENSE|composer.json|.github|.idea|doc|docs|.git|.setting|runtime|test|test_old|tests|Tests|vendor-bin|.md))(.*)$#',

    //与期望不一致，指定排除目录时，会导致所有目录下的 config ｜ build | runtime | .md 目录/文件 全部被忽略
    //'filter_pattern'   => '/^(?!.*(config|build|.idea|.git|runtime|.md))(.*)$/',
];