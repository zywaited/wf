<?php

namespace Wf\Bean\Parser;

use Wf\Core\{
	Context, RuntimeException
};

class ValueParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		$config = (Context::instance())->getConfig();
		$values = explode('.', $annotationObject->value);
		foreach ($values as $key) {
			if (!isset($config->{$key})) {
				throw new RuntimeException(
					"the config[{$annotationObject->value}] not exists on class[{$objectDefinition->getClassName()}]"
				);	
			}

			$config = $config->{$key};
		}

		$typeObject->setRef(false)->setValue($config);
	}
}