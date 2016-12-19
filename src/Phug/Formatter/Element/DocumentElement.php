<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Formatter\MarkupInterface;
use Phug\Formatter\Partial\MarkupTrait;

class DocumentElement extends AbstractElement implements MarkupInterface
{
	use MarkupTrait;

	public function getName()
	{
		return 'document';
	}
}
