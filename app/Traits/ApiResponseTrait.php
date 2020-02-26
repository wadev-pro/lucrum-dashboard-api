<?php

namespace App\Traits;


trait ApiResponseTrait
{
    private $responseMetadata = [];
    private $responseData = [];

    /**
     * @return array
     */
    protected function getResponseMetadata(): array
    {
        return $this->responseMetadata;
    }

    /**
     * @param array $responseMetadata
     * @return ApiResponseTrait
     */
    protected function setResponseMetadata(array $responseMetadata)
    {
        $this->responseMetadata = $responseMetadata;
        return $this;
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @param array $responseData
     * @return ApiResponseTrait
     */
    public function setResponseData(array $responseData)
    {
        $this->responseData = $responseData;
        return $this;
    }


    /**
     * Add response metadata element
     *
     * @param $key
     * @param $value
     * @return ApiResponseTrait
     */
    protected function addResponseMetadata($key, $value)
    {
        $this->responseMetadata[$key] = $value;
        return $this;
    }

    /**
     * Returning API response compatible array
     *
     * @return array
     */
    public function toArrayWithMetadata(): array
    {
        return [
            'metadata' => $this->getResponseMetadata(),
            'data' => $this->getResponseData()
        ];
    }
}
