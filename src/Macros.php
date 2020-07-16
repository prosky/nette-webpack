<?php declare(strict_types=1);

namespace Prosky\NetteWebpack;

use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Macros\MacroSet;


class Macros extends MacroSet
{

    public static function install(Compiler $compiler, string $macroName): Macros
    {
        $me = new static($compiler);
        $me->addMacro($macroName, [$me, 'assetMacro']);
        return $me;
    }

    public function assetMacro(MacroNode $node, PhpWriter $writer): string
    {
        return $writer->write('echo $this->global->assetsPathProvider->locate(%node.word)');
    }

}
