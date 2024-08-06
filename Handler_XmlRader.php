<?	// http://sgusar6i.beget.tech/handler.php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
	global $USER;
	$USER->Authorize(1);
	CModule::IncludeModule('iblock');

    $rxml = new XMLReader();
    $rxml->open('___catalog-feed.xml');
 
	while($rxml->read() && $rxml->name !== 'category'); // Перемещаемся к первому продукту
	while($rxml->name === 'category') {
		$node = new SimpleXMLElement($rxml->readOuterXML()); // Читаем значение элементов
		
		$bs = new CIBlockSection;
		$arFields = Array(
			'ACTIVE' => 'Y',
			'IBLOCK_ID' => 4,
			'NAME' => $node->title,
			'CODE' => $node->code,
			'XML_ID' => $rxml->getAttribute('uuid')
		);
		
		// проверяем наличие раздела - есть уже или нет в инфоблоке
		$ID = CIBlockSection::GetList(array(), array('IBLOCK_ID' => 4, 'XML_ID' => $rxml->getAttribute('uuid')), false, array('ID'), false)->Fetch()['ID'];
		if ($ID > 0) {
			$res = $bs->Update($ID, $arFields);
		} else {
			$ID = $bs->Add($arFields);
			$res = ($ID > 0);
		}
		if(!$res)
		  echo $bs->LAST_ERROR;

		$rxml->next('category'); // Перемещаемся к следующему
	}

	$barands_arr = [];
	$color_arr = [];
	while($rxml->read() && $rxml->name !== 'product'); // Перемещаемся к первому продукту
	while($rxml->name === 'product') {
		$node = new SimpleXMLElement($rxml->readOuterXML()); // Читаем значение элементов
		if (!in_array($node->brand, $barands_arr)) {
			$barands_arr[] = $node->brand;
		}
		if (!in_array($node->color, $color_arr)) {
			$color_arr[] = $node->color;
		}
		$rxml->next('product'); // Перемещаемся к следующему
	}
	//echo '<pre>barands_arr: '; print_r($barands_arr); echo '</pre>';
	//echo '<pre>color_arr: '; print_r($color_arr); echo '</pre>';

 	// свойства ИБ - Брэнд и Цвет
	$arFields = Array(
		'NAME' => 'Брэнд',
		'ACTIVE' => 'Y',
		'CODE' => 'BRAND',
		'PROPERTY_TYPE' => 'L',
		'IBLOCK_ID' => 4
	);
	foreach ($barands_arr as $value) {
		$arFields['VALUES'][] = Array(
			'VALUE' => $value,
			'DEF' => 'N',
		);
	}
	$ibp = new CIBlockProperty;
	$id_prop = CIBlockProperty::GetList(array(), array('IBLOCK_ID' => 4, 'CODE' => 'BRAND'))->Fetch()['ID'];
	
	if ($id_prop > 0) {
		if(!$ibp->Update($id_prop, $arFields))
			echo $ibp->LAST_ERROR;
	} else {
		if(!$ibp->Add($arFields))
			echo $ibp->LAST_ERROR;
	}
	
	$arFields = Array(
		'NAME' => 'Цвет',
		'ACTIVE' => 'Y',
		'CODE' => 'COLOR',
		'PROPERTY_TYPE' => 'L',
		'IBLOCK_ID' => 4
	);
	foreach ($color_arr as $value) {
		$arFields['VALUES'][] = Array(
			'VALUE' => $value,
			'DEF' => 'N',
		);
	}
	$ibp = new CIBlockProperty;
	$id_prop = CIBlockProperty::GetList(array(), array('IBLOCK_ID' => 4, 'CODE' => 'COLOR'))->Fetch()['ID'];
	if ($id_prop > 0) {
		if(!$ibp->Update($id_prop, $arFields))
			echo $ibp->LAST_ERROR;
	} else {
		if(!$ibp->Add($arFields))
			echo $ibp->LAST_ERROR;
	}
	
	$property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID" => 4, "CODE" => "BRAND"));
	while($enum_fields = $property_enums->GetNext()) {
		$brand_ass[$enum_fields["VALUE"]] = $enum_fields["ID"];
	}
	$property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID" => 4, "CODE" => "COLOR"));
	while($enum_fields = $property_enums->GetNext()) {
		$color_ass[$enum_fields["VALUE"]] = $enum_fields["ID"];
	}
	//echo '<pre>brand_ass: '; print_r($brand_ass); echo '</pre>';
	//echo '<pre>color_ass: '; print_r($color_ass); echo '</pre>';
	// закончили свойства ИБ - Брэнд и Цвет
	
	// заполняем товары
	while($rxml->read() && $rxml->name !== 'product'); // Перемещаемся к первому продукту
	while($rxml->name === 'product') {
		$node = new SimpleXMLElement($rxml->readOuterXML()); // Читаем значение элементов
		
		$uuid = $node->uuid;
		$title = $node->title;
		$code = $node->code;
		$price = $node->price;
		$category_uuid = $node->category_uuid;
		$description = $node->description;
		$brand = $node->brand;
		$color = $node->color;

		$el = new CIBlockElement;
		$arFields = Array(
			'MODIFIED_BY'    => $USER->GetID(),
			'IBLOCK_SECTION_ID' => CIBlockSection::GetList(array(), array('IBLOCK_ID' => 4, 'XML_ID' => $category_uuid), false, array('ID'), false)->Fetch()['ID'],
			'IBLOCK_ID'      => 4,
			'NAME'           => $title,
			'ACTIVE'         => 'Y',
			'PREVIEW_TEXT'   => $description,
			'DETAIL_TEXT'    => $description,
			'XML_ID' => $uuid,
			'CODE' => $code,
		);
		//echo "<pre>arFields: "; print_r($arFields); echo "</pre>";

		// проверяем наличие элемента - есть уже или нет в инфоблоке
		$ID = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 4, 'XML_ID' => $uuid), false, array('ID'), false)->Fetch()['ID'];
		if ($ID > 0) {
			$res = $el->Update($ID, $arFields);
		} else {
			$ID = $el->Add($arFields);
			$res = ($ID > 0);
		}
		if(!$res)
			echo $el->LAST_ERROR;

		CIBlockElement::SetPropertyValuesEx($ID, 4, array('BRAND' => $brand_ass[$brand], 'COLOR' => $color_ass[$color]));

		$arFieldsPrice = array('PRODUCT_ID' => $ID, 'CATALOG_GROUP_ID' => 1, 'PRICE' => $price, 'CURRENCY' => !$currency ? 'RUB' : $currency);
		// смотрим установлена ли цена адля данного товара
		$dbPrice = \Bitrix\Catalog\Model\Price::getList([
			'filter' => array('PRODUCT_ID' => $ID, 'CATALOG_GROUP_ID' => 1)
		]);
		if ($arPrice = $dbPrice->fetch()) {
			// если цена установлена, то обновляем
			$result = \Bitrix\Catalog\Model\Price::update($arPrice['ID'], $arFieldsPrice);
			if ($result->isSuccess()) {
				//echo 'Обновили цену у товара у элемента каталога ' . $ID . ' Цена ' . $price . PHP_EOL;
			} else {
				//echo 'Ошибка обновления цены у товара у элемента каталога ' . $ID . ' Ошибка ' . $result->getErrorMessages() . PHP_EOL;
			}
		} else {
			// если цены нет, то добавляем
			$result = \Bitrix\Catalog\Model\Price::add($arFieldsPrice);
			if ($result->isSuccess()) {
				//echo 'Добавили цену у товара у элемента каталога ' . $ID . ' Цена ' . $price . PHP_EOL;
			} else {
				//echo 'Ошибка добавления цены у товара у элемента каталога ' . $ID . ' Ошибка ' . $result->getErrorMessages() . PHP_EOL;
			}
		}

		$rxml->next('product'); //перемещаемся к следующему
	}
