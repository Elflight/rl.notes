<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('PUBLIC_AJAX_MODE', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Elements\ElementNotesTable;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('iblock')) {
    http_response_code(500);
    echo Json::encode(['error' => 'iblock not loaded']);
    exit;
}

// Check iblock exists
$iblock = IblockTable::getList([
    'filter' => ['=CODE' => 'notes', '=ACTIVE' => 'Y'],
    'select' => ['ID']
])->fetch();

if (!$iblock) {
    http_response_code(500);
    echo Json::encode(['error' => 'Iblock "notes" not found or inactive']);
    exit;
}

// API token from module options or default
$API_TOKEN = \Bitrix\Main\Config\Option::get('rl.notes', 'api_token', '1234567890abcdef');

$request = Application::getInstance()->getContext()->getRequest();
$token = $request->getHeader('X-API-KEY') ?: '';
if ($token !== $API_TOKEN) {
    http_response_code(401);
    echo Json::encode(['error' => 'Unauthorized']);
    exit;
}

// Routing
$method = $request->getRequestMethod();
$path = trim(parse_url($request->getRequestUri(), PHP_URL_PATH), '/');
$segments = explode('/', $path);

// Expect /api/notes or /api/notes/{id}
if ($segments[0] !== 'api' || $segments[1] !== 'notes') {
    http_response_code(404);
    echo Json::encode(['error' => 'Not found']);
    exit;
}

$id = isset($segments[2]) ? (int)$segments[2] : null;

$input = [];
if (in_array($method, ['POST', 'PUT'], true)) {
    try {
        $input = Json::decode($request->getInput());
    } catch (\Exception $e) {
        http_response_code(400);
        echo Json::encode(['error' => 'Invalid JSON']);
        exit;
    }
}


// Controller
switch ($method) {
    case 'GET':
        if ($id) {
            getNote($id);
        } else {
            getList();
        }
        break;
    case 'POST':
        createNote($input);
        break;
    case 'PUT':
        if (!$id) {
            methodNotAllowed();
        }
        updateNote($id, $input);
        break;
    case 'DELETE':
        if (!$id) {
            methodNotAllowed();
        }
        deleteNote($id);
        break;
    default:
        methodNotAllowed();
}

// Methods
function getList()
{
    $items = [];
    $res = ElementNotesTable::getList([
        'select' => ['ID', 'NAME', 'DETAIL_TEXT'],
        'order' => ['ID' => 'DESC'],
        'limit' => 50
    ]);
    while ($row = $res->fetch()) {
        $items[] = [
            'id' => (int)$row['ID'],
            'title' => $row['NAME'],
            'text' => $row['DETAIL_TEXT']
        ];
    }
    echo Json::encode($items);
    exit;
}

function getNote($id)
{
    $row = ElementNotesTable::getByPrimary($id, ['select' => ['ID', 'NAME', 'DETAIL_TEXT']])->fetch();
    if (!$row) {
        http_response_code(404);
        echo Json::encode(['error' => 'Not found']);
        exit;
    }
    echo Json::encode([
        'id' => (int)$row['ID'],
        'title' => $row['NAME'],
        'text' => $row['DETAIL_TEXT']
    ]);
    exit;
}

function createNote($data)
{
    $errors = validateNoteData($data);
    if ($errors) {
        http_response_code(400);
        echo Json::encode(['errors' => $errors]);
        exit;
    }
    try {
        $res = ElementNotesTable::add([
            'NAME' => $data['title'],
            'DETAIL_TEXT' => $data['text'],
            'ACTIVE' => 'Y'
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo Json::encode(['error' => $e->getMessage()]);
        exit;
    }
    http_response_code(201);
    echo Json::encode(['id' => $res->getId()]);
    exit;
}

function updateNote($id, $data)
{
    $errors = validateNoteData($data, true);
    if ($errors) {
        http_response_code(400);
        echo Json::encode(['errors' => $errors]);
        exit;
    }

    $fields = [];
    if (isset($data['title'])) {
        $fields['NAME'] = $data['title'];
    }
    if (isset($data['text'])) {
        $fields['DETAIL_TEXT'] = $data['text'];
    }
    if (!$fields) {
        http_response_code(400);
        echo Json::encode(['error' => 'Nothing to update']);
        exit;
    }
    if (!ElementNotesTable::getByPrimary($id)->fetch()) {
        http_response_code(404);
        echo Json::encode(['error' => 'Not found']);
        exit;
    }
    try {
        ElementNotesTable::update($id, $fields);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo Json::encode(['error' => $e->getMessage()]);
        exit;
    }
    echo Json::encode(['status' => 'updated']);
    exit;
}

function deleteNote($id)
{
    if (!ElementNotesTable::getByPrimary($id)->fetch()) {
        http_response_code(404);
        echo Json::encode(['error' => 'Not found']);
        exit;
    }
    try {
        ElementNotesTable::delete($id);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo Json::encode(['error' => $e->getMessage()]);
        exit;
    }
    http_response_code(204);
    exit;
}

function methodNotAllowed()
{
    http_response_code(405);
    echo Json::encode(['error' => 'Method not allowed']);
    exit;
}

function validateNoteData(array $data, bool $isUpdate = false): array
{
    $errors = [];

    // Для create — оба поля обязательны
    if (!$isUpdate) {
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }
        if (empty($data['text'])) {
            $errors[] = 'Text is required';
        }
    }

    // Если поля есть — проверяем тип и длину
    if (isset($data['title'])) {
        if (!is_string($data['title'])) {
            $errors[] = 'Title must be a string';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors[] = 'Title cannot be longer than 255 characters';
        }
    }

    if (isset($data['text'])) {
        if (!is_string($data['text'])) {
            $errors[] = 'Text must be a string';
        } elseif (mb_strlen($data['text']) > 10000) {
            $errors[] = 'Text cannot be longer than 10000 characters';
        }
    }

    return $errors;
}