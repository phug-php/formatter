<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
use Phug\Formatter\MarkupInterface;
use Phug\Formatter\Partial\MarkupTrait;
use Phug\Parser\NodeInterface as ParserNode;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\NameTrait;
use Phug\Util\UnorderedArguments;
use SplObjectStorage;

class MarkupElement extends AbstractElement implements MarkupInterface
{
    use AttributeTrait;
    use NameTrait;
    use MarkupTrait;

    /**
     * @var bool
     */
    protected $autoClosed = false;

    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $name = $arguments->optional('string');
        if (is_null($name)) {
            $name = $arguments->optional(ExpressionElement::class);
        }
        $this->autoClosed = $arguments->optional('boolean');
        $originNode = $arguments->optional(ParserNode::class);
        $parent = $arguments->optional(NodeInterface::class);
        $attributes = $arguments->optional(SplObjectStorage::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($originNode, $parent, $children);

        $this->setName($name);
        if ($attributes) {
            $this->getAttributes()->addAll($attributes);
        }
    }

    public function getAttribute($name)
    {
        foreach ($this->getAttributes() as $attribute) {
            if ($attribute->getName() === $name) {
                return $attribute->getValue();
            }
        }
    }

    /**
     * @return bool
     */
    public function isAutoClosed()
    {
        return $this->autoClosed;
    }
}
