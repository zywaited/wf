<?php

namespace Wf\Bean\Annotations;

class Declared
{
	const UNDEFINED = 0;

	const TYPE = 1;

	const CUSTOMIZE_ANNOTATION = 2;

	const SYSTEM_ANNOTATION = 4;

	const CONSTRUCT = 8;

	const PROPERTY = 16;

	const METHOD = 32;

	// 常用
	const ANNOTATION = 6;

	const ALL = 65;
}