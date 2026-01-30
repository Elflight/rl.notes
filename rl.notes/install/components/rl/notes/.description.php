<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    "NAME" => GetMessage("RL_NOTES_COMP_NAME"),
    "DESCRIPTION" => GetMessage("RL_NOTES_COMP_DESC"),
    "ICON" => "/images/system.empty.png",
    "VERSION" => "1.0.0",
    "PATH" => [
        "ID" => "bitrixonrails",
        "NAME" => GetMessage("RL_NOTES_COMP_GROUP_NAME"),
        "CHILD" => [
            "ID" => "system",
            "NAME" => GetMessage("RL_NOTES_COMP_GROUP_SYSTEM"),
        ],
    ],
];
