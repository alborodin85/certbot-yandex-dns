<?php

namespace It5\Localization;

class Ru
{
    private array $phrases = [
        'errors' => [
            'cli_count' => 'Передано безымянных параметров больше, чем требуется!',
            'cli_redundant_param' => 'Передан лишний параметр %s',
            'cli_registry_not_inited' => 'CliParametersRegistry не инициализирован!',
            'domain_registry_not_inited' => 'DomainsParametersRegistry не инициализирован!',
            'domain_empty_param' => 'Для домена %s не указан параметр %s!',
            'domain_coll_incorrect_value' => 'Некорректный тип элемента DomainsParametersCollection!',
            'domain_absent' => 'Для домена %s не указаны параметры!',
            'domain_redundant_record' => 'Домен %s встречается в конфиге несколько раз!',
            'openssl_error' => 'Ошибка получения срока действия текущего сертификата. 
                Проверьте файл сертификата и работоспособность программы openssl.'
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

    public static function phrases(): array
    {
        return self::singleton()->phrases;
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