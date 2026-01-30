<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!CModule::IncludeModule("iblock")) {
    return;
}

$arComponentParameters = [
    "GROUPS" => [
        "SETTINGS" => [
            "NAME" => GetMessage("RL_NOTES_PARAM_GROUP_SETTINGS")
        ],
    ],
    "PARAMETERS" => [
        "API_PATH" => [
            "PARENT" => "SETTINGS",
            "NAME" => GetMessage("RL_NOTES_PARAM_API_PATH"),
            "TYPE" => "STRING",
            "DEFAULT" => "/api/notes",
        ],
        "API_AUTH_TOKEN" => [
            "PARENT" => "SETTINGS",
            "NAME" => GetMessage("RL_NOTES_PARAM_API_TOKEN"),
            "TYPE" => "STRING",
            "DEFAULT" => "1234567890abcdef",
        ],
        "SET_TITLE" => [],
        "CACHE_TIME" => [],
    ]
];
