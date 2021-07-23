<?php

namespace It5\Localization;

use It5\DebugLibs\DebugLib;

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

// ---------------------------------------------

    private array $phrases = [];
    private array $existingLangFiles = [];

    public function addPhrases(string $fileAbsolutePath)
    {
        if (!in_array($fileAbsolutePath, $this->existingLangFiles)) {
            $currentPhrases = include $fileAbsolutePath;
            $this->phrases = array_merge_recursive($this->phrases, $currentPhrases);
            $this->existingLangFiles[] = $fileAbsolutePath;
        }
    }

    public function clearPhrases()
    {
        $this->existingLangFiles = [];
        $this->phrases = [];
    }

    public function getString(string $key, string ...$subs): string
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