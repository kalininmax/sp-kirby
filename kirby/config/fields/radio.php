<?php

return [
	'mixins' => ['options'],
	'props' => [
		/**
		 * Unset inherited props
		 */
		'after'       => null,
		'before'      => null,
		'icon'        => null,
		'placeholder' => null,

		/**
		 * Arranges the radio buttons in the given number of columns
		 */
		'columns' => function (int $columns = 1) {
			return $columns;
		},
	],
	'computed' => [
		'default' => function () {
			$default = $this->model()->toString($this->default);
			return $this->sanitizeOption($default);
		},
		'value' => function () {
			return $this->sanitizeOption($this->value) ?? '';
		}
	]
];
