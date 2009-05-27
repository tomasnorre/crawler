<?php

if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA["tx_crawler_infopot"] = array (
    "ctrl" => $TCA["tx_crawler_infopot"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "type,queueentry_id"
    ),
    "feInterface" => $TCA["tx_crawler_infopot"]["feInterface"],
    "columns" => array (
        "type" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_infopot.type",
            "config" => Array (
                "type" => "input",
                "size" => "30",
            )
        ),
        "queueentry_id" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_infopot.queueentry_id",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "tx_crawler_infopot",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
    ),
    "types" => array (
        "0" => array("showitem" => "type;;;;1-1-1, queueentry_id")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);

?>