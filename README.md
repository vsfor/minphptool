#### mini tool by php

PHP简易工具包

Usage: `composer create-project jpp/mintool mpt dev-main`

##### 运行模式

- 命令行脚本  `php mpt -r cmd/test/index -p "a=b&c=3"`
- php-fpm API `路由仅支持 小写字母、数字、中划线`
- phar 打包部署 `php -d phar.readonly=0 ./mpt -r phar/pack`

##### 基础功能集成

- 动态加载配置文件 基于 `J_ENV`
- 日志 基于php `error_log`
- MySQL数据库连接及查询操作封装
- Redis连接基于php redis扩展 `https://pecl.php.net/package/redis`
- Excel 文件读写 `csv xls xlsx`
- Phar 打包，确认不使用phar打包时，可简化core/functions.php文件中的appBasePath方法

##### nginx php-fpm 配置样例

- 普通模式(非phar打包部署)

```
server {
    listen 80;
    server_name mt.local.com;
    index index.php;
    root /data/www/mintool/web;

    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php/$1 last;
    }

    location ~ [^/]\.php(/|$) {
        #fastcgi_pass 127.0.0.1:9000;
        fastcgi_pass unix:/dev/shm/php-cgi.sock;
        fastcgi_index index.php;
        include fastcgi.conf;
    }

    location ~ ^(.*)\/\.(git|svn|ht|project)\/  {
        deny all;
    }
    location /.well-known {
        allow all;
    }
    access_log off;
}
```

- phar 打包后使用nginx部署，可在同级目录创建index.php文件后参考普通模式配置

```php
// index.php 样例代码
try {
    defined('J_DEBUG') or define('J_DEBUG', false);//是否调试模式
    defined('J_ENV') or define('J_ENV', 'prod'); //环境｜主机｜项目

    Phar::loadPhar(__DIR__ .'/mpt.phar','mpt');
    require_once 'phar://mpt/web/index.php';
} catch (\Throwable $e) {
    var_dump($e->getMessage());
}
```