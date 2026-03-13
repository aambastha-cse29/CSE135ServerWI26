<?php
// auth_helpers.php
// Shared authentication helper functions.
// Included on any page that needs role or section permission checks.
// Assumes session is already started and auth_check.php has already run.

// --------------------
// ROLE CHECKS
// --------------------

// Returns the current user's role from session
function getRole(): string {
    return $_SESSION['role'] ?? '';
}

// Returns true if current user is a superadmin
function isSuperAdmin(): bool {
    return getRole() === 'superadmin';
}

// Returns true if current user is an analyst
function isAnalyst(): bool {
    return getRole() === 'analyst';
}

// Returns true if current user is a viewer
function isViewer(): bool {
    return getRole() === 'viewer';
}

// --------------------
// SECTION ACCESS
// --------------------

// Returns true if the current user can access the given section.
// Superadmin can access everything.
// Analyst can access only their assigned sections.
// Viewer cannot access any section (only saved reports).
function hasAccess(string $section): bool {
    if (isSuperAdmin()) {
        return true;
    }

    if (isAnalyst()) {
        $sections = $_SESSION['sections'] ?? [];
        return in_array($section, $sections, true);
    }

    return false;
}

// Returns true if the current user can write comments and export reports.
// Only superadmin and analyst can do this.
function canExport(): bool {
    return isSuperAdmin() || isAnalyst();
}

// Returns true if the current user can manage users.
// Only superadmin can do this.
function canManageUsers(): bool {
    return isSuperAdmin();
}

// --------------------
// CURRENT USER
// --------------------

// Returns current user info from session as an associative array
function getCurrentUser(): array {
    return [
        'id'       => $_SESSION['user_id']  ?? null,
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role']     ?? '',
        'sections' => $_SESSION['sections'] ?? [],
    ];
}