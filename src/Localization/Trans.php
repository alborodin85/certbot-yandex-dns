<?php

namespace It5\Localization;

class Trans
{
    private static self $instance;
    public static function instance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function T(string $key, string ...$subs): string
    {
        return self::instance()->getString($key, ...$subs);
    }

    public static function phrases(): array
    {
        return self::instance()->phrases;
    }

// ---------------------------------------------

    private array $phrases;

    public function init(string $fileAbsolutePath)
    {
        $this->phrases = include $fileAbsolutePath;
    }

    public function getString(string $key, string ...$subs): string
    {
        if (is_null($this->phrases)) {
            throw new LocalizationError('It5\Localization\Trans not initiated!');
        }

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