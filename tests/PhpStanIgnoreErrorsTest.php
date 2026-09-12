<?php

namespace Dynamic\Base\Test;

use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the stale ignoreErrors pattern that made the phplinting CI job
 * red on every PR in this repo (dynamic/silverstripe-base-site#205).
 *
 * PHPStan renders the receiver of parent::SearchForm() as PageController<Page> in CI,
 * where silverstan resolves the generic parameter from the full dependency tree, but as a
 * bare PageController against this module's own vendor tree. CI caught the stale pattern;
 * a local phpstan run cannot, because it never produces the generic rendering. These tests
 * therefore pin the generic message as text, taken verbatim from CI run 34662748358 /
 * job 103468543700 ("8.3 mysql80 phplinting"), so narrowing the pattern again fails here
 * instead of only in CI. phpstan's own reportUnmatchedIgnoredErrors stays the authoritative
 * gate in CI - testUnmatchedIgnoredErrorsAreStillEnabled guards that it is not switched off.
 */
class PhpStanIgnoreErrorsTest extends TestCase
{
    /**
     * The message as PHPStan reports it in CI, with the generic parameter.
     */
    private const GENERIC_MESSAGE = 'Call to an undefined static method PageController<Page>::SearchForm().';

    /**
     * The same message as local phpstan reports it, without the generic parameter.
     */
    private const NON_GENERIC_MESSAGE = 'Call to an undefined static method PageController::SearchForm().';

    /**
     * The config entry's path, used only to describe the entry in failure messages.
     */
    private const CONFIG_PATH = 'src/Page/SearchPageController.php';

    /**
     * Both phpstan renderings of the suppressed call must be matched by the one pattern.
     *
     * @return void
     */
    public function testSearchFormIgnorePatternSuppressesBothPhpStanRenderings(): void
    {
        $pattern = $this->searchFormIgnorePattern();

        $this->assertSame(
            1,
            preg_match($pattern, self::NON_GENERIC_MESSAGE),
            'The pattern must match the non-generic rendering local phpstan reports.'
        );
        $this->assertSame(
            1,
            preg_match($pattern, self::GENERIC_MESSAGE),
            'The pattern must match the generic rendering CI reports - the miss that made '
            . 'phplinting red on every PR (dynamic/silverstripe-base-site#205).'
        );
    }

    /**
     * The optional generic suffix stays bounded: near-miss messages must not be suppressed,
     * or the entry would hide real calls to a genuinely undefined method.
     *
     * @return void
     */
    public function testSearchFormIgnorePatternDoesNotSuppressUnrelatedCalls(): void
    {
        $pattern = $this->searchFormIgnorePattern();

        $notSuppressed = [
            '',
            'Call to an undefined static method OtherController::SearchForm().',
            'Call to an undefined static method PageController::OtherMethod().',
            'Call to an undefined method PageController<Page>::SearchForm().',
            'Call to an undefined static method PageController<Page>::SearchForm() twice.',
        ];

        foreach ($notSuppressed as $message) {
            $this->assertSame(
                0,
                preg_match($pattern, $message),
                sprintf('The pattern must not match "%s".', $message)
            );
        }
    }

    /**
     * phpstan's own unmatched-ignored-error report is what makes CI catch a stale pattern in
     * the first place; if it is switched off this test is the only remaining guard. NEON reads
     * false, no and off (any case) as boolean false, so all three spellings are checked.
     *
     * @return void
     */
    public function testUnmatchedIgnoredErrorsAreStillEnabled(): void
    {
        $contents = $this->configContents();

        $this->assertSame(
            0,
            preg_match('/reportUnmatchedIgnoredErrors\s*:\s*(false|no|off)\b/i', $contents),
            'reportUnmatchedIgnoredErrors is disabled, so a stale ignoreErrors pattern '
            . 'would no longer fail phplinting.'
        );
    }

    /**
     * The message pattern of the ignoreErrors entry covering SearchPageController::SearchForm().
     *
     * Read as text rather than parsed as YAML: phpstan.neon.dist is NEON, and NEON accepts
     * scalars such as %rootDir%/tmp that Symfony's YAML parser rejects outright.
     *
     * @return string
     */
    private function searchFormIgnorePattern(): string
    {
        $patterns = [];

        foreach ($this->configMessageLines() as $line) {
            if (str_contains($line, 'PageController') && str_contains($line, 'SearchForm')) {
                $patterns[] = $line;
            }
        }

        $this->assertCount(
            1,
            $patterns,
            sprintf(
                'Expected exactly one ignoreErrors message pattern for %s::SearchForm(), got %d.',
                self::CONFIG_PATH,
                count($patterns)
            )
        );

        return $patterns[0];
    }

    /**
     * Every declared ignoreErrors message pattern in phpstan.neon.dist, delimiters included.
     *
     * @return array<int, string>
     */
    private function configMessageLines(): array
    {
        $matched = preg_match_all('/^\s*message:\s*\'([^\']*)\'\s*$/m', $this->configContents(), $matches);

        $this->assertIsInt($matched, 'Could not scan phpstan.neon.dist for ignoreErrors patterns.');

        return $matches[1];
    }

    /**
     * @return string
     */
    private function configContents(): string
    {
        $configPath = dirname(__DIR__) . '/phpstan.neon.dist';
        $contents = @file_get_contents($configPath);

        $this->assertIsString($contents, sprintf('Unable to read %s.', $configPath));

        return $contents;
    }
}
