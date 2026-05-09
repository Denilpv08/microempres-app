<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('ui_flash_set')) {
    function ui_flash_set(string $type, string $message): void
    {
        $_SESSION['ui_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('ui_flash_get')) {
    function ui_flash_get(): ?array
    {
        if (!isset($_SESSION['ui_flash']) || !is_array($_SESSION['ui_flash'])) {
            return null;
        }

        $flash = $_SESSION['ui_flash'];
        unset($_SESSION['ui_flash']);

        $type = $flash['type'] ?? 'info';
        if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) {
            $type = 'info';
        }

        $message = trim((string) ($flash['message'] ?? ''));
        if ($message === '') {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('ui_icon')) {
    function ui_icon(string $name): string
    {
        $icons = [
            'app' => '<svg viewBox="0 0 64 64" role="img" aria-hidden="true" focusable="false"><rect x="8" y="8" width="48" height="48" rx="12" fill="currentColor" opacity="0.18"></rect><path d="M20 23h24M20 32h24M20 41h14" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path></svg>',
            'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1v-8.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16 19v-1.25c0-1.52-1.12-2.75-2.5-2.75h-3c-1.38 0-2.5 1.23-2.5 2.75V19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="none" stroke="currentColor" stroke-width="1.8"></path><path d="M19 19v-1c0-1.1-.8-2-1.8-2.2M15.7 6.2a2.8 2.8 0 0 1 0 5.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'clients' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.5 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="none" stroke="currentColor" stroke-width="1.8"></path><path d="M4 19v-1.1C4 15.7 5.9 14 8.2 14h.5c1 0 1.8.3 2.6.8M20 19v-1.1c0-2.2-1.9-3.9-4.2-3.9h-.5c-1 0-1.8.3-2.6.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'inventory' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 8.5 12 4l8 4.5-8 4.5L4 8.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="M4 8.5V16l8 4 8-4V8.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="m12 13 8-4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'sales' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5h14l-1.3 8.2a2 2 0 0 1-2 1.7H8.2a2 2 0 0 1-2-1.7L4.5 3.5H3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" fill="none" stroke="currentColor" stroke-width="1.8"></path></svg>',
            'reports' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 20h12M8 20V10M12 20V4M16 20v-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 17 15 12l-5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M15 12H4M13 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            'add' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'search' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m15.5 15.5 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><circle cx="10.5" cy="10.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"></circle></svg>',
            'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m4 16 10.5-10.5 4 4L8 20H4v-4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="m13.5 5.5 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'delete' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 7h14M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M7 7l1 13h8l1-13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path></svg>',
            'save' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 4h11l3 3v13H5V4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path><path d="M8 4v6h8V4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'close' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
            'loading' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8" opacity="0.2"></circle><path d="M20 12a8 8 0 0 0-8-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>',
        ];

        return $icons[$name] ?? '';
    }
}
