<?	// http://sgusar6i.beget.tech/handler_new.php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
	global $USER;
	$USER->Authorize(1);
	CModule::IncludeModule('iblock');

	function poisk_lev_prav__arr($lev, $prav, $stroka) {
		$razbien_array = explode($lev, $stroka);
		for ($i = 1; $i < count($razbien_array); $i++) {
			$razbien_array_1 = explode($prav, $razbien_array[$i]);
			$stroka_arr[] = trim($razbien_array_1[0]);
		}
	return $stroka_arr;
	}
	
	function poisk_lev_prav__lev($lev, $prav, $stroka) {
		$razbien_array = explode($lev, $stroka);
		$razbien_array_1 = explode($prav, $razbien_array[1]);
		$iskom_stroka = trim($razbien_array_1[0]);
		return $iskom_stroka;
	}
	
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
 
	$fh = fopen(__DIR__  . '/___catalog-feed.xml', 'r');
	$content = fread($fh, 99999999);
	fclose($fh);

	$arr = poisk_lev_prav__arr('<product', '</product>', $content);
	echo count($arr) . '<br>';
	$barands_arr = [];
	$color_arr = [];
	for ($i = 0; $i < count($arr); $i++) {
		$brand = poisk_lev_prav__lev('<brand>', '</brand>', $arr[$i]);
		$color = poisk_lev_prav__lev('<color>', '</color>', $arr[$i]);
		if (!in_array($brand, $barands_arr)) {
			$barands_arr[] = $brand;
		}
		if (!in_array($color, $color_arr)) {
			$color_arr[] = $color;
		}
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
	for ($i = 1; $i < count($arr); $i++) {
		$uuid = poisk_lev_prav__lev('uuid="', '">', $arr[$i]);
		$title = poisk_lev_prav__lev('<title>', '</title>', $arr[$i]);
		$code = poisk_lev_prav__lev('<code>', '</code>', $arr[$i]);
		$price = poisk_lev_prav__lev('<price>', '</price>', $arr[$i]);
		$category_uuid = poisk_lev_prav__lev('<category_uuid>', '</category_uuid>', $arr[$i]);
		$description = poisk_lev_prav__lev('<description>', '</description>', $arr[$i]);
		$brand = poisk_lev_prav__lev('<brand>', '</brand>', $arr[$i]);
		$color = poisk_lev_prav__lev('<color>', '</color>', $arr[$i]);
		
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

		file_put_contents('counter.txt', $i . ' ');
		//die();
	}
