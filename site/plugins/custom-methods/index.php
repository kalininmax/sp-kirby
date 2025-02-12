<?php

use Kirby\Cms\Url;

Kirby::plugin('pfrlv/custom-methods', [
  'fieldMethods' => [
    'typograf' => function ($field) {
      $t = new \Akh\Typograf\Typograf();
      return $t->apply($field->value);
    },
  ],
]);
