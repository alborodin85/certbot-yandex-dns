<?php

namespace It5\CurlShell;

class Ru
{
    private array $phrases = [
        'errors' => [
            'file_not_exists' => 'Переданный для отправки файл не существует!',
            'file_access_errors' => 'Проблемы с доступом к файлу %s!',
        ],
    ];

    private static self $instance;

    private static function singleton(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get(string $key, string ...$subs): string
    {
        return self::singleton()->instGet($key, ...$subs);
    }

    public function instGet(string $key, string ...$subs): string
    {
        $keysParsed = explode('.', $key);
        $subArray = $this->phrases;
        foreach ($keysParsed as $subKey) {
            $subArray = $subArray[$subKey];
        }

        $result = sprintf($subArray, ...$subs);
        $result = preg_replace('/\s+/m', ' ', $result);

        return $result;
    }
}