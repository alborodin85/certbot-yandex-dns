<?php

namespace It5\ParametersParser;

use It5\Localization\Trans;

class CliParametersRegistry
{
    const UNNAMED_UNLIMIT = -1;

    private array $parameters;

    private static self | null $instance;

    private function __construct(
        private array $cliArgv,
        private array $allowedParams,
        private $allowedUnnamedCount
    ) {
        Trans::instance()->addPhrases(__DIR__ . '/localization/ru.php');
        $this->parameters = $this->parseCliArgv();
        // Первый параметр в $argv - всегда путь к скрипту и он будет всегда
        $this->allowedUnnamedCount++;
    }

    public function getInstanceProperty(string $propertyName): mixed
    {
        return $this->{$propertyName};
    }

    public static function init(
        array $cliArgv = [],
        array $allowedParams = [],
        int $allowedUnnamedCount = self::UNNAMED_UNLIMIT
    ): self {
        self::$instance = new self($cliArgv, $allowedParams, $allowedUnnamedCount);

        return self::$instance;
    }

    public static function reset()
    {
        self::$instance = null;
    }

    public static function singleton(): self
    {
        if (empty(self::$instance)) {
            throw new CliParametersError(Trans::T('errors.cli_registry_not_inited'));
        }

        return self::$instance;
    }

    public static function get(string $parameterName): string
    {
        return self::singleton()->instanceGet($parameterName);
    }

    public function instanceGet(string $parameterName): string
    {
        if ($this->parameters[$parameterName] ?? false) {
            return (string)$this->parameters[$parameterName];
        } else {
            return '';
        }
    }

    private function parseCliArgv(): array {
        $result = [];
        $unnamedParamNum = 1;
        for($i=0; $i<count($this->cliArgv); $i++) {
            if ($i == 0) {
                $result['script_path'] = $this->cliArgv[$i];
                continue;
            }
            if (str_starts_with($this->cliArgv[$i], '--')) {
                $paramsPair = explode('=' ,$this->cliArgv[$i]);
                $paramName = str_replace('--', '', trim($paramsPair[0]));
                if (count($this->allowedParams)) {
                    if (!in_array($paramName, $this->allowedParams)) {
                        throw new CliParametersError(Trans::T('errors.cli_redundant_param', $paramName));
                    }
                }
                if (isset($paramsPair[1])) {
                    $paramValue = trim($paramsPair[1]);
                    if ($paramValue === 'false') {
                        $paramValue = false;
                    }
                } else {
                    $paramValue = true;
                }

                $result[$paramName] = $paramValue;

                continue;
            }
            if ($this->allowedUnnamedCount != self::UNNAMED_UNLIMIT) {
                if ($unnamedParamNum > $this->allowedUnnamedCount) {
                    throw new CliParametersError(Trans::T('errors.cli_count'));
                }
            }
            $result['param' . $unnamedParamNum++] = $this->cliArgv[$i];
        }

        return $result;
    }
}