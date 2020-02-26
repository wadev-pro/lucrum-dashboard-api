<?php

namespace App\Http\Requests\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use \Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Trait HasApiResponseTrait
 * @package App\Http\Requests\Traits
 */
trait HasApiResponseTrait
{

    /**
     * Checks if the request is looking for a json response and returns appropriate
     * response.
     *
     * @param Validator $validator
     * @throws HttpResponseException | ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->wantsJson()) {
            $errors = (new ValidationException($validator))->errors();
            throw new HttpResponseException(response()->json(['errors' => $errors
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

        } else {
            throw (new ValidationException($validator))
                ->errorBag($this->errorBag)
                ->redirectTo($this->getRedirectUrl());
        }

    }
}
