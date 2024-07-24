<?php
namespace mt\app\api\site;

use mt\core\db\MySQLQuery;
use mt\core\lib\JResponse;

class IndexAction
{
    public function run()
    {
        $list = MySQLQuery::find('user')->groupBy(['email'])->all();
        echo JResponse::json([
            'someKey' => 'some value',
            'list' => $list,
        ]);
    }
}