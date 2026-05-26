<?php

/*
 -------------------------------------------------------------------------
 moreticket plugin for GLPI
 Copyright (C) 2015-2026 by the moreticket Development Team.

 https://github.com/InfotelGLPI/moreticket
 -------------------------------------------------------------------------

 LICENSE

 This file is part of moreticket.

 moreticket is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 moreticket is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with moreticket. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

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
