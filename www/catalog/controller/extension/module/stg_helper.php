<?php

/**
 * @category   OpenCart
 * @package    SEO Tags Generator
 * @copyright  © Serge Tkach, 2017, http://sergetkach.com/
 */


class ControllerExtensionModuleStgHelper extends Controller {
	private $stg;

	public function __construct($registry) {
		parent::__construct($registry);

		if (version_compare(PHP_VERSION, '7.2') >= 0) {
			$php_v = '72_73';
		} elseif (version_compare(PHP_VERSION, '7.1') >= 0) {
			$php_v = '71';
		} elseif (version_compare(PHP_VERSION, '5.6.0') >= 0) {
			$php_v = '56_70';
		} elseif (version_compare(PHP_VERSION, '5.4.0') >= 0) {
			$php_v = '54_56';
		} else {
			echo "Sorry! Version for PHP 5.3 Not Supported!<br>Please contact to author!";
			exit;
		}

		require_once DIR_SYSTEM . 'library/seo_tags_generator/seo_tags_generator_' . $php_v . '.php';

		$this->stg = new SeoTagsGenerator();
		$this->stg->setLicence($this->config->get('seo_tags_generator_licence'));
	}


	public function getCategoryTags($category_info) {
		if (!is_array($category_info)) {
			return false;
		}

		$lang_id = $this->config->get('config_language_id');

		foreach ($category_info as $key => $value) {
			$category_info[$key] = is_string($value) ? trim($value) : $value;
		}

		if (!$this->config->get('seo_tags_generator_status')) {
			return $category_info;
		}

		if (array_key_exists('h1', $category_info)) {
			$h1 = 'h1'; // Мой модификатор
		} elseif (array_key_exists('meta_h1', $category_info)) {
			$h1 = 'meta_h1'; // ocStore
		} else {
			$h1 = false;
		}

		$this->load->model('extension/module/seo_tags_generator');

		if (!$this->model_extension_module_seo_tags_generator->notUseAutoCategory($category_info['category_id'])) {
			$a_specific_formula = $this->model_extension_module_seo_tags_generator->getSTGFormulasByCatId($category_info['category_id'], $lang_id, 'category');
		} else {
			// Change vars in real meta-tags
			$a_specific_formula = array(
				'title'				 => $category_info['meta_title'],
				'description'	 => $category_info['meta_description'],
				'keyword'			 => $category_info['meta_keyword'],
				'text'				 => $category_info['description']
			);

			if ($h1 && isset($category_info[$h1])) {
				$a_specific_formula['h1'] = $category_info[$h1];
			}
		}

		// Внимание!
		// В специфических формулах может быть такое, что задан только title или только description (!)
		// В админке не проверяется на заполненность всех полей для специфических формул

		if (isset($a_specific_formula['title']) && !empty($a_specific_formula['title'])) {
			$f_title = html_entity_decode($a_specific_formula['title'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_title = $this->config->get('seo_tags_generator_category_title');
			$f_title = html_entity_decode($f_title[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['description']) && !empty($a_specific_formula['description'])) {
			$f_description = html_entity_decode($a_specific_formula['description'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_description = $this->config->get('seo_tags_generator_category_description');
			$f_description = html_entity_decode($f_description[$lang_id], ENT_QUOTES, 'UTF-8');
		}

        $f_description = $this->addOCFilterValues($f_description);


		if (isset($a_specific_formula['keyword']) && !empty($a_specific_formula['keyword'])) {
			$f_keyword = html_entity_decode($a_specific_formula['keyword'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_keyword = $this->config->get('seo_tags_generator_category_keyword');
			$f_keyword = html_entity_decode($f_keyword[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['h1']) && !empty($a_specific_formula['h1'])) {
			$f_h1 = html_entity_decode($a_specific_formula['h1'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_h1	 = $this->config->get('seo_tags_generator_category_h1');
			$f_h1	 = html_entity_decode($f_h1[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['text']) && !empty($a_specific_formula['text'])) {
			$f_text = html_entity_decode($a_specific_formula['text'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_text = $this->config->get('seo_tags_generator_category_text');
			$f_text = html_entity_decode($f_text[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		// Чисто для isFollowedVar()
		$formulas_array = array(
			'title'				 => $f_title,
			'description'	 => $f_description,
			'keyword'			 => $f_keyword,
			'h1' => $f_h1,
			'text'				 => $f_text,
			'ci_meta_title' => html_entity_decode($category_info['meta_title'], ENT_QUOTES, 'UTF-8'),
			'ci_meta_description' => html_entity_decode($category_info['meta_description'], ENT_QUOTES, 'UTF-8'),
			'ci_meta_keyword' => html_entity_decode($category_info['meta_keyword'], ENT_QUOTES, 'UTF-8'),
			'ci_description' => html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'),
		);

		if ($h1 && isset($category_info[$h1])) {
			$formulas_array['ci_h1'] = html_entity_decode($category_info[$h1], ENT_QUOTES, 'UTF-8');
		}

		### Подготовка данных

		// Данные из $category_info по умолчанию, которые будут участвовать в заменах с помощью функций!
		$var_values = array(
			'category_name' => $category_info['name'],
		);

		$var_values['static_category_h1'] = '';

		if ($h1 && $category_info[$h1]) {
			$var_values['static_category_h1'] = $category_info[$h1];
		}

		if ($this->isFollowedVar('page_number', $formulas_array)) {
			$var_values['page_number'] = isset($this->request->get['page']) && $this->request->get['page'] ? $this->request->get['page'] : false;
		}

		if ($this->isFollowedVar('shop_name', $formulas_array)) {
			$var_values['shop_name'] = $this->config->get('config_name');
		}

		if ($this->isFollowedVar('config_telephone', $formulas_array)) {
			$var_values['config_telephone'] = $this->config->get('config_telephone');
		}

		// category_name - already exist
		if ($this->isFollowedVar('category_name_', $formulas_array)) {
			$category_declension = $this->model_extension_module_seo_tags_generator->getCategoryDeclension($category_info['category_id'], $lang_id);

			if (is_array($category_declension)) {
					$var_values['category_name_singular_nominative'] = $category_declension['category_name_singular_nominative'] ? $category_declension['category_name_singular_nominative'] : false;
					$var_values['category_name_plural_nominative'] = $category_declension['category_name_plural_nominative'] ? $category_declension['category_name_plural_nominative'] : false;
					$var_values['category_name_plural_genitive'] = $category_declension['category_name_plural_genitive'] ? $category_declension['category_name_plural_genitive'] : false;
				} else {
					// Юзеру сразу видно, что он не заполнил переменные, то есть переменные вообще не попадают в список переменных
					$var_values['category_name_singular_nominative'] = $var_values['category_name_plural_nominative'] = $var_values['category_name_plural_genitive'] = false;
				}
		}

		if ($this->isFollowedVar('city', $formulas_array)) {
			$config_store	= $this->config->get('config_store');
			$config_store	= $config_store[$lang_id];

			//$followed_variables[] = 'city'; // ... multiple

			$var_values['city'] = $config_store['city'];
			$var_values['city_genitive'] = $config_store['city_genitive'];
			$var_values['city_dative'] = $config_store['city_dative'];
			$var_values['city_prepositional'] = $config_store['city_prepositional'];
		}

		// count products in cat
		if ($this->isFollowedVar('count_products', $formulas_array)) {
			$filter_data = array(
				'filter_category_id' => $category_info['category_id'],
				'filter_sub_category' => true
			);

/* add mofificator for use  ocfilter filter data
for filter category pages use filtered count of products

*/
            if (isset($this->request->get['filter_ocfilter'])) {
                $filter_ocfilter = $this->request->get['filter_ocfilter'];
                $filter_data['filter_ocfilter'] = $filter_ocfilter;
            }else{
                $filter_ocfilter = '';
            }

			$var_values['count_products'] = $this->model_catalog_product->getTotalProducts($filter_data);
		}

		// get min price in this category
		if ($this->isFollowedVar('min_price', $formulas_array)) {
			$min_price = $this->model_extension_module_seo_tags_generator->getMinPriceInCat($category_info['category_id'],$filter_ocfilter);

			if ($min_price) {
				$var_values['min_price'] = $min_price;
			} else {
				$var_values['min_price'] = 0;
			}
		}

		// get max price in this category
		if ($this->isFollowedVar('max_price', $formulas_array)) {
			$max_price = $this->model_extension_module_seo_tags_generator->getMaxPriceInCat($category_info['category_id'],$filter_ocfilter);

			if ($max_price) {
				$var_values['max_price'] = $max_price;
			} else {
				$var_values['max_price'] = 0;
			}
		}

		// Category Level
		if ($this->isFollowedVar('category_level', $formulas_array)) {
			$var_values['category_level'] = $this->model_extension_module_seo_tags_generator->getCategoryLevel($category_info['category_id']);

			// Levels for people begin with 1 (not with 0)
			if (false !== $var_values['category_level']) {
				$var_values['category_level']++;
			}
		}

		// Category Nested
		if ($this->isFollowedVar('category_nested', $formulas_array)) {
			// has category_nested without indexes
			// Index array begin with 1 (not 0)
			$categories_names = array();

			$categories_names0 = $this->model_extension_module_seo_tags_generator->getCategoriesNestedNames($category_info['category_id'],	$lang_id);

			foreach ($categories_names0 as $key => $value) {
				$categories_names[$key + 1] = $value['name']; // sort array start with index 1 (not 0)
			}

			// One time is found in all formulas!
			if ($this->isFollowedVar('category_nested SORT_FROM_PARENT_TO_CHILD', $formulas_array)) {
				$categories_names_reverse = array();

				foreach (array_reverse($categories_names) as $key => $value) {
					$categories_names_reverse[$key + 1] = $value; // sort array start with index 1 (not 0)
				}

				$var_values['category_nested SORT_FROM_PARENT_TO_CHILD'] = $this->stg->getCategoryNestedSortedValue($categories_names_reverse);

				if ($this->isFollowedVar('category_nested SORT_FROM_PARENT_TO_CHILD exclude', $formulas_array)) {
					$var_values = array_merge($var_values, $this->excludeCategories($formulas_array, $categories_names_reverse, 'SORT_FROM_PARENT_TO_CHILD'));
				}
			}

			// One time is found in all formulas!
			if ($this->isFollowedVar('category_nested SORT_FROM_CHILD_TO_PARENT', $formulas_array)) {
				$var_values['category_nested SORT_FROM_CHILD_TO_PARENT'] = $this->stg->getCategoryNestedSortedValue($categories_names);

				if ($this->isFollowedVar('category_nested SORT_FROM_CHILD_TO_PARENT exclude', $formulas_array)) {
					$var_values = array_merge($var_values, $this->excludeCategories($formulas_array, $categories_names, 'SORT_FROM_CHILD_TO_PARENT'));
				}
			}

			// One time is found in all formulas!
			if ($this->isFollowedVar('category_nested', $formulas_array)) {
				$var_values['category_nested'] = $this->stg->getCategoryNestedSortedValue($categories_names);

				if ($this->isFollowedVar('category_nested exclude', $formulas_array)) {
					$var_values = array_merge($var_values, $this->excludeCategories($formulas_array, $categories_names));
				}
			}
		}

		if ($this->isFollowedVar('category_nested sort', $formulas_array)) {
			// has category_nested with indexes

			$category_indexes = $this->stg->findCategoryNestedIndexes($formulas_array);

			$categories_keys = $this->stg->getCategoriesKeysForVars($category_indexes);

			//$this->stg->getCategoriesLevels($category_indexes);

			foreach ($category_indexes as $item) {
				$var_values[$item['key']] = $this->stg->getCategoryNestedSortedValue($categories_names, $item['sort']);
			}
		}

		// A! [original_text] must be last!
		if ($this->isFollowedVar('original_text', $formulas_array)) {
			$var_values['original_text'] = $this->stg->parse(html_entity_decode(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $var_values);
		}
		
		// Борьба с багом со скобками при использовании функций
		if (false !== strpos($var_values['category_name'], '(')) {
			$var_values['category_name'] = str_replace(array('(', ')'), array('left_bracket', 'right_bracket'), $var_values['category_name']);
		}

		// Генерация мета-тегов в зависимости от настроек модуля
		$generate_mode = $this->config->get('seo_tags_generator_generate_mode_category');

		if ('nofollow' == $generate_mode) {
			// only vars replace, but no follow formulas
			$category_info['meta_title'] = $this->cleanup($this->stg->parse($category_info['meta_title'], $var_values));
			$category_info['meta_description'] = $this->cleanup($this->stg->parse($category_info['meta_description'], $var_values));
			$category_info['meta_keyword'] = $this->cleanup($this->stg->parse($category_info['meta_keyword'], $var_values));
		}

		if ('empty' == $generate_mode) {
			if (empty($category_info['meta_title'])) {
				$category_info['meta_title'] = $this->cleanup($this->stg->parse($f_title, $var_values));
			} else {
				$category_info['meta_title'] = $this->cleanup($this->stg->parse($category_info['meta_title'], $var_values));
			}

			if (empty($category_info['meta_description'])) {
				$category_info['meta_description'] = $this->cleanup($this->stg->parse($f_description, $var_values));
			} else {
				$category_info['meta_description'] = $this->cleanup($this->stg->parse($category_info['meta_description'], $var_values));
			}

			if (empty($category_info['meta_keyword'])) {
				$category_info['meta_keyword'] = $this->cleanup($this->stg->parse($f_keyword, $var_values));
			} else {
				$category_info['meta_keyword'] = $this->cleanup($this->stg->parse($category_info['meta_keyword'], $var_values));
			}
		}

		if ('forced' == $generate_mode) {
			$category_info['meta_title']			 = $this->cleanup($this->stg->parse($f_title, $var_values));
			$category_info['meta_description'] = $this->cleanup($this->stg->parse($f_description, $var_values));
			$category_info['meta_keyword']		 = $this->cleanup($this->stg->parse($f_keyword, $var_values));
		}

		// Проверяем, не генерится ли H1 по формуле?
		$generate_mode_category_h1 = $this->config->get('seo_tags_generator_generate_mode_category_h1');

		// Заголовок в OpenCart Initial отсутствует, а name (из которого он берется) обязателен
		if ('nofollow' == $generate_mode_category_h1) {
			// only vars replace, but no follow formulas
			if ($h1) {
				$category_info[$h1] = $this->escapeBugParentheses($this->stg->parse($category_info[$h1], $var_values));
			} else {
				$category_info['name'] = $this->escapeBugParentheses($this->stg->parse($category_info['name'], $var_values)); // A! OpenCart Initial - for catalog ONLY
			}
		}

		if ('empty' == $generate_mode_category_h1) {
			if ($h1) {
				if (empty($category_info[$h1])) {
					$category_info[$h1] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
				} else {
					$category_info[$h1] = $this->escapeBugParentheses($this->stg->parse($category_info[$h1], $var_values));
				}
			} else {
				if (empty($category_info['name'])) {
					$category_info['name'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
				} else {
					$category_info['name'] = $this->escapeBugParentheses($this->stg->parse($category_info['name'], $var_values)); // A! OpenCart Initial - for catalog ONLY
				}
			}
		}

		if ('forced' == $generate_mode_category_h1) {
			if ($h1) {
				$category_info[$h1] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
			} else {
				$category_info['name'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values)); // A! OpenCart Initial - for catalog ONLY
			}
		}

		# Description - Text
		#
		// Description - is separated
		// for decode double htmlentities (1 in js in text editor + 1 on save process in DB)
		$category_text_tmp = html_entity_decode(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

		$generate_mode_category_text = $this->config->get('seo_tags_generator_generate_mode_category_text');

		if ('nofollow' == $generate_mode_category_text) {
			// only vars replace, but no follow formulas
			$category_info['description'] = $this->escapeBugParentheses($this->stg->parse($category_text_tmp, $var_values));
		}

		if ('empty' == $generate_mode_category_text) {
			$tmp_descr = trim(str_replace('&nbsp;', '', strip_tags(html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8'))));
			if (empty($tmp_descr)) {
				$category_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
			} else {
				$category_info['description'] = $this->escapeBugParentheses($this->stg->parse($category_text_tmp, $var_values));
			}
		}

		if ('forced' == $generate_mode_category_text) {
			$category_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
		}

		return $category_info;
	}


	public function getProductTags($data) {
		$lang_id = $this->config->get('config_language_id');

		if (!$this->config->get('seo_tags_generator_status')) {
			return $data['product_info'];
		}

		$product_info = $data['product_info'];

		foreach ($product_info as $key => $value) {
			$product_info[$key] = is_string($value) ? trim($value) : $value;
		}

		$attribute_groups = $data['attribute_groups'];

		if (array_key_exists('h1', $product_info)) {
			$h1 = 'h1'; // Мой модификатор
		} elseif (array_key_exists('meta_h1', $product_info)) {
			$h1 = 'meta_h1'; // ocStore
		} else {
			$h1 = false;
		}

		$this->load->model('extension/module/seo_tags_generator');

		$parent_category_id = $this->model_extension_module_seo_tags_generator->getParentCategoryByProductId($product_info['product_id']);

		if (!$this->model_extension_module_seo_tags_generator->notUseAutoProduct($product_info['product_id'])) {
			$a_specific_formula = $this->model_extension_module_seo_tags_generator->getSTGFormulasByCatId($parent_category_id, $lang_id, 'product');
		} else {
			// Change vars in real meta-tags
			$a_specific_formula = array(
				'title'				 => $product_info['meta_title'],
				'description'	 => $product_info['meta_description'],
				'keyword'			 => $product_info['meta_keyword'],
				'text'				 => $product_info['description']
			);

			if ($h1 && isset($product_info[$h1])) {
				$a_specific_formula['h1'] = $product_info[$h1];
			}
		}

		// Внимание!
		// В специфических формулах может быть такое, что задан только title или только description (!)
		// В админке не проверяется на заполненность всех полей для специфических формул

		if (isset($a_specific_formula['title']) && !empty($a_specific_formula['title'])) {
			$f_title = html_entity_decode($a_specific_formula['title'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_title = $this->config->get('seo_tags_generator_product_title');
			$f_title = html_entity_decode($f_title[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['description']) && !empty($a_specific_formula['description'])) {
			$f_description = html_entity_decode($a_specific_formula['description'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_description = $this->config->get('seo_tags_generator_product_description');
			$f_description = html_entity_decode($f_description[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['keyword']) && !empty($a_specific_formula['keyword'])) {
			$f_keyword = html_entity_decode($a_specific_formula['keyword'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_keyword = $this->config->get('seo_tags_generator_product_keyword');
			$f_keyword = html_entity_decode($f_keyword[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['h1']) && !empty($a_specific_formula['h1'])) {
			$f_h1 = html_entity_decode($a_specific_formula['h1'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_h1	 = $this->config->get('seo_tags_generator_product_h1');
			$f_h1	 = html_entity_decode($f_h1[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		if (isset($a_specific_formula['text']) && !empty($a_specific_formula['text'])) {
			$f_text = html_entity_decode($a_specific_formula['text'], ENT_QUOTES, 'UTF-8');
		} else {
			$f_text = $this->config->get('seo_tags_generator_product_text');
			$f_text = html_entity_decode($f_text[$lang_id], ENT_QUOTES, 'UTF-8');
		}

		// Чисто для isFollowedVar()
		$formulas_array = array(
			'title' => $f_title,
			'description' => $f_description,
			'keyword' => $f_keyword,
			'h1' => $f_h1,
			'text' => $f_text,
			'pi_meta_title' => html_entity_decode($product_info['meta_title'], ENT_QUOTES, 'UTF-8'),
			'pi_meta_description' => html_entity_decode($product_info['meta_description'], ENT_QUOTES, 'UTF-8'),
			'pi_meta_keyword' => html_entity_decode($product_info['meta_keyword'], ENT_QUOTES, 'UTF-8'),
			'pi_description' => html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'),
		);

		if ($h1 && isset($product_info[$h1])) {
			$formulas_array['pi_h1'] = html_entity_decode($product_info[$h1], ENT_QUOTES, 'UTF-8');
		}

		### Подготовка данных

		// Данные из $product_info по умолчанию, которые будут участвовать в заменах с помощью функций!
		$var_values = array(
			'minimum'						 => $product_info['minimum'],
			'price'							 => $product_info['price'], // A! without currency
			'price_formatted'		 => $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
			'special'						 => $product_info['special'], // A! without currency
			'special_formatted'	 => $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
			'rating'						 => $product_info['rating'],
			'reviews'						 => $product_info['reviews'],
			'quantity'					 => $product_info['quantity'],
			'viewed'						 => $product_info['viewed'],
			'product_name'			 => $product_info['name'],
			'model'							 => $product_info['model'],
			'sku'								 => $product_info['sku'],
			'upc'								 => $product_info['upc'],
			'ean'								 => $product_info['ean'],
			'jan'								 => $product_info['jan'],
			'isbn'							 => $product_info['isbn'],
			'mpn'								 => $product_info['mpn'],
			'manufacturer'			 => $product_info['manufacturer'],
		);

		// Записываем использованные переменные, которые не являются стандартными полями товаров
		if (isset($product_info['model_synonym'])) {
			$var_values['model_synonym'] = $product_info['model_synonym'];
		}

		$var_values['static_product_h1'] = '';

		if ($h1 && $product_info[$h1]) {
			$var_values['static_product_h1'] = $product_info[$h1];
		}

		if ($this->isFollowedVar('manufacturer', $formulas_array)) {
			$manufacturer_description = $this->model_extension_module_seo_tags_generator->getManufacturerDescription($product_info['manufacturer_id']);

			if (is_array($manufacturer_description)) {
				$var_values['manufacturer_synonym'] = isset($manufacturer_description['name_synonym']) ? $manufacturer_description['name_synonym'] : '';
				$var_values['static_manufacturer_h1'] = isset($manufacturer_description['meta_h1']) ? $manufacturer_description['meta_h1'] : ''; // My modificator don't has h1 for manufacturer
			}
		}

		if ($this->isFollowedVar('page_number', $formulas_array)) {
			$var_values['page_number'] = isset($this->request->get['page']) && $this->request->get['page'] ? $this->request->get['page'] : false;
		}

		if ($this->isFollowedVar('shop_name', $formulas_array)) {
			$var_values['shop_name'] = $this->config->get('config_name');
		}

		if ($this->isFollowedVar('config_telephone', $formulas_array)) {
			$var_values['config_telephone'] = $this->config->get('config_telephone');
		}

		if ($this->isFollowedVar('count_sales', $formulas_array)) {
			$var_values['count_sales'] = $this->model_extension_module_seo_tags_generator->getProductSales($product_info['product_id']);
		}

		if ($this->isFollowedVar('category', $formulas_array)) {
			$category_description = $this->model_extension_module_seo_tags_generator->getCategoryDescriptionByIdAndLang($parent_category_id, $lang_id);

			$var_values['category_name'] = $category_description['name'];

			if ($this->isFollowedVar('static_category_h1', $formulas_array)) {
				$var_values['static_category_h1'] = $category_description['h1'];
			}

			if ($this->isFollowedVar('category_name_', $formulas_array)) {
				$category_declension = $this->model_extension_module_seo_tags_generator->getCategoryDeclension($parent_category_id, $lang_id);

				if (is_array($category_declension)) {
					$var_values['category_name_singular_nominative'] = $category_declension['category_name_singular_nominative'] ? $category_declension['category_name_singular_nominative'] : false;
					$var_values['category_name_plural_nominative'] = $category_declension['category_name_plural_nominative'] ? $category_declension['category_name_plural_nominative'] : false;
					$var_values['category_name_plural_genitive'] = $category_declension['category_name_plural_genitive'] ? $category_declension['category_name_plural_genitive'] : false;
				} else {
					// Юзеру сразу видно, что он не заполнил переменные, то есть переменные вообще не попадают в список переменных
					$var_values['category_name_singular_nominative'] = $var_values['category_name_plural_nominative'] = $var_values['category_name_plural_genitive'] = false;
				}
			}
		}

		if ($this->isFollowedVar('city', $formulas_array)) {
			$config_store	= $this->config->get('config_store');
			$config_store	= $config_store[$lang_id];

			//$followed_variables[] = 'city'; // ... multiple

			$var_values['city'] = $config_store['city'];
			$var_values['city_genitive'] = $config_store['city_genitive'];
			$var_values['city_dative'] = $config_store['city_dative'];
			$var_values['city_prepositional'] = $config_store['city_prepositional'];
		}

		// Attributes
		// Can Be [attributes] (all) & [attribute index="n"] (separately)
		if ($this->isFollowedVar('attribute', $formulas_array)) {
			// Если [attributes] - то просто поместить в переменную [attributes] все атрибуты, которые идут по настройкам
			// Если [attribute index= - то создать переменнуые под каждый атрибут

			$s_attributes = '';

			$parent_category_setting = $this->model_extension_module_seo_tags_generator->getSTGSettingsByCatId($parent_category_id);

			if (isset($parent_category_setting['attributes'])) {
				$attributes_setting = $parent_category_setting['attributes'];
			} else {
				$attributes_setting = $this->config->get('seo_tags_generator_attributes');
			}

			$attr_i_exist = array();

			if (isset($attributes_setting) && count($attributes_setting) > 0) {
				$a_attributes = array();

				// Внимание!
				// Может быть так, что в формуле и настройках атрибут задан, а в самом товаре - нет

				foreach ($attribute_groups as $item) {
					foreach ($item['attribute'] as $attribute) {
						$a_attributes[$attribute['attribute_id']]['name'] = $attribute['name'];
						$a_attributes[$attribute['attribute_id']]['text'] = $attribute['text'];
					}
				}

				$i = 1; // индекс задается порядковым номером при переборе - но не ключом в массиве!!
				foreach ($attributes_setting as $attribute_id) {
					$attr_i_exist[] = $i;

					if (isset($a_attributes[$attribute_id])) {
						$s_attributes .= ($i > 1) ? '; ' : '';
						$s_attributes .= $a_attributes[$attribute_id]['name'] . ': ' . $a_attributes[$attribute_id]['text'];

						if ($this->isFollowedVar('attribute index="' . $i . '"', $formulas_array)) {
							$var_values['attribute index="' . $i . '"'] = $a_attributes[$attribute_id]['text'];
						}
					} else {
						if ($this->isFollowedVar('attribute index="' . $i . '"', $formulas_array)) {
							$var_values['attribute index="' . $i . '"'] = ''; // Заменяем переменную на пустоту
						}
					}

					$i++;
				}

				$var_values['attributes'] = $s_attributes;

				// Внимание!
				// Может случиться так, что переменные атрибутов в настройках не будут добавлены, но при этом будут использованы в формуле
				// В таком случае получим [attribute index="1"] с кавычками в формулах, а это привеет к ошибкам в мета-тегах

				$product_info['meta_title'] = $this->excludeNotFollowedAttributesVars($product_info['meta_title'], $i, $attr_i_exist);
				$product_info['meta_description'] = $this->excludeNotFollowedAttributesVars($product_info['meta_description'], $i, $attr_i_exist);
				$product_info['meta_keyword'] = $this->excludeNotFollowedAttributesVars($product_info['meta_keyword'], $i, $attr_i_exist);

				$f_title = $this->excludeNotFollowedAttributesVars($f_title, $i, $attr_i_exist);
				$f_description = $this->excludeNotFollowedAttributesVars($f_description, $i, $attr_i_exist);
				$f_keyword = $this->excludeNotFollowedAttributesVars($f_keyword, $i, $attr_i_exist);
			}
		}

		// Category Level
		if ($this->isFollowedVar('category_level', $formulas_array)) {
			$var_values['category_level'] = $this->model_extension_module_seo_tags_generator->getCategoryLevel($parent_category_id);

			// Levels for people begin with 1 (not with 0)
			if (false !== $var_values['category_level']) {
				$var_values['category_level']++;
			}
		}

		// Category Nested
		if ($this->isFollowedVar('category_nested', $formulas_array)) {
			// has category_nested without indexes
			// Index array begin with 1 (not 0)
			$categories_names = array();

			$categories_names0 = $this->model_extension_module_seo_tags_generator->getCategoriesNestedNames($parent_category_id,	$lang_id);

			foreach ($categories_names0 as $key => $value) {
				$categories_names[$key + 1] = $value['name']; // sort array start with index 1 (not 0)
			}

			// One time is found in all formulas!
			if ($this->isFollowedVar('category_nested SORT_FROM_PARENT_TO_CHILD', $formulas_array)) {
				$categories_names_reverse = array();

				foreach (array_reverse($categories_names) as $key => $value) {
					$categories_names_reverse[$key + 1] = $value; // sort array start with index 1 (not 0)
				}

				$var_values['category_nested SORT_FROM_PARENT_TO_CHILD'] = $this->stg->getCategoryNestedSortedValue($categories_names_reverse);

				if ($this->isFollowedVar('category_nested SORT_FROM_PARENT_TO_CHILD exclude', $formulas_array)) {
					$var_values = array_merge($var_values, $this->excludeCategories($formulas_array, $categories_names_reverse, 'SORT_FROM_PARENT_TO_CHILD'));
				}
			}

			// One time is found in all formulas!
			if ($this->isFollowedVar('category_nested SORT_FROM_CHILD_TO_PARENT', $formulas_array)) {
				$var_values['category_nested SORT_FROM_CHILD_TO_PARENT'] = $this->stg->getCategoryNestedSortedValue($categories_names);

				if ($this->isFollowedVar('category_nested SORT_FROM_CHILD_TO_PARENT exclude', $formulas_array)) {
					$var_values = array_merge($var_values, $this->excludeCategories($formulas_array, $categories_names, 'SORT_FROM_CHILD_TO_PARENT'));
				}
			}

			// One time is found in all formulas!
			if ($this->isFollowedVar('category_nested', $formulas_array)) {
				$var_values['category_nested'] = $this->stg->getCategoryNestedSortedValue($categories_names);

				if ($this->isFollowedVar('category_nested exclude', $formulas_array)) {
					$var_values = array_merge($var_values, $this->excludeCategories($formulas_array, $categories_names));
				}
			}
		}

		if ($this->isFollowedVar('category_nested sort', $formulas_array)) {
			// has category_nested with indexes

			$category_indexes = $this->stg->findCategoryNestedIndexes($formulas_array);

			$categories_keys = $this->stg->getCategoriesKeysForVars($category_indexes);

			//$this->stg->getCategoriesLevels($category_indexes);

			foreach ($category_indexes as $item) {
				$var_values[$item['key']] = $this->stg->getCategoryNestedSortedValue($categories_names, $item['sort']);
			}
		}

		// A! [original_text] must be last!
		if ($this->isFollowedVar('original_text', $formulas_array)) {
			$var_values['original_text'] = $this->stg->parse(html_entity_decode(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $var_values);
		}
		
		// Борьба с багом со скобками при использовании функций
		if (false !== strpos($var_values['product_name'], '(')) {
			$var_values['product_name'] = str_replace(array('(', ')'), array('left_bracket', 'right_bracket'), $var_values['product_name']);
		}
		
		if (false !== strpos($var_values['model'], '(')) {
			$var_values['model'] = str_replace(array('(', ')'), array('left_bracket', 'right_bracket'), $var_values['model']);
		}
		
		if (false !== strpos($var_values['sku'], '(')) {
			$var_values['sku'] = str_replace(array('(', ')'), array('left_bracket', 'right_bracket'), $var_values['sku']);
		}

		// Генерация мета-тегов в зависимости от настроек модуля
		$generate_mode = $this->config->get('seo_tags_generator_generate_mode_product');

		if ('nofollow' == $generate_mode) {
			// only vars replace, but no follow formulas
			// h1 is separated
			$product_info['meta_title'] = $this->cleanup($this->stg->parse($product_info['meta_title'], $var_values));
			$product_info['meta_description'] = $this->cleanup($this->stg->parse($product_info['meta_description'], $var_values));
			$product_info['meta_keyword'] = $this->cleanup($this->stg->parse($product_info['meta_keyword'], $var_values));
		}

		if ('empty' == $generate_mode) {
			if (empty($product_info['meta_title'])) {
				$product_info['meta_title'] = $this->cleanup($this->stg->parse($f_title, $var_values));
			} else {
				$product_info['meta_title'] = $this->cleanup($this->stg->parse($product_info['meta_title'], $var_values));
			}

			if (empty($product_info['meta_description'])) {
				$product_info['meta_description'] = $this->cleanup($this->stg->parse($f_description, $var_values));
			} else {
				$product_info['meta_description'] = $this->cleanup($this->stg->parse($product_info['meta_description'], $var_values));
			}

			if (empty($product_info['meta_keyword'])) {
				$product_info['meta_keyword'] = $this->cleanup($this->stg->parse($f_keyword, $var_values));
			} else {
				$product_info['meta_keyword'] = $this->cleanup($this->stg->parse($product_info['meta_keyword'], $var_values));
			}
		}

		if ('forced' == $generate_mode) {
			$product_info['meta_title']				 = $this->cleanup($this->stg->parse($f_title, $var_values));
			$product_info['meta_description']	 = $this->cleanup($this->stg->parse($f_description, $var_values));
			$product_info['meta_keyword']			 = $this->cleanup($this->stg->parse($f_keyword, $var_values));
		}

		// Проверяем, не генерится ли H1 по формуле?
		$generate_mode_product_h1 = $this->config->get('seo_tags_generator_generate_mode_product_h1');

		// Заголовок в OpenCart Initial отсутствует, а name (из которого он берется) обязателен
		if ('nofollow' == $generate_mode_product_h1) {
			// only vars replace, but no follow formulas
			if ($h1) {
				$product_info[$h1] = $this->escapeBugParentheses($this->stg->parse($product_info[$h1], $var_values));
			} else {
				$product_info['name'] = $this->escapeBugParentheses($this->stg->parse($product_info['name'], $var_values)); // A! OpenCart Initial - for catalog ONLY
			}
		}

		if ('empty' == $generate_mode_product_h1) {
			if ($h1) {
				if (empty($product_info[$h1])) {
					$product_info[$h1] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
				} else {
					$product_info[$h1] = $this->escapeBugParentheses($this->stg->parse($product_info[$h1], $var_values));
				}
			} else {
				if (empty($product_info['name'])) {
					$product_info['name'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
				} else {
					$product_info['name'] = $this->escapeBugParentheses($this->stg->parse($product_info['name'], $var_values)); // A! OpenCart Initial - for catalog ONLY
				}
			}
		}

		if ('forced' == $generate_mode_product_h1) {
			if ($h1) {
				$product_info[$h1] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
			} else {
				$product_info['name'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values)); // A! OpenCart Initial - for catalog ONLY
			}
		}

		# Description - Text
		#
		// Description - is separated
		// for decode double htmlentities (1 in js in text editor + 1 on save process in DB)
		// $str3 = '&lt;[special]&gt;';
		// print_r(htmlentities($str3, ENT_QUOTES, 'UTF-8'));
		// &amp;lt;[special]&amp;gt;
		$product_text_tmp = html_entity_decode(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

		$generate_mode_product_text = $this->config->get('seo_tags_generator_generate_mode_product_text');

		if ('nofollow' == $generate_mode_product_text) {
			// only vars replace, but no follow formulas
			$product_info['description'] = $this->escapeBugParentheses($this->stg->parse($product_text_tmp, $var_values));
		}

		if ('empty' == $generate_mode_product_text) {
			$tmp_descr = trim(str_replace('&nbsp;', '', strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))));
			if (empty($tmp_descr)) {
				$product_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
			} else {
				$product_info['description'] = $this->escapeBugParentheses($this->stg->parse($product_text_tmp, $var_values));
			}
		}

		if ('forced' == $generate_mode_product_text) {
			$product_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
		}

		return $product_info;
	}


	public function getManufacturerTags($manufacturer_info) {
		$lang_id = $this->config->get('config_language_id');

		foreach ($manufacturer_info as $key => $value) {
			$manufacturer_info[$key] = is_string($value) ? trim($value) : $value;
		}

		if (!$this->config->get('seo_tags_generator_status')) {
			return $manufacturer_info;
		}

		$this->load->model('extension/module/seo_tags_generator');

		$f_title = $this->config->get('seo_tags_generator_manufacturer_title');
		$f_title = html_entity_decode($f_title[$lang_id], ENT_QUOTES, 'UTF-8');

		$f_description = $this->config->get('seo_tags_generator_manufacturer_description');
		$f_description = html_entity_decode($f_description[$lang_id], ENT_QUOTES, 'UTF-8');

		$f_keyword = $this->config->get('seo_tags_generator_manufacturer_keyword');
		$f_keyword = html_entity_decode($f_keyword[$lang_id], ENT_QUOTES, 'UTF-8');

		$f_h1	 = $this->config->get('seo_tags_generator_manufacturer_h1');
		$f_h1	 = html_entity_decode($f_h1[$lang_id], ENT_QUOTES, 'UTF-8');

		$f_text = $this->config->get('seo_tags_generator_manufacturer_text');
		$f_text = html_entity_decode($f_text[$lang_id], ENT_QUOTES, 'UTF-8');

		// Чисто для isFollowedVar()
		$formulas_array = array(
			'title' => $f_title,
			'description' => $f_description,
			'keyword' => $f_keyword,
			'h1' => $f_h1,
			'text' => $f_text,
		);

		if (isset($manufacturer_info['meta_title'])) {
			$formulas_array['mi_meta_title'] = html_entity_decode($manufacturer_info['meta_title'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($manufacturer_info['meta_description'])) {
			$formulas_array['mi_meta_description'] = html_entity_decode($manufacturer_info['meta_description'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($manufacturer_info['meta_keyword'])) {
			$formulas_array['mi_meta_keyword'] = html_entity_decode($manufacturer_info['meta_keyword'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($manufacturer_info['meta_h1'])) {
			$formulas_array['mi_h1'] = html_entity_decode($manufacturer_info['meta_h1'], ENT_QUOTES, 'UTF-8');
		}

		if (isset($manufacturer_info['description'])) {
			$formulas_array['mi_description'] = html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8');
		}


		### Подготовка данных

		// Данные из $manufacturer_info по умолчанию, которые будут участвовать в заменах с помощью функций!
		$var_values = array(
			'manufacturer_name' => $manufacturer_info['name'],
		);

		if (isset($manufacturer_info['meta_h1'])) {
			$var_values['static_manufacturer_h1'] = $manufacturer_info['meta_h1'];
		} elseif (isset($manufacturer_info['h1'])) {
			$var_values['static_manufacturer_h1'] = $manufacturer_info['h1'];
		}

		if ($this->isFollowedVar('page_number', $formulas_array)) {
			$var_values['page_number'] = isset($this->request->get['page']) && $this->request->get['page'] ? $this->request->get['page'] : false;
		}

		if ($this->isFollowedVar('manufacturer_synonym', $formulas_array)) {
			$var_values['manufacturer_synonym'] = isset($manufacturer_info['name_synonym']) ? $manufacturer_info['name_synonym'] : '';
		}

		if ($this->isFollowedVar('shop_name', $formulas_array)) {
			$var_values['shop_name'] = $this->config->get('config_name');
		}

		if ($this->isFollowedVar('config_telephone', $formulas_array)) {
			$var_values['config_telephone'] = $this->config->get('config_telephone');
		}

		if ($this->isFollowedVar('city', $formulas_array)) {
			$config_store	= $this->config->get('config_store');
			$config_store	= $config_store[$lang_id];

			//$followed_variables[] = 'city'; // ... multiple

			$var_values['city'] = $config_store['city'];
			$var_values['city_genitive'] = $config_store['city_genitive'];
			$var_values['city_dative'] = $config_store['city_dative'];
			$var_values['city_prepositional'] = $config_store['city_prepositional'];
		}

		// A! [original_text] must be last!
		if ($this->isFollowedVar('original_text', $formulas_array)) {
			if (array_key_exists('description', $manufacturer_info)) {
				$var_values['original_text'] = $this->stg->parse(html_entity_decode(html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $var_values);
			} else {
				$var_values['original_text'] = '';
			}
		}

		// for no errors on OpenCart pure
		if (!isset($manufacturer_info['meta_title'])) {
			$manufacturer_info['meta_title'] = $manufacturer_info['name'];
		}

		if (!isset($manufacturer_info['meta_description'])) {
			$manufacturer_info['meta_description'] = '';
		}

		if (!isset($manufacturer_info['meta_keyword'])) {
			$manufacturer_info['meta_keyword'] = '';
		}
		
		// Борьба с багом со скобками при использовании функций
		if (false !== strpos($var_values['manufacturer_name'], '(')) {
			$var_values['manufacturer_name'] = str_replace(array('(', ')'), array('left_bracket', 'right_bracket'), $var_values['manufacturer_name']);
		}

		// Генерация мета-тегов в зависимости от настроек модуля
		$generate_mode = $this->config->get('seo_tags_generator_generate_mode_manufacturer');

		if ('nofollow' == $generate_mode) {
			// only vars replace, but no follow formulas
			$manufacturer_info['meta_title'] = $this->cleanup($this->stg->parse($manufacturer_info['meta_title'], $var_values));
			$manufacturer_info['meta_description'] = $this->cleanup($this->stg->parse($manufacturer_info['meta_description'], $var_values));
			$manufacturer_info['meta_keyword'] = $this->cleanup($this->stg->parse($manufacturer_info['meta_keyword'], $var_values));
		}

		if ('empty' == $generate_mode) {
			if (empty($manufacturer_info['meta_title'])) {
				$manufacturer_info['meta_title'] = $this->cleanup($this->stg->parse($f_title, $var_values));
			} else {
				$manufacturer_info['meta_title'] = $this->cleanup($this->stg->parse($manufacturer_info['meta_title'], $var_values));
			}

			if (empty($manufacturer_info['meta_description'])) {
				$manufacturer_info['meta_description'] = $this->cleanup($this->stg->parse($f_description, $var_values));
			} else {
				$manufacturer_info['meta_description'] = $this->cleanup($this->stg->parse($manufacturer_info['meta_description'], $var_values));
			}

			if (empty($manufacturer_info['meta_keyword'])) {
				$manufacturer_info['meta_keyword'] = $this->cleanup($this->stg->parse($f_keyword, $var_values));
			} else {
				$manufacturer_info['meta_keyword'] = $this->cleanup($this->stg->parse($manufacturer_info['meta_keyword'], $var_values));
			}
		}

		if ('forced' == $generate_mode) {
			$manufacturer_info['meta_title']			 = $this->cleanup($this->stg->parse($f_title, $var_values));
			$manufacturer_info['meta_description'] = $this->cleanup($this->stg->parse($f_description, $var_values));
			$manufacturer_info['meta_keyword']		 = $this->cleanup($this->stg->parse($f_keyword, $var_values));
		}

		// Проверяем, не генерится ли H1 по формуле?
		$generate_mode_manufacturer_h1 = $this->config->get('seo_tags_generator_generate_mode_manufacturer_h1');

		// Заголовок в OpenCart Initial отсутствует, а name (из которого он берется) обязателен
		if ('nofollow' == $generate_mode_manufacturer_h1) {
			// only vars replace, but no follow formulas
			if (isset($manufacturer_info['meta_h1']) && $manufacturer_info['meta_h1']) {
				$manufacturer_info['meta_h1'] = $this->escapeBugParentheses($this->stg->parse($manufacturer_info['meta_h1'], $var_values));
			} else {
				$manufacturer_info['name'] = $this->escapeBugParentheses($this->stg->parse($manufacturer_info['name'], $var_values)); // A! OpenCart Initial - for catalog ONLY
			}
		}

		if ('empty' == $generate_mode_manufacturer_h1) {
			if (isset($manufacturer_info['meta_h1'])) {
				if (empty($manufacturer_info['meta_h1'])) {
					$manufacturer_info['meta_h1'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
				} else {
					$manufacturer_info['meta_h1'] = $this->escapeBugParentheses($this->stg->parse($manufacturer_info['meta_h1'], $var_values));
				}
			} else {
				if (empty($manufacturer_info['name'])) {
					$manufacturer_info['name'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
				} else {
					$manufacturer_info['name'] = $this->escapeBugParentheses($this->stg->parse($manufacturer_info['name'], $var_values)); // A! OpenCart Initial - for catalog ONLY
				}
			}
		}

		if ('forced' == $generate_mode_manufacturer_h1) {
			if (isset($manufacturer_info['meta_h1'])) {
				$manufacturer_info['meta_h1'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values));
			} else {
				$manufacturer_info['name'] = $this->escapeBugParentheses($this->stg->parse($f_h1, $var_values)); // A! OpenCart Initial - for catalog ONLY
			}
		}

		# Description - Text
		#
		// Description - is separated
		// for decode double htmlentities (1 in js in text editor + 1 on save process in DB)

		// In OpenCart pure manufacturer don't hase description
		if (isset($manufacturer_info['description'])) {
			$manufacturer_text_tmp = html_entity_decode(html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
		} else {
			$manufacturer_text_tmp = false;
		}

		$generate_mode_manufacturer_text = $this->config->get('seo_tags_generator_generate_mode_manufacturer_text');

		if ('nofollow' == $generate_mode_manufacturer_text) {
			// only vars replace, but no follow formulas
			if ($manufacturer_text_tmp) {
				$manufacturer_info['description'] = $this->escapeBugParentheses($this->stg->parse($manufacturer_text_tmp, $var_values));
			}
		}

		if ('empty' == $generate_mode_manufacturer_text) {
			if ($manufacturer_text_tmp) {
				$tmp_descr = trim(str_replace('&nbsp;', '', strip_tags(html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8'))));
				if (empty($tmp_descr)) {
					$manufacturer_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
				} else {
					$manufacturer_info['description'] = $this->escapeBugParentheses($this->stg->parse($manufacturer_text_tmp, $var_values));
				}
			} else {
				$manufacturer_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
			}
		}

		if ('forced' == $generate_mode_manufacturer_text) {
			$manufacturer_info['description'] = $this->escapeBugParentheses($this->stg->parse($f_text, $var_values));
		}

		return $manufacturer_info;
	}


	/*
	 * Check if is followed var
	 */

	private function isFollowedVar($var_key, $array) {
		// !A - [city is multiple vars: [city], [city_genitive]
		// => "[$var_key"

		foreach ($array as $key => $value) {
			if (false !== strpos($array[$key], "[$var_key")) {
				return true;
			}
		}

		return false;
	}


	/*
	 * Follow cleanup only for meta tags!!
	 * Replace " - to &quot;
	 * A! No follow htmlentities($str, ENT_QUOTES, "UTF-8");
	 * Data inserted from admin is processed with htmlentities
	 */

	private function cleanup($string) {
		$string = strip_tags($string); // от Лайтшоп
		$string = $this->escapeBugParentheses($string);
		
		return $string;
	}
	
	private function escapeBugParentheses($string) {
		$string = trim(preg_replace(array('/\s+/', '/\s\./', '/\"/'), array(' ', '.', '&quot;') , $string)); // Убрать двойные пробелы - некоторые криво вписывают названия товаров и формулы
		$string = str_replace(array('left_bracket', 'right_bracket'), array('(', ')') , $string); // Борьба с багом со скобками при использовании функций
		return $string;
	}


	/*
	 * Remove not followed attributes vars from meta-tags
	 */

	private function excludeNotFollowedAttributesVars($value, $i, $attr_i_exist) {
		$n = 1;

		while ($n <= $i) {
			if (!in_array($n, $attr_i_exist)) {
				$value = str_replace('[attribute index="' . $n . '"]', '', $value);
			}

			$n++;
		}

		return $value;
	}


	/*
	 * Exclude Categories
	 */

	private function excludeCategories($formulas_array, $catgories_names, $flag = false) {
		$result = array();

		$category_keys_exist = array();

		$category_nested_followed = array();

		$string = implode($formulas_array);

		// if no flag no 2 spaces!
		$s_find = '\[category_nested ';
		$s_find .= $flag ? $flag . ' ' : '';
		$s_find .= 'exclude="(.*?)"\s*\]';

		preg_match_all('|' . $s_find . '|s', $string, $matches_foo, PREG_SET_ORDER);

		if (count($matches_foo) > 0) {
			foreach ($matches_foo as $key => $item) {
				if (!in_array($item[0], $category_keys_exist)) {
					$category_keys_exist[] = $item[0];
					$category_nested_followed[$key]['key'] = $categories_keys[$key] = str_replace(array('[', ']'), array('', ''), $item[0]);
					$category_nested_followed[$key]['exclude'] = $item[1];
				}
			}
		}

		foreach ($category_nested_followed as $item) {
			$a_exclude = explode(',', trim($item['exclude']));

			foreach ($a_exclude as $key => $value) {
				$value = trim($value);
				if (!empty($value)) {
					$a_exclude[$key] = trim($value);
				} else {
					unset($a_exclude[$key]);
				}
			}

			$catgories_names1 = $catgories_names;

			foreach ($a_exclude as $a_exclude_value) {
				unset($catgories_names1[$a_exclude_value]);
			}

			$out1 = '';

			$i = 0;
			foreach ($catgories_names1 as $item1) {
				$out1 .= $i ? ' ' : '';
				$out1 .= $item1;
				$i++;
			}

			$result[$item['key']] = $out1;
		}

		return $result;
	}


    /**
     * @param $description
     * @return string
     * add oc filter value names to meta description
     */
	public function addOCFilterValues($description)
    {
        $catNamePos = strpos($description,'])');
        $posToInsert = $catNamePos+2;
        $dataToInsert = $this->getOcFilterAliases();

        $descriptionFirst = substr($description,0,$posToInsert);
        $descriptionSecond = substr($description,$posToInsert);
        $description = $descriptionFirst . $dataToInsert . $descriptionSecond;
        return $description;
    }


    /**
     * @return string
     * make additional description for meta description  by oc filter values seo aliases or names
     */
    private function getOcFilterAliases()
    {
        $aliases=' ';

        if (isset($this->request->get['filter_ocfilter'])) {
            $filter_ocfilter = $this->request->get['filter_ocfilter'];

        }
        $ocfilter = new OCFilter($this->registry);

        $options = $ocfilter->getSelectedOptions();

        foreach ($options as $option){
            foreach ($option['values'] as $value){
                $valueName = $value['seo_name']? mb_strtolower($value['seo_name']): mb_strtolower($value['name']);
                $aliases .= $valueName .', ';
            }

        }


        return $aliases;
    }

}
