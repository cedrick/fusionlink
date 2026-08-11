<?php

class Controller
{
    protected function requireLogin()
    {
        if (!isset($_SESSION['user'])) {
            redirect('/login');
        }
    }

    protected function requireAdmin()
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'ADMIN') {
            echo "<h1>Access Denied</h1>";
            echo "<p>You do not have permission to access this page.</p>";
            echo "<a href='" . htmlspecialchars(url('/dashboard'), ENT_QUOTES, 'UTF-8') . "'>Back to Dashboard</a>";
            exit;
        }
    }

    protected function requireStaff()
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['ADMIN', 'STAFF'])) {
            echo "<h1>Access Denied</h1>";
            echo "<p>This page is restricted to staff.</p>";
            echo "<a href='" . htmlspecialchars(url('/dashboard'), ENT_QUOTES, 'UTF-8') . "'>Back to Dashboard</a>";
            exit;
        }
    }
}
