<?php

class Bf_Product extends Bf_MutableEntity {
	protected static $_resourcePath;

	public static function initStatics() {
		self::$_resourcePath = new Bf_ResourcePath('products', 'Product');
	}
}
Bf_Product::initStatics();
