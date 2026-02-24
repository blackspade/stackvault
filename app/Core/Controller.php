<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // ─── Response helpers ─────────────────────────────────────────────────────

    /**
     * Render a view inside a layout.
     *
     * @param string $template  Path relative to resources/views/ (no .php)
     * @param array  $data      Variables to pass to the view
     * @param string $layout    Layout name relative to resources/layouts/ (no .php)
     */
    protected function view(string $template, array $data = [], string $layout = 'main'): void
    {
        View::render($template, $data, $layout);
    }

    protected function redirect(string $path, int $code = 302): never
    {
        Response::redirect($path, $code);
    }

    protected function json(mixed $data, int $code = 200): never
    {
        Response::json($data, $code);
    }

    protected function abort(int $code, string $message = ''): never
    {
        Response::abort($code, $message);
    }

    protected function notFound(): never
    {
        Response::notFound();
    }

    // ─── CSRF helpers ─────────────────────────────────────────────────────────

    /** Abort 403 if CSRF token is missing or invalid. */
    protected function validateCsrf(): void
    {
        $token = $this->request->post('_token', '');
        if (!$token || !hash_equals(csrf_token(), $token)) {
            $this->abort(403, 'Invalid security token.');
        }
    }

    // ─── Flash messages ───────────────────────────────────────────────────────

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    protected function flashSuccess(string $message): void
    {
        $this->flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        $this->flash('error', $message);
    }
}
