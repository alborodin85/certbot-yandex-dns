<?php

namespace It5\CurlShell;

use It5\DebugLibs\DebugLib;
use JetBrains\PhpStorm\Pure;

class HttpRequestWrapper
{
    private static self $instance;

    public string $rawResponse = '';
    public string $rawHeaders = '';
    public array $arHeaders = [];
    public string $rawBody = '';
    public array $arBody = [];
    public string $answer = '';
    private RequestExecutor $requestExecutor;

    #[Pure]
    private function __construct()
    {
        $this->requestExecutor = new RequestExecutor();
    }

    public function replaceExecutor(RequestExecutor $executor)
    {
        $this->requestExecutor = $executor;
    }

    public static function instance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /** Возвращает и выводит на экран результат выполнения запроса в отладочном виде; статическая */
    public static function dump(string $url, string $method, array $parameters, array $headers): self
    {
        return self::instance()->dumpResponse($url, $method, $parameters, $headers);
    }

    /** Просто возвращает результат выполнения запроса в отладочном виде; статическая */
    public static function response(string $url, string $method, array $parameters, array $headers): self
    {
        return self::instance()->quietResponse($url, $method, $parameters, $headers);
    }

    /** Возвращает и выводит на экран результат выполнения запроса в отладочном виде */
    public function dumpResponse(string $url, string $method, array $parameters, array $headers): self
    {
        $result = $this->quietResponse($url, $method, $parameters, $headers);

        DebugLib::dump($result->rawHeaders);
        if ($result->rawBody) {
            DebugLib::dump($result->rawBody);
        }
        DebugLib::dump($result->arBody);

        return $result;
    }

    /** Просто возвращает результат выполнения запроса в отладочном виде */
    public function quietResponse(string $url, string $method, array $parameters, array $headers): self
    {
        if (in_array(
            $method,
            [RequestExecutor::METHOD_DELETE, RequestExecutor::METHOD_PATCH, RequestExecutor::METHOD_GET]
        )) {
            $strResult = self::instance()->requestExecutor->makeUrlRequest($url, $method, $parameters, $headers);
        } else {
            $strResult = self::instance()->requestExecutor->makeDataRequest($url, $method, $parameters, $headers);
        }
        $strResult = self::instance()->decodeUnicode($strResult);

        $this->rawResponse = $strResult;
        $result = self::instance()->parseResponse($strResult);

        return $result;
    }

    private function decodeUnicode(string $string): string
    {
        $output = 'utf-8';
        return preg_replace_callback('#\\\\u([a-fA-F0-9]{4})#', function ($m) use ($output) {
            return iconv('ucs-2be', $output, pack('H*', $m[1]));
        }, $string);
    }

    private function parseResponse(string $response): self
    {
        $startObjectJsonBody = strpos($response, '{');
        $startArrayJsonBody = strpos($response, '[');

        if ($startObjectJsonBody === false && $startArrayJsonBody === false) {
            $startJsonBody = false;
            $jsonBodyLength = 0;
        } elseif ($startObjectJsonBody === false && $startArrayJsonBody) {
            $startJsonBody = $startArrayJsonBody;
            $jsonBodyLength = strrpos($response, ']') - $startJsonBody + 1;
        } elseif ($startObjectJsonBody && $startArrayJsonBody === false) {
            $startJsonBody = $startObjectJsonBody;
            $jsonBodyLength = strrpos($response, '}') - $startJsonBody + 1;
        } elseif ($startArrayJsonBody < $startObjectJsonBody) {
            $startJsonBody = $startArrayJsonBody;
            $jsonBodyLength = strrpos($response, ']') - $startJsonBody + 1;
        } else {
            $startJsonBody = $startObjectJsonBody;
            $jsonBodyLength = strrpos($response, '}') - $startJsonBody + 1;
        }

        if ($startJsonBody && $jsonBodyLength > 0) {
            $this->rawHeaders = substr($response, 0, $startJsonBody - 1);
            $this->rawBody = substr($response, $startJsonBody, $jsonBodyLength);
            $this->arBody = $this->recursiveDecode($this->rawBody);
        } else {
            $endHeaders = mb_strpos($response, "\n\n");
            if ($endHeaders !== false) {
                $this->rawHeaders = trim(substr($response, 0, $endHeaders));
                $this->rawBody = trim(substr($response, $endHeaders, mb_strlen($response)));
                $this->arBody = [$this->rawBody];
            } else {
                $this->rawHeaders = $response;
                $this->rawBody = '';
                $this->arBody = [];
            }
        }

        $this->arHeaders = $this->parseHeaders($this->rawHeaders);

        return $this;
    }

    private function parseHeaders(string $rawHeaders): array
    {
        $arResult = [];
        $rawHeaders = str_replace(["\r\n", "\r"], "\n", $rawHeaders);
        $arHeadersStr = explode("\n", $rawHeaders);
        $this->answer = $arHeadersStr[0];
        foreach ($arHeadersStr as $strItem) {
            if (!str_contains($strItem, ': ')) {
                continue;
            }
            $headerPair = explode(': ', $strItem);
            $arResult[$headerPair[0]] = $headerPair[1];
        }

        return $arResult;
    }

    private function recursiveDecode(array|string $json): array|string
    {
        $array = $json;
        if (is_string($json)) {
            $array = json_decode($json, true) ?: $json;
        }
        if (!is_array($array)) {
            return $array;
        }
        foreach ($array as $key => $value) {
            $array[$key] = self::recursiveDecode($value);
        }

        return $array;
    }
}
