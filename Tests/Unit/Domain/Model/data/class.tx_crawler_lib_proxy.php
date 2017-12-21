<?php
/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 25.04.14
 * Time: 13:58
 */
class tx_crawler_lib_proxy extends \AOE\Crawler\Controller\CrawlerController
{
    public function getHttpResponseFromStream($filePointer)
    {
        return parent::getHttpResponseFromStream($filePointer);
    }
}
