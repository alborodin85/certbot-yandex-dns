<?php

namespace Localization;

use It5\Localization\Trans;
use phpDocumentor\Reflection\Types\This;
use PHPUnit\Framework\TestCase;

class TransTest extends TestCase
{
    public function testTrans()
    {
        Trans::instance()->addPhrases(__DIR__ . '/phases1.php');
        Trans::instance()->addPhrases(__DIR__ . '/phases2.php');

        $correct = '12 рабочих копают котлован утром в 12:30';
        $result = Trans::T('level1.level2.level3', 12, 'котлован', '12:30');
        $this->assertEquals($correct, $result);

        $correct = '3 отдыхающих лежат на песочке вечером в 18:15';
        $result = Trans::T('level4.level5.level6', 3, 'на песочке', '18:15');
        $this->assertEquals($correct, $result);
    }

    public function testError()
    {
        Trans::instance()->clearPhrases();
        Trans::instance()->addPhrases(__DIR__ . '/phases1.php');
        $correct = '12 рабочих копают котлован утром в 12:30';
        $result = Trans::T('level1.level2.level3', 12, 'котлован', '12:30');
        $this->assertEquals($correct, $result);

        $this->expectError();
        Trans::T('level4.level5.level6', 3, 'на песочке', '18:15');
    }
}
