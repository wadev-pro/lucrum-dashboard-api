<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponseTrait;
use App\Helpers\IsJSON;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        // return parent::render($request, $exception);
        switch (true) {
            case ($exception instanceof AuthenticationException): // Unauthenticatd
                return $this->unauthenticated($request, $exception);
                break;
            case ($exception instanceof AuthorizationException): // Unauthorized
                return $this->unauthorized($request, $exception);
                break;
            case ($exception instanceof NotFoundHttpException): // Not Found
                $this->setResponseData(['message' => 'Please check the URL']);
                return response()->json($this->toArrayWithMetadata(), 404);
                break;
            case ($exception instanceOf ValidationException): // Validation Excpetion
                return parent::render($request, $exception);
                break;
            default:
                break;
        }
        $statusCode = 500;
        $error = array(
            'errors' => $exception->data ?? null,
            'message' => !empty($exception->getMessage()) ? $exception->getMessage(): "Invalid Request",
        );
        return response()->json($error, $statusCode);
    }
}
