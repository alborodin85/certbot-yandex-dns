<?php

namespace It5\Adapters\HttpRequestExecutor;

use It5\Localization\Trans;
use JetBrains\PhpStorm\ArrayShape;

class RequestExecutor
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';

    public function __construct()
    {
        Trans::instance()->init(__DIR__ . '/localization/ru.php');
    }

    /** Делает запрос с GET-параметрами (GET, DELETE, PATCH). Возвращает сырую строку */
    public function makeUrlRequest(string $url, string $method, array $parameters, array $headers): string
    {
        $countParameters = count($parameters);
        [$parameters, $headers] = $this->prepareHeadersAndParameters($parameters, $headers);
        if ($countParameters) {
            $url = $url . '?' . $parameters;
        }
        $options = $this->prepareOptions($url, $method, $headers);
        $response = $this->execCurl($options);

        return $response;
    }

    /**
     * Делает запрос с POST-параметрами (пока только POST). Возвращает сырую строку
     * Параметры, которые не требуются - передаются пустыми массивами или пустыми строками
     */
    public function makeDataRequest(
        string $url,
        string $method,
        array $parameters,
        array $headers,
    ): string
    {
        [$parameters, $headers] = $this->prepareHeadersAndParameters($parameters, $headers);
        $options = $this->prepareOptions($url, $method, $headers);
        $options[CURLOPT_POSTFIELDS] = $parameters;
        $options[CURLOPT_POST]= 1;
        $response = $this->execCurl($options);

        return $response;
    }

    /**
     * Передача файла методом POST, остальных параметров - методом GET
     * При передаче файла обязательно указывать только абсолютный путь
     */
    public function sendFile(
        string $url,
        string $method,
        array $parameters,
        array $headers,
        string $fileParameterName,
        string $fileAbsolutePath,
        string $fileDisplayName,
    ): string {
        $countParameters = count($parameters);
        $fileData = $this->prepareFile($fileParameterName, $fileAbsolutePath, $fileDisplayName);
        [$parameters, $headers] = $this->prepareHeadersAndParameters($parameters, $headers);
        if ($countParameters) {
            $url = $url . '?' . $parameters;
        }
        $options = $this->prepareOptions($url, $method, $headers);
        $options[CURLOPT_POSTFIELDS] = $fileData;
        // ВАЖНО! CURLOPT_POST вообще не указывать! ИНАЧЕ НЕ УЙДЕТ!
        $options[CURLOPT_TIMEOUT] = 30;

        $response = $this->execCurl($options);

        return $response;
    }

    private function prepareFile(
        string $fileParameterName, string $fileAbsolutePath, string $fileDisplayName
    ): array {
        try {
            if (!filetype($fileAbsolutePath)) {
                throw new HttpRequestExecutorError(Trans::T('errors.file_not_exists'));
            }
        } catch (\Throwable) {
            throw new HttpRequestExecutorError(Trans::T('errors.file_access_errors', $fileAbsolutePath));
        }
        if (!$fileDisplayName) {
            $fileDisplayName = basename($fileAbsolutePath);
        }
        $mime = mime_content_type($fileAbsolutePath);
        $fileParameterName = $fileParameterName ?: 'file';

        $result[$fileParameterName] = new \CURLFile($fileAbsolutePath, $mime, $fileDisplayName);

        return $result;
    }

    private function prepareHeadersAndParameters(array $parameters, array $headers): array
    {
        $parameters = http_build_query($parameters);
        $headers = array_map(
            fn(string $value, string $key) => "{$key}: $value",
            $headers,
            array_flip($headers),
        );

        return [$parameters, $headers];
    }

    #[ArrayShape([
        \CURLOPT_URL => "string",
        \CURLOPT_RETURNTRANSFER => "bool",
        \CURLOPT_CUSTOMREQUEST => "string",
        \CURLOPT_HEADER => "bool",
        \CURLOPT_HTTPHEADER => "array"
    ])]
    private function prepareOptions(string $url, string $method, array $headers): array
    {
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        return $options;
    }

    private function execCurl(array $options): string
    {
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}