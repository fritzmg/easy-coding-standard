<?php

declare(strict_types=1);

namespace Contao\EasyCodingStandard\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class MockMethodChainingIndentationFixer extends AbstractFixer
{
    use IndentationFixerTrait;

    /**
     * @var array
     */
    private static $methods = [
        'after',
        'expects',
        'method',
        'will',
        'willReturn',
        'willReturnReference',
        'willReturnMap',
        'willReturnArgument',
        'willReturnCallback',
        'willReturnSelf',
        'willReturnOnConsecutiveCalls',
        'willThrowException',
        'with',
        'withConsecutive',
        'withAnyParameters',
    ];

    public function getDefinition(): FixerDefinition
    {
        return new FixerDefinition(
            'Chained mock methods must be properly indented.',
            [
                new CodeSample(
                    '<?php

use PHPUnit\Framework\TestCase;

class SomeTest extends TestCase
{
    public function testFoo(): void
    {
        $mock = $this->createMock(Foo::class);
        $mock
            ->method("isBar")
            ->willReturn(false);
}
'
                ),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_OBJECT_OPERATOR);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 1, $count = \count($tokens); $index < $count; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_OBJECT_OPERATOR)) {
                continue;
            }

            $nextMeaningful = $tokens->getNextMeaningfulToken($index);

            if (
                !$tokens[$nextMeaningful + 1]->equals('(')
                || !\in_array($tokens[$nextMeaningful]->getContent(), self::$methods, true)
            ) {
                continue;
            }

            $start = $tokens->getPrevTokenOfKind($index, [';', '{']);
            $end = $tokens->getNextTokenOfKind($index, [';']);
            $operators = $tokens->findGivenKind(T_OBJECT_OPERATOR, $start, $end);

            // Single method call
            if (1 === \count($operators)) {
                continue;
            }

            $shift = 0;
            $indent = $this->getIndent($tokens, $index);

            foreach (array_keys($operators) as $pos) {
                $key = $pos + $shift;

                if (!\in_array($tokens[$key + 1]->getContent(), self::$methods, true)) {
                    continue;
                }

                if (!$tokens[$key - 1]->isGivenKind(T_WHITESPACE)) {
                    $tokens->insertAt($key, new Token([T_WHITESPACE, $indent.'    ']));
                    ++$shift;
                }
            }

            $index = $end;
        }
    }
}
