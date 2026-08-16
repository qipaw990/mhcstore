<?php
/**
 * Validation Helpers
 */

function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function validate_required(array $data, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' wajib diisi.';
        }
    }
    return $errors;
}
