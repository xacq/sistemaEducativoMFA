<?php
/**
 * Simple flash message helper.
 * Assumes the session is already started by the caller.
 */
function flash_push(string $type, string $message): void
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    if (!isset($_SESSION['flash'][$type]) || !is_array($_SESSION['flash'][$type])) {
        $_SESSION['flash'][$type] = [];
    }
    $_SESSION['flash'][$type][] = $message;
}

function flash_consume(): array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return ['success' => [], 'error' => [], 'warning' => []];
    }
    $messages = $_SESSION['flash'];
    unset($_SESSION['flash']);
    foreach (['success', 'error', 'warning'] as $type) {
        if (!isset($messages[$type]) || !is_array($messages[$type])) {
            $messages[$type] = [];
        }
    }
    return $messages;
}
