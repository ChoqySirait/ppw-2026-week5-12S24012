<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'soap/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = 500;
                $title = 'Internal Server Error';
                $detail = config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan sistem internal pada server.';
                $type = 'https://tools.ietf.org/html/rfc7231#section-6.6.1';
                $extensions = [];

                if ($e instanceof AuthenticationException) {
                    $status = 401;
                    $title = 'Unauthorized';
                    $detail = 'Autentikasi Bearer Token diperlukan untuk mengakses sumber daya ini atau token tidak valid.';
                    $type = 'https://tools.ietf.org/html/rfc7235#section-3.1';
                } elseif ($e instanceof ValidationException) {
                    $status = 422;
                    $title = 'Unprocessable Content';
                    $detail = 'Data yang dikirimkan tidak valid atau tidak memenuhi kriteria validasi.';
                    $type = 'https://tools.ietf.org/html/rfc4918#section-11.2';
                    $extensions['errors'] = $e->errors();
                } elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    $status = 404;
                    $title = 'Not Found';
                    $detail = $e->getMessage() ?: 'Resource atau endpoint yang diminta tidak ditemukan.';
                    $type = 'https://tools.ietf.org/html/rfc7231#section-6.5.4';
                } elseif ($e instanceof HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                    if ($status === 409) {
                        $title = 'Conflict';
                        $type = 'https://tools.ietf.org/html/rfc7231#section-6.5.8';
                    } elseif ($status === 403) {
                        $title = 'Forbidden';
                        $type = 'https://tools.ietf.org/html/rfc7231#section-6.5.3';
                    } elseif ($status === 404) {
                        $title = 'Not Found';
                        $type = 'https://tools.ietf.org/html/rfc7231#section-6.5.4';
                    } elseif ($status === 401) {
                        $title = 'Unauthorized';
                        $type = 'https://tools.ietf.org/html/rfc7235#section-3.1';
                    } elseif ($status === 422) {
                        $title = 'Unprocessable Content';
                        $type = 'https://tools.ietf.org/html/rfc4918#section-11.2';
                    } else {
                        $title = Response::$statusTexts[$status] ?? 'HTTP Error';
                        $type = 'https://tools.ietf.org/html/rfc7231#section-6';
                    }
                    $detail = $e->getMessage() ?: $title;
                }

                $payload = array_merge([
                    'type' => $type,
                    'title' => $title,
                    'status' => $status,
                    'detail' => $detail,
                    'instance' => $request->getRequestUri(),
                ], $extensions);

                return response()->json($payload, $status, [
                    'Content-Type' => 'application/problem+json',
                ]);
            }
        });
    })->create();
