<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractValueElement;

class CodeElement extends AbstractValueElement
{
    public function isCodeBlock()
    {
        $tokens = $tokens = array_slice(
            token_get_all('<?php '.$this->getValue()),
            1
        );

        return is_array($tokens[0]) &&
            (
                end($tokens) === '}' ||
                $this->hasChildren()
            ) &&
            in_array($tokens[0][0], [
                T_CATCH,
                T_CLASS,
                T_DO,
                T_ELSE,
                T_ELSEIF,
                T_EXTENDS,
                T_FINALLY,
                T_FOR,
                T_FOREACH,
                T_FUNCTION,
                T_IF,
                T_IMPLEMENTS,
                T_INTERFACE,
                T_NAMESPACE,
                T_SWITCH,
                T_TRAIT,
                T_TRY,
                T_WHILE,
            ]);
    }
}
