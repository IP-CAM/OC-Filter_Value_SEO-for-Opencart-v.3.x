<?php

final class OCFilter {
  use Helper;

  private $registry;

  private $path = '';
  private $category_id = 0;
  private $params = '';
  private $counters = array();
  private $options = null;
  private $options_get = array();
  private $min_price = 0;
  private $max_price = 0;
  private $min_price_get = 0;
  private $max_price_get = 0;
  private $filter_title = '';

  private $page_info = array();

	public function __construct($registry) {
		$this->registry = $registry;

    $this->load->config('ocfilter');

    $this->load->language('extension/module/ocfilter');

    $this->load->model('extension/module/ocfilter');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');

    // Decode URL
    $this->decode();

    if (!$this->path) {
    	return;
    }

    $parts = explode('_', $this->path);

    $this->category_id = (int)end($parts);

    if (isset($this->request->get['filter_ocfilter'])) {
      $this->params = $this->cleanParamsString($this->request->get['filter_ocfilter']);
    }

    // Get values counter
    $filter_data = array(
			'filter_category_id' => $this->getCategoryId(),
      'filter_ocfilter' => $this->getParams()
		);

		$this->counters = $this->model_extension_module_ocfilter->getCounters($filter_data);

    if ($this->config->get('module_ocfilter_show_price')) {
      $filter_data['filter_ocfilter'] = $this->cancelOptionParams('p');

      $product_prices = $this->model_extension_module_ocfilter->getProductPrices($filter_data);

      if ($product_prices) {
        $this->min_price = $this->currency->format(floor($product_prices['min']), $this->session->data['currency'], '', false);
        $this->max_price = $this->currency->format(ceil($product_prices['max']), $this->session->data['currency'], '', false);
      }
    }

		if ($this->min_price_get && $this->min_price_get < $this->min_price) {
			$this->min_price = $this->min_price_get;
    }

		if ($this->max_price_get && $this->max_price_get > $this->max_price) {
			$this->max_price = $this->max_price_get;
    }

    if ($this->getParams()) {
      $this->options_get = $this->decodeParamsFromString($this->getParams());

      if ($this->config->get('module_ocfilter_show_price') && !empty($this->options_get['p'])) {
        $range = $this->getRangeParts(end($this->options_get['p']));

        if (isset($range['from']) && isset($range['to'])) {
        	$this->min_price_get = $range['from'];
        	$this->max_price_get = $range['to'];
        }
      }

      if (!$this->getPageInfo()) {
       	$this->document->setNoindex(true);
      }

      $this->filter_title = $this->getSelectedsFilterTitle();

    }
	}

	public function __get($name) {
    if ($this->registry->has($name)) {
    	return $this->registry->get($name);
    } else {
      return null;
    }
	}

	public function getCategoryId() {
		return $this->category_id;
	}

	public function getPath() {
		return $this->path;
	}

	public function getParams() {
		return $this->params;
	}

  public function getPageInfo() {
    return $this->page_info;
  }

  public function getMinPrice() {
    return $this->min_price;
  }

  public function getMaxPrice() {
    return $this->max_price;
  }

  public function getMinPriceGet() {
    return $this->min_price_get;
  }

  public function getMaxPriceGet() {
    return $this->max_price_get;
  }

  public function getOptionsGet() {
    return $this->options_get;
  }

	public function getPageMetaTitle($meta_title) {
    $page_info = $this->getPageInfo();


    if ($page_info) {
		  $meta_title = $page_info['meta_title'];
    } else if ($this->filter_title) {

      if (false !== strpos($meta_title, '{filter}')) {
        $meta_title = trim(str_replace('{filter}', $this->filter_title, $meta_title));
      } else {

 // insert filter metadata after category name
          $title = explode(' ',$meta_title);
           $titleData=[];
          foreach ($title as $key=>$item){
              if (htmlentities($item) =='&bull;' ){
                  $titleData[]= mb_strtolower($this->filter_title);
                  $titleData[]= $item;
              }else {
                  $titleData[] =  $item;
              }

          }
          $meta_title = implode(' ',$titleData);
// insert filter metadata after category name

//        $meta_title .= ' ' . $this->filter_title;
      }
    } else {
      $meta_title = trim(str_replace('{filter}', '', $meta_title));
    }

    return $meta_title;
	}

	public function getPageMetaDescription($meta_description) {
    $page_info = $this->getPageInfo();

    if ($page_info) {
		  $meta_description = $page_info['meta_description'];
    } else if ($this->filter_title) {
      if (false !== strpos($meta_description, '{filter}')) {
        $meta_description = trim(str_replace('{filter}', $this->filter_title, $meta_description));
      } else {
      //  $meta_description .= '. ' . $this->filter_title;
      }
    } else {
      $meta_description = trim(str_replace('{filter}', '', $meta_description));
    }

    return $meta_description;
	}

	public function getPageMetaKeywords($meta_keyword) {
    $page_info = $this->getPageInfo();

    if ($page_info) {
		  $meta_keyword = $page_info['meta_keyword'];
    } else if ($this->filter_title) {
      if (false !== strpos($meta_keyword, '{filter}')) {
        $meta_keyword = trim(str_replace('{filter}', $this->filter_title, $meta_keyword));
      } else {
        $meta_keyword .= ' ' . $this->filter_title;
      }
    } else {
      $meta_keyword = trim(str_replace('{filter}', '', $meta_keyword));
    }

    return $meta_keyword;
	}

	public function getPageHeadingTitle($heading_title) {

    $page_info = $this->getPageInfo();

    if ($page_info) {
		  $heading_title = $page_info['title'];
    } else if ($this->filter_title) {
      if (false !== strpos($heading_title, '{filter}')) {
        $heading_title = trim(str_replace('{filter}', $this->filter_title, $heading_title));
      } else {
        $heading_title .= ' ' . $this->filter_title;
      }
    } else {
      $heading_title = trim(str_replace('{filter}', '', $heading_title));
    }

    return $heading_title;
	}

	public function getPageDescription() {
    $page_info = $this->getPageInfo();

    if ($page_info && trim(strip_tags(html_entity_decode($page_info['description'], ENT_QUOTES, 'UTF-8'))) && !isset($this->request->get['page']) && !isset($this->request->get['sort']) && !isset($this->request->get['order']) && !isset($this->request->get['search']) && !isset($this->request->get['limit'])) {
    	$description = html_entity_decode($page_info['description'], ENT_QUOTES, 'UTF-8');
    } else {
      $description = '';
    }

    return $description;
	}

	public function getPageBreadCrumb() {
    $page_info = $this->getPageInfo();

    if ($page_info) {
		  $text = $page_info['title'];
    } else if ($this->filter_title) {
      $text = $this->filter_title;

      if (utf8_strlen($text) > 30) {
      	$text = utf8_substr($text, 0, 30) . '..';
      }
    } else {
      return false;
    }

		return array(
      'text' => $text,
      'href' => $this->link($this->getParams())
    );
	}

	public function getOCFilterOptions() {
    if (!is_null($this->options)) {
    	return $this->options;
    }

    $options = array();

    // Manufacturers filtering
    if ($this->config->get('module_ocfilter_manufacturer')) {
  		$results = $this->model_extension_module_ocfilter->getManufacturersByCategoryId($this->getCategoryId());

      if ($results) {
        $options[] = array(
          'option_id'   => 'm',
          'name'        => $this->language->get('text_manufacturer'),
          'description' => $this->language->get('text_manufacturer_description'),
          'type'        => $this->config->get('module_ocfilter_manufacturer_type'),
          'values'      => $results
        );
      }
    }

    // Stock status filtering
    if ($this->config->get('module_ocfilter_stock_status')) {
			if ($this->config->get('module_ocfilter_stock_status_method') == 'stock_status_id') {
				$results = $this->model_extension_module_ocfilter->getStockStatuses();

	      $options['stock'] = array(
	        'option_id'   => 's',
	        'name'        => $this->language->get('text_stock'),
          'description' => $this->language->get('text_stock_description'),
	        'type'        => $this->config->get('module_ocfilter_stock_status_type'),
	        'values'      => $results
	      );
			} else if ($this->config->get('module_ocfilter_stock_status_method') == 'quantity') {
	      $options['stock'] = array(
	        'option_id'   => 's',
	        'name'        => $this->language->get('text_stock'),
          'description' => $this->language->get('text_stock_description'),
	        'type'        => ($this->config->get('module_ocfilter_stock_out_value') ? 'radio' : 'checkbox'),
	        'values'      => array(
						array(
							'value_id'    => 'in',
							'name'        => 'В наличии'
						)
					)
	      );

				if ($this->config->get('module_ocfilter_stock_out_value')) {
          $options['stock']['values'][] = array(
						'value_id'    => 'out',
						'name'        => $this->language->get('text_out_of_stock')
					);
				}
			}
    }

    // Get category options
	  $results = $this->model_extension_module_ocfilter->getOCFilterOptionsByCategoryId($this->getCategoryId());

    if ($results) {
	 		$options = array_merge($options, $results);
		}

    $options_data = array();

    $index = 0;

	  foreach ($options as $key => $option) {
      if ($option['type'] == 'select') {
        $option['type'] = 'radio';
        $option['selectbox'] = true;
      }

      $this_option = isset($this->options_get[$option['option_id']]);

			$values = array();

      if ($option['type'] != 'slide' && $option['type'] != 'slide_dual') {
        foreach ($option['values'] as $value) {
					$this_value = isset($this->options_get[$option['option_id']]) && in_array($value['value_id'], $this->options_get[$option['option_id']]);

          $count = 0;

					if (isset($this->counters[$option['option_id'] . $value['value_id']])) {
						if ($this_option && $option['type'] == 'checkbox') {
							$count = '+' . $this->counters[$option['option_id'] . $value['value_id']];
						} else {
							$count = $this->counters[$option['option_id'] . $value['value_id']];
						}
					}

          if ($count || !$this->config->get('module_ocfilter_hide_empty_values')) {
						if (isset($option['image']) && $option['image'] && isset($value['image']) && $value['image'] && file_exists(DIR_IMAGE . $value['image'])) {
              $image = $this->model_tool_image->resize($value['image'], 19, 19);
						} else {
							$image = false;
						}

            $params = $this->getValueParams($option['option_id'], $value['value_id'], $option['type']);

			if (isset($value['name_seo'])){
			  $seoName = html_entity_decode($value['name_seo'], ENT_QUOTES, 'UTF-8');
            } else {
			    $seoName = '';
            }

/*
* /hide some values
*/
			$isAttribute = false;
            if (isset($value['is_attribute'])&& $value['is_attribute'] == '1'){
                $isAttribute = true;
            }

	          $values[] = array(
	            'value_id' => $value['value_id'],
							'id'       => $option['option_id'] . $value['value_id'],
	            'name'     => html_entity_decode($value['name'] . (isset($option['postfix']) ? $option['postfix'] : ''), ENT_QUOTES, 'UTF-8'),
	            'name_seo' => $seoName,
              'keyword'  => html_entity_decode((isset($value['keyword']) ? $value['keyword'] : $value['value_id']), ENT_QUOTES, 'UTF-8'),
							'color'    => ((isset($value['color']) && $value['color']) ? $value['color'] : '#FFFFFF'),
              'image'    => $image,
	            'params'   => $params,
							'count'    => $count,
	            'selected' => $this_value,
                  'is_attribute'=>$isAttribute
	          );
					}
        }

        if (!$values) {
        	continue;
        }
      } else {
        $range = $this->model_extension_module_ocfilter->getSliderRange($option['option_id'], array(
    			'filter_category_id' => $this->getCategoryId(),
          'filter_ocfilter' => $this->cancelOptionParams($option['option_id']),
        ));

        if ($range['min'] == $range['max']) {
        	continue;
        }

        $option['slide_value_min'] = $range['min'];
        $option['slide_value_max'] = $range['max'];
      }

      if ($option['type'] == 'radio') {
        $params = $this->cancelOptionParams($option['option_id']);

				if (isset($this->counters[$option['option_id'] . 'all'])) {
					$count = $this->counters[$option['option_id'] . 'all'];
				} else {
					$count = 1;
				}

        array_unshift($values, array(
          'value_id' => $option['option_id'],
					'id'       => 'cancel-' . $option['option_id'],
          'name'     => $this->language->get('text_any'),
          'params'   => $params,
					'count'    => $count,
          'selected' => !$this_option
        ));
			}

      $option_data = array(
        'option_id'           => $option['option_id'],
        'index'               => ++$index,
       	'name'                => html_entity_decode($option['name'], ENT_QUOTES, 'UTF-8'),
        'selectbox'           => (isset($option['selectbox']) ? $option['selectbox'] : false),
        'color'			          => (isset($option['color']) ? $option['color'] : false),
        'image'		            => (isset($option['image']) ? $option['image'] : false),
        'keyword'		          => (isset($option['keyword']) ? $option['keyword'] : $option['option_id']),
				'postfix' 		        => (isset($option['postfix']) ? html_entity_decode($option['postfix'], ENT_QUOTES, 'UTF-8') : ''),
        'description'         => (isset($option['description']) ? $option['description'] : ''),
        'slide_value_min'     => (isset($option['slide_value_min']) ? $option['slide_value_min'] : 0),
        'slide_value_max'     => (isset($option['slide_value_max']) ? $option['slide_value_max'] : 0),
        'slide_value_min_get' => (isset($option['slide_value_min']) ? $option['slide_value_min'] : 0),
        'slide_value_max_get' => (isset($option['slide_value_max']) ? $option['slide_value_max'] : 0),
        'type'                => $option['type'],
        'selected'            => $this_option,
        'values'              => $values
      );

      if (($option['type'] == 'slide' || $option['type'] == 'slide_dual') && isset($this->options_get[$option['option_id']][0])) {
        $range = $this->getRangeParts($this->options_get[$option['option_id']][0]);

        if (isset($range['from']) && isset($range['to'])) {
          $option_data['slide_value_min_get'] = $range['from'];
          $option_data['slide_value_max_get'] = $range['to'];

          // For getSelectedOptions
          array_unshift($option_data['values'], array(
            'value_id' => $range['from'] . '-' . $range['to'],
            'name'     => 'от ' . $range['from'] . ' до ' . $range['to'] . $option['postfix']
          ));
        }
      }

      $options_data[] = $option_data;
    } // End options each

    $this->options = $options_data;

    return $options_data;
  }

	public function getValueParams($option_id, $value_id, $type = 'checkbox') {
		$decoded_params = $this->decodeParamsFromString($this->getParams());

		if ($type == 'checkbox') {
			if (isset($decoded_params[$option_id])) {
				if (false !== $key = array_search($value_id, $decoded_params[$option_id])) {
					unset($decoded_params[$option_id][$key]);
				} else {
					$decoded_params[$option_id][] = $value_id;
				}
			} else {
				$decoded_params[$option_id] = array($value_id);
			}
 		} else if ($type == 'select' || $type == 'radio') {
			if (isset($decoded_params[$option_id])) {
				unset($decoded_params[$option_id]);
			}

			$decoded_params[$option_id] = array($value_id);
		}

		return $this->encodeParamsToString($decoded_params);
	}

  public function cancelOptionParams($option_id) {
    if ($this->getParams()) {
			$params = $this->decodeParamsFromString($this->getParams());

			if (isset($params[$option_id])) {
				unset($params[$option_id]);
			}

			return $this->encodeParamsToString($params);
    }
  }

  public function getSelectedOptions() {
    $selected_options = array();

    $category_options = $this->getOCFilterOptions();

    if ($this->min_price_get && $this->max_price_get) {
      $category_options[] = array(
        'option_id' => 'p',
        'name'      => $this->language->get('text_price'),
				'type'      => 'select',
        'selected'  => isset($this->options_get['p']),
        'values'    => array(array(
					'value_id' 	=> $this->min_price_get . '-' . $this->max_price_get,
          'name' 			=> 'от ' . $this->currency->getSymbolLeft($this->session->data['currency']) . $this->min_price_get . ' до ' . $this->max_price_get . $this->currency->getSymbolRight($this->session->data['currency'])
				))
      );
    }

		foreach ($category_options as $option) {
			if (!$option['selected']) {
				continue;
			}

      $option_id = $option['option_id'];

			$values = array();

			foreach ($option['values'] as $value) {
        if (!in_array($value['value_id'], $this->options_get[$option_id])) {
          continue;
				}

			  $params = '';

        if (count($this->options_get) > 1 || count($this->options_get[$option_id]) > 1) {
          if ($option['type'] == 'radio' || $option['type'] == 'select' || $option['type'] == 'slide' || $option['type'] == 'slide_dual') {
            $params .= $this->cancelOptionParams($option_id);
          } else {
            $params .= $value['params'];
          }
        }

        $name = html_entity_decode($value['name'], ENT_QUOTES, 'UTF-8');
        if (isset($value['name_seo'])){
            $seoName = html_entity_decode($value['name_seo'], ENT_QUOTES, 'UTF-8');
        }else{
            $seoName ='';
        }



			  $values[] = array(
          'name' => $name,
          'seo_name'=>$seoName,
          'id'   => $option_id . $value['value_id'],
          'href' => $this->link($params),
        );
			}

			$selected_options[$option_id] = array(
        'name'   		=> $option['name'],
        'values' 		=> $values
      );
		}

    return $selected_options;
  }

  public function getSelectedsFilterTitle() {
    $filter_title = '';

    $selecteds = $this->getSelectedOptions();

    foreach ($selecteds as $option_id => $option) {
      if ($filter_title) {
        $filter_title .= ', ';
      }

      if ($option_id == 'm') {
        $values_name  = '';

        foreach ($option['values'] as $value) {
          if ($values_name) {
          	$values_name .= ', ';
          }

      	  $values_name .= $value['name'];
        }

        if ($values_name) {
        	$filter_title .= $values_name;
        }
      } else if ($option_id == 'p') {
        $price = array_shift($option['values']);

        $filter_title .= $price['name'];
      } else if ($option_id == 's') {
        if ($this->config->get('module_ocfilter_stock_status_method') == 'quantity') {
          $stock_status = array_shift($option['values']);

          if ($stock_status['name'] == 'in') {
            $filter_title .= 'в наличии';
          } else if ($stock_status['name'] == 'out') {
            $filter_title .= 'нет в наличии';
          }
        } else {
          $values_name  = '';

          foreach ($option['values'] as $value) {
            if ($values_name) {
            	$values_name .= ', ';
            }

        	  $values_name .= $value['name'];
          }

          if ($values_name) {
          	$filter_title .= $values_name;
          }
        }
      } else {
        $values_name  = '';

        foreach ($option['values'] as $value) {
          if ($values_name) {
          	$values_name .= ', ';
          }

      	  //$values_name .= $value['name'];

      	  $values_name .= $value['seo_name']? $value['seo_name']: $value['name'];
        }

        if ($values_name) {
        	$filter_title .= $values_name;
        	//$filter_title .= $option['name'] . ' ' . $values_name;
        }
      }
    }

    return $filter_title;
  }

  public function link($filter_ocfilter = '') {
    $url = '';

    if ($this->getPath()) {
      $url .= '&path=' . (string)$this->getPath();
    }

    if ($filter_ocfilter) {
      $url .= '&filter_ocfilter=' . (string)$filter_ocfilter;
    }

    if (isset($this->request->get['sort'])) {
      $url .= '&sort=' . (string)$this->request->get['sort'];
    }

    if (isset($this->request->get['order'])) {
      $url .= '&order=' . (string)$this->request->get['order'];
    }

    if (isset($this->request->get['limit'])) {
      $url .= '&limit=' . (int)$this->request->get['limit'];
    }

    return $this->url->link('product/category', $url);
  }

  public function decode() {
    if (isset($this->request->get['path'])) {
      $this->path = $this->request->get['path'];
    }

    if (!isset($this->request->get['_route_'])) {
      return;
    }

    $_route_ = $this->request->get['_route_'];

		$keywords = explode('/', $_route_);

		// remove any empty arrays from trailing
		if (utf8_strlen(end($keywords)) == 0) {
			array_pop($keywords);
		}

    $ignored = array();

    $page_keywords = array();

    // Get category path
    if (!$this->path) {
      $path_info = $this->model_extension_module_ocfilter->decodeCategory($keywords);

      if ($path_info && $path_info->path) {
      	$this->path = $path_info->path;

        $ignored = $path_info->keywords;
      }
    }

    if (!$this->path) {
    	return;
    }

    $parts = explode('_', $this->path);

    $category_id = (int)end($parts);

    // Ignore language
    $key = array_search($this->session->data['language'], $keywords);

    if (false !== $key) {
    	$ignored[] = $keywords[$key];
    }

    // Get SEO Page
    foreach ($keywords as $key => $keyword) {
      if (in_array($keyword, $ignored)) {
      	continue;
      }

      $page_info = $this->model_extension_module_ocfilter->decodePage($category_id, $keyword);

      if ($page_info) {
      	$this->page_info = $page_info;

  			$keywords = explode('/', $this->page_info['params']);

  			// remove any empty arrays from trailing
  			if (utf8_strlen(end($keywords)) == 0) {
  				array_pop($keywords);
  			}

        break;
      }
    }

    $params = array();

    // Special filters
    foreach ($keywords as $key => $keyword) {
      if (in_array($keyword, $ignored)) {
      	continue;
      }

      if ($keyword == 'price') {
        unset($keywords[$key++]);

        $page_keywords[] = $keyword;

        if (isset($keywords[$key]) && $this->isRange($keywords[$key])) {
      	  $params['p'] = array($keywords[$key]);

          $page_keywords[] = $keywords[$key];

          unset($keywords[$key]);
        }
      } else if ($keyword == 'sklad' && $this->config->get('module_ocfilter_stock_status_method') == 'quantity') {
        unset($keywords[$key++]);

        $page_keywords[] = $keyword;

        if (isset($keywords[$key]) && ($keywords[$key] == 'in' || $keywords[$key] == 'out')) {
          if (!isset($params['s'])) {
            $params['s'] = array();
          }

          $params['s'][$keywords[$key]] = $keywords[$key];

          $page_keywords[] = $keywords[$key];

          unset($keywords[$key]);
        }
      }
    }

    $current = '';

    foreach ($keywords as $key => $keyword) {
      if (in_array($keyword, $ignored)) {
      	continue;
      }

      $founded = 0;

      // Values
      if ($current == 's' && $this->isID($keyword) && $this->config->get('module_ocfilter_stock_status_method') == 'stock_status_id') {
        $params['s'][$keyword] = $keyword;

        $founded = 1;
      } else if ($current) {
        $value_id = $this->model_extension_module_ocfilter->decodeValue($keyword, $current);

        if ($value_id) {
          $params[$current][$value_id] = $value_id;

          $founded = 1;
        } else if ($this->isRange($keyword)) { // If Slider
          $params[$current][$keyword] = $keyword;

          $founded = 2;
        }
      }

      if ($founded > 0) {
        $page_keywords[] = $keyword;

        if ($founded > 1) {
        	$current = '';
        }

      	unset($keywords[$key]);

        continue;
      }

      // Options
      if ($keyword == 'sklad' && $this->config->get('module_ocfilter_stock_status_method') == 'stock_status_id') {
      	$params['s'] = array();

        $current = 's';

        $page_keywords[] = $keyword;

        unset($keywords[$key]);
      } else if (!$this->isRange($keyword)) {
        $option_id = $this->model_extension_module_ocfilter->decodeOption($keyword, $category_id);

        if ($option_id) {
          $params[$option_id] = array();

          $current = $option_id;

          $page_keywords[] = $keyword;

          unset($keywords[$key]);
        }
      }
    }

    // Manufacturer
    foreach ($keywords as $key => $keyword) {
      $manufacturer_id = $this->model_extension_module_ocfilter->decodeManufacturer($keyword);

      if ($manufacturer_id) {
        if (!isset($params['m'])) {
          $params['m'] = array();
        }

       	$params['m'][$manufacturer_id] = $manufacturer_id;

        $page_keywords[] = $keyword;

        unset($keywords[$key]);
      }
    }

    // Add category SEO keywords to _route_
    if ($this->page_info) {
    	$path = $this->model_extension_module_ocfilter->getCategorySeoPathByCategoryId($this->page_info['category_id']);

      if ($path) {
        $parts = explode('/', $path);

        foreach (array_reverse($parts) as $part) {
          array_unshift($keywords, $part);
        }
      }
    }

    if (!$this->page_info && $page_keywords) {
    	$this->page_info = $this->model_extension_module_ocfilter->getPage($category_id, implode('/', $page_keywords));
    }

    if ($keywords) {
    	$this->request->get['_route_'] = implode('/', $keywords);
    }

    if ($params) {
      $this->request->get['filter_ocfilter'] = $this->encodeParamsToString($params);

      if (isset($this->request->get['route'])) {
      	unset($this->request->get['route']);
      } else {
        $this->request->get['route'] = 'product/category';
      }
    }
  }

  public function rewrite($link) {
    $url_info = parse_url(str_replace('&amp;', '&', $link));

    if (!isset($url_info['query'])) {
    	return $link;
    }

		$data = array();

		parse_str($url_info['query'], $data);

    if (!isset($data['filter_ocfilter'])) {
      return $link;
    }

    $params = $this->decodeParamsFromString($data['filter_ocfilter']);

    unset($data['filter_ocfilter']);

    $path = '';

    foreach ($params as $option_id => $values) {
      if ($option_id == 'p') {
      	$path .= '/price';
      } else if ($option_id == 's') {
      	$path .= '/sklad';
      } else if ($option_id != 'm') {
        $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "ocfilter_option WHERE option_id = '" . (int)$option_id . "'");

        if ($query->num_rows && $query->row['keyword']) {
        	$path .= '/' . $query->row['keyword'];
        } else {
        	$path .= '/' . $option_id;
        }
      }

      foreach ($values as $value_id) {
        $query = false;

        if ($option_id == 'm') {
          $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE `query` = 'manufacturer_id=" . (int)$value_id . "'");
        } else if ($this->isID($value_id)) {
          $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "ocfilter_option_value WHERE value_id = '" . $this->db->escape((string)$value_id) . "'");
        }

        if ($query && $query->num_rows && $query->row['keyword']) {
        	$path .= '/' . $query->row['keyword'];
        } else {
        	$path .= '/' . $value_id;
        }
      }
    }

    if ($path) {
      $page_path = ltrim($path, '/');

      $page_info = $this->model_extension_module_ocfilter->getPage($this->category_id, $page_path);

      if ($page_info && $page_info['keyword']) {
      	$path = '/' . $page_info['keyword'];
      }
    }

    $rewrite = $url_info['scheme'] . '://' . $url_info['host'];

    if (isset($url_info['port'])) {
    	$rewrite .= ':' . $url_info['port'];
    }

    if (isset($url_info['path'])) {
    	$rewrite .= str_replace('/index.php', '', $url_info['path']);
    } else {
      $rewrite .= '/index.php';
    }

    if ($path) {
    	$rewrite = rtrim($rewrite, '/') . $path;

      if ($this->config->has('config_seo_url_type') && $this->config->get('config_seo_url_type') == 'seo_pro') {
      	$rewrite .= '/';
      }
    }

		$query = '';

		if ($data) {
			foreach ($data as $key => $value) {
				$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
			}

			if ($query) {
				$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
			}
		}

    $rewrite .= $query;

		return $rewrite;
  }
}

trait Helper {
  public function isRange($string) {
    return preg_match('/^(-|)(\d+\.)?\d+?\-(-|)(\d+\.)?\d+?$/', $string);
  }

  public function getRangeParts($string) {
    preg_match('/^((-|)(\d+\.)?\d+?)\-((-|)(\d+\.)?\d+?)$/', $string, $output);

    if (isset($output[1]) && isset($output[4])) {
    	return array(
        'from' => $output[1],
        'to' => $output[4]
      );
    }
  }

  public function isID($string) {
    return preg_match('/^[0-9]+?$/', $string);
  }

  public function cleanParamsString($params) {
    $matches = array();

    if ($params) {
      foreach (explode($this->config->get('module_ocfilter_part_separator'), (string)$params) as $part) {
        $option = explode($this->config->get('module_ocfilter_option_separator'), $part);

        $values = array();

        if (isset($option[1])) {
          // If slider
          if ($this->isRange($option[1])) {
            $range = $this->getRangeParts($option[1]);

            if (isset($range['from']) && isset($range['to'])) {
              $matches[] = $option[0] . $this->config->get('module_ocfilter_option_separator') . (float)$range['from'] . '-' . (float)$range['to'];
            }
          } elseif ($option[0] == 'm' || ($option[0] == 's' && $this->config->get('module_ocfilter_stock_status_method') == 'stock_status_id')) {
            foreach (explode($this->config->get('module_ocfilter_option_value_separator'), $option[1]) as $value_id) {
              $values[] = (int)$value_id;
            }

            if ($values) {
              $matches[] = $option[0] . $this->config->get('module_ocfilter_option_separator') . implode($this->config->get('module_ocfilter_option_value_separator'), $values);
            }
          } elseif ($option[0] == 's' && $this->config->get('module_ocfilter_stock_status_method') == 'quantity') {
  					if ($option[1] == 'in' || $option[1] == 'out') {
  						$matches[] = 's' . $this->config->get('module_ocfilter_option_separator') . $option[1];
  					}
          } elseif ($this->isID($option[0])) {
            foreach (explode($this->config->get('module_ocfilter_option_value_separator'), $option[1]) as $value_id) {
              $values[] = (string)$value_id;
            }

            if ($values) {
              $matches[] = (int)$option[0] . $this->config->get('module_ocfilter_option_separator') . implode($this->config->get('module_ocfilter_option_value_separator'), $values);
            }
          }
        }
      }
    }

    return implode($this->config->get('module_ocfilter_part_separator'), $matches);
  }

  // From params string to array
  public function decodeParamsFromString($params) {
  	$decode = array();

    if ($params = $this->cleanParamsString($params)) {
      foreach (explode($this->config->get('module_ocfilter_part_separator'), $params) as $part) {
        $option = explode($this->config->get('module_ocfilter_option_separator'), $part);

        $values = explode($this->config->get('module_ocfilter_option_value_separator'), $option[1]);

        sort($values);

        $decode[$option[0]] = $values;
      }
    }

    ksort($decode);

    return $decode;
  }

  // From params array to string
  public function encodeParamsToString($params) {
  	$encode = array();

    if ($params) {
      ksort($params);

      foreach ($params as $option_id => $values) {
        sort($values);

        if ($values) $encode[] = $option_id . $this->config->get('module_ocfilter_option_separator') . implode($this->config->get('module_ocfilter_option_value_separator'), $values);
      }
    }

    return $this->cleanParamsString(implode($this->config->get('module_ocfilter_part_separator'), $encode));
  }

  public function declOfNum($number, $cases) {
    if ($number % 10 == 1 && $number % 100 != 11) {
      $key = 0;
    } elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
      $key = 1;
    } else {
      $key = 2;
    }

    return sprintf($cases[$key], $number);
  }
}