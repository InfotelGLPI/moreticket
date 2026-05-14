<?php

namespace GlpiPlugin\Moreticket\Tests\Unit;

use GlpiPlugin\Moreticket\Profile;
use PHPUnit\Framework\TestCase;

class ProfileTest extends TestCase
{
    public function testTranslateARightEmptyStringReturnsZero(): void
    {
        $this->assertSame(0, Profile::translateARight(''));
    }

    public function testTranslateARightRReturnsReadConstant(): void
    {
        $this->assertSame(READ, Profile::translateARight('r'));
    }

    public function testTranslateARightWReturnsFullWriteValue(): void
    {
        $expected = ALLSTANDARDRIGHT + READNOTE + UPDATENOTE;
        $this->assertSame($expected, Profile::translateARight('w'));
    }

    public function testTranslateARightZeroStringReturnsSameString(): void
    {
        $this->assertSame('0', Profile::translateARight('0'));
    }

    public function testTranslateARightOneStringReturnsSameString(): void
    {
        $this->assertSame('1', Profile::translateARight('1'));
    }

    public function testTranslateARightUnknownStringReturnsZero(): void
    {
        $this->assertSame(0, Profile::translateARight('UNKNOWN'));
    }

    public function testTranslateARightArbitraryStringReturnsZero(): void
    {
        $this->assertSame(0, Profile::translateARight('admin'));
    }
}
