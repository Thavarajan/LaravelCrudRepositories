<?php

namespace Thavam\Repositories\Helpers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class FormatResponseHelper
{
    /**
     * Send the response as json to the client.
     *
     * @param mixed        $result any object that can be converted to json
     * @param ResponseCode $code   Any response code
     *
     * @return json string response
     */
    public function responseJSON($result, $code = Response::HTTP_OK)
    {
        return response()->json($result, $code);
    }

    /**
     * send an exception as json reponse with some default errorcode.
     *
     * @param \Exception $exception Genereal exception that can be sent to the user
     *
     * @return Json string response with some invalid http reponsecode
     */
    public function responseError(\Exception $exception)
    {
        return $this->getJsonResponseForException($exception);
    }

    /**
     * Creates a new JSON response based on exception type.
     *
     * @param Exception $e
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJsonResponseForException(\Exception $e)
    {
        $debug = config('app.debug');
        if ($debug) {
            throw $e;
        } elseif ($e instanceof ValidationException) {
            throw $e;
        } elseif ($e instanceof ModelNotFoundException) {
            $retval = $this->modelNotFound();
        } elseif ($e instanceof QueryException) {
            $retval = $this->handleQueryException($e);
        } else {
            $retval = $this->badRequest($e->getMessage());
        }

        return $retval;
    }

    /**
     * return the string message as an exception json reponse with some default errorcode.
     *
     * @param string $message    to return the Error string
     * @param int    $statusCode to return the error with statuscode
     */
    public function responseErrorMessage($message, $statusCode)
    {
        return $this->responseJSON(['error' => $message], $statusCode);
    }

    /**
     * Returns json response for generic bad request.
     *
     * @param string $message
     * @param int    $statusCode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function badRequest($message = '', $statusCode = 400)
    {
        if (!$message) {
            $message = trans('Bad request');
        }

        return $this->responseErrorMessage($message, $statusCode);
    }

    /**
     * Returns json response for Eloquent model not found exception.
     *
     * @param string $message
     * @param int    $statusCode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function modelNotFound($message = '', $statusCode = 404)
    {
        if (!$message) {
            $message = trans('Record not found');
        }

        return $this->responseErrorMessage($message, $statusCode);
    }

    /**
     * Returns json response for Eloquent model not found exception.
     *
     * @param string $message
     * @param int    $statusCode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleQueryException(QueryException $e, $message = 'Sql Error', $statusCode = 400)
    {
        if ($message != 'Sql Error') {
            return $this->responseErrorMessage($message, $statusCode);
        } else {
            return $this->responseErrorMessage($e->getMessage(), $statusCode);
        }
    }
}
