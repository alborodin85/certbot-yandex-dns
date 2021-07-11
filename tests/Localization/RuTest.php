<?php

namespace Localization;

use It5\Localization\Ru;
use PHPUnit\Framework\TestCase;

class RuTest extends TestCase
{

    public function testGet()
    {
        $this->assertEquals(Ru::phrases()['errors']['cli_count'], Ru::get('errors.cli_count'));
        $domain = 'it5.su';
        $parameter = 'subDomains';
        $this->assertEquals(
            sprintf(Ru::phrases()['errors']['domain_empty_param'], $domain, $parameter),
            Ru::get('errors.domain_empty_param', $domain, $parameter)
        );
    }
}
