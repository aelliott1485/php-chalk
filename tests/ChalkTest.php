<?php

namespace TdTrung\Chalk\Tests;

use PHPUnit\Framework\TestCase;
use TdTrung\Chalk\Chalk;
use TdTrung\Chalk\InvalidStyleException;

class ChalkTest extends TestCase
{
    /**
     * Builds a Chalk instance with a fixed, deterministic color support
     * level so tests don't depend on the terminal/CI environment.
     */
    private function makeChalk(int $supportLevel = 3): Chalk
    {
        $chalk = new Chalk();

        $property = new \ReflectionProperty(Chalk::class, 'supportLevel');
        $property->setAccessible(true);
        $property->setValue($chalk, $supportLevel);

        return $chalk;
    }

    public function testChainedStyleMatchesBoldGreenExample(): void
    {
        $text = 'Bold Green';

        $this->assertSame("\033[32m\033[1m$text\033[0m\033[0m", $this->makeChalk()->bold->green($text));
    }

    public function testChainedForegroundAndBackground256ColorsExample(): void
    {
        $text = 'Blink Foreground 220 Background 20';

        $result = $this->makeChalk()->underscore->color220->bgColor20($text);

        $this->assertSame("\033[48;5;20m\033[38;5;220m\033[4m$text\033[0m\033[0m\033[0m", $result);
    }

    public function testRgbTrueColorWithInverseExample(): void
    {
        $text = 'Inverse' . PHP_EOL;

        $result = $this->makeChalk()->rgb(200, 20, 100)->inverse($text);

        $this->assertSame("\033[7m\033[38;2;200;20;100m$text\033[0m\033[0m", $result);
    }

    public function testStyleNestingExample(): void
    {
        $chalk = $this->makeChalk();

        $firstText = 'Red then';
        $secondText = 'bold and green then';
        $thirdText = 'back to normal' . PHP_EOL;

        $this->assertSame(
            "\033[31m$firstText \033[32m\033[1m$secondText\033[0m\033[0m \033[0m$thirdText\033[0m\033[0m",
            $chalk->red($firstText, $chalk->bold->green($secondText), $chalk->reset($thirdText))
        );
    }

    public function test256ColorForegroundBlockExample(): void
    {
        $color = 'color100';
        $text = ' 100 ';

        $this->assertSame("\033[38;5;100m$text\033[0m", $this->makeChalk()->$color($text));
    }

    public function test256ColorBackgroundBlockExample(): void
    {
        $color = 'bgColor100';
        $text = '  ';

        $this->assertSame("\033[48;5;100m$text\033[0m", $this->makeChalk()->$color($text));
    }

    public function testInvalidStyleThrowsInvalidStyleException(): void
    {
        $this->expectException(InvalidStyleException::class);
        $this->expectExceptionMessage('Invalid style notAStyle.');

        $this->makeChalk()->notAStyle;
    }

    public function testDisableColorReturnsPlainTextEvenWithFullColorSupport(): void
    {
        $chalk = $this->makeChalk(3);
        $chalk->disableColor();
        $text = 'plain text';

        $this->assertSame($text, $chalk->bold->red($text));
    }

    public function testNoColorSupportOnDumbTerminalReturnsPlainText(): void
    {
        $previousTerm = getenv('TERM');
        putenv('TERM=dumb');
        $text = 'plain text';

        try {
            $chalk = new Chalk();
            $this->assertFalse($chalk->hasColorSupport());
            $this->assertSame($text, $chalk->bold->red($text));
        } finally {
            putenv($previousTerm === false ? 'TERM' : "TERM={$previousTerm}");
        }
    }
}
