<?php
namespace mt\app\cmd\phar;

use mt\core\lib\JConfig;
use mt\core\yiihelper\YiiFileHelper;
use Phar;
use RuntimeException;

/**
 * 配置文件参考：
 *  config/local/phar.php
 * 可通过修改配置，打包其他项目
 * usage:
 *  php -d phar.readonly=0 ./mpt -r phar/pack
 */
class PackAction
{
    public function run()
    {
        $this->checkEnv();
        $conf = JConfig::getEnv('phar');
        //showLog($conf);return;
        $packDir = $conf['pack_dir'] ? : BASE_PATH;
        showLog('Phar pack for dir['.$packDir.'] start...');
        dirExistOrMake($conf['output_dir']);
        if (!is_dir($conf['output_dir'])) {
            throw new RuntimeException('Please set the phar file output directory.');
        }

        if (empty($conf['output_file'])) {
            throw new RuntimeException('Please set the phar filename.');
        }

        if (!in_array($conf['sign_algorithm'],[Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512,Phar::OPENSSL])) {
            throw new RuntimeException('The signature algorithm must be one of Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, or Phar::OPENSSL.');
        }

        $pharFile = rtrim($conf['output_dir'],DS) . DS . $conf['output_file'];
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new Phar($pharFile, 0, 'mpt');
        $phar->startBuffering();

        if ($conf['sign_algorithm'] === Phar::OPENSSL) {
            if (empty($conf['private_key']) || !file_exists($conf['private_key'])) {
                throw new RuntimeException("If the value of the signature algorithm is 'Phar::OPENSSL', you must set the private key file.");
            }
            $private = openssl_get_privatekey(file_get_contents($conf['private_key']));
            $pkey = '';
            openssl_pkey_export($private, $pkey);
            $phar->setSignatureAlgorithm($conf['sign_algorithm'], $pkey);
        } else {
            $phar->setSignatureAlgorithm($conf['sign_algorithm']);
        }

        //使用目录的打包形式 不符合预期；调整为遍历项目文件后再添加
//        $filterPattern = $conf['filter_pattern'] ?? null;
//        $phar->buildFromDirectory(BASE_PATH. '/test', $filterPattern);

        showLog('Files collect start...');
        $options = [];
        if (!empty($conf['exclude_dirs'])) {
            foreach ($conf['exclude_dirs'] as $dir) {
                $options['except'][] = $dir;
//                $options['except'][] = '/'.trim($dir,'/').'/';
            }
        }
        $fs = YiiFileHelper::findFiles($packDir, $options);

        showLog('Files collect complete, begin add file to Phar.');
        foreach ($fs as $f) {
            $phar->addFile($f, str_replace($packDir,'',$f));
        }

        foreach ($conf['exclude_files'] as $file) {
            if ($phar->offsetExists($file)) {
                $phar->delete($file);
            }
        }

        $phar->setDefaultStub('/mpt','/web/index.php');

        showLog('Write requests to the Phar archive, save changes to disk.');

        $phar->stopBuffering();
        unset($phar);

        return 0;
    }


    private function checkEnv(): void
    {
        if (!class_exists(Phar::class, false)) {
            throw new RuntimeException("The 'phar' extension is required for build phar package");
        }

        if (ini_get('phar.readonly')) {
            throw new RuntimeException(
                "The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0 ./mpt phar/pack'"
            );
        }
    }
}