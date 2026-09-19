<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    public function __construct(protected Application $app)
    {
    }

    protected function render(string $template, array $data = [], ?string $layout = 'site'): void
    {
        View::render($template, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    /** Remember submitted form values for old() and flash a message, then go back. */
    protected function back(string $path, ?string $error = null, array $input = []): never
    {
        if ($error !== null) {
            $this->app->session()->flash('error', $error);
        }
        if ($input) {
            unset($input['_token'], $input['password']);
            $this->app->session()->flash('old_input', $input);
        }
        Response::redirect($path);
    }
}
