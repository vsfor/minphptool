<?php
namespace mt\app\web\site;

use mt\core\db\MySQLQuery;
use mt\core\lib\JView;

class IndexAction
{
    public function run()
    {
        $list = MySQLQuery::find('user')
            ->groupBy(['mobile'])
            ->limit(3)
            ->all();
        echo JView::render('index', [
            'list' => $list,
        ]);
    }
}