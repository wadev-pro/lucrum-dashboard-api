<?php

namespace App\Http\Requests\Traits;


trait RequestValidationTrait
{

    /**
     * Automatically invoke validation rules if any
     * @return string|false
     */
    protected function getCallableValidationMethod(): string
    {

        $callableMethod = 'rules' . ucfirst(strtolower($this->method()));

        if(method_exists($this, $callableMethod)){
            return $callableMethod;
        }

        return 'genericEmptyValidation';
    }

    /**
     * Get empty validation array if nothing else is defined
     *
     * @return array
     */
    protected function genericEmptyValidation(): array
    {
        return $this->validationRules ?? [] ;
    }
}
