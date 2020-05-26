<?php

class ControllerExtensionModuleOCFilter extends Controller {
  use Helper;

	public function index($settings = array()) {
    $this->load->language('extension/module/ocfilter');

    if (!$this->ocfilter->getCategoryId()) {
    	return;
    }

    if ($this->config->get('module_ocfilter_show_price') && $this->ocfilter->getMinPrice() < $this->ocfilter->getMaxPrice() - 1) {
      $data['show_price'] = 1;
    } else {
      $data['show_price'] = 0;
    }

    $data['heading_title'] = $this->language->get('heading_title');

    $data['options']              = $this->ocfilter->getOCFilterOptions();
    $data['options'] = $this->addHrefs($data['options']);
    $data['min_price']            = $this->ocfilter->getMinPrice();
		$data['max_price']            = $this->ocfilter->getMaxPrice();
    $data['min_price_get']        = $this->ocfilter->getMinPriceGet() ? $this->ocfilter->getMinPriceGet() : $this->ocfilter->getMinPrice();
    $data['max_price_get']        = $this->ocfilter->getMaxPriceGet() ? $this->ocfilter->getMaxPriceGet() : $this->ocfilter->getMaxPrice();
    $data['path']                 = $this->ocfilter->getPath();

    $data['link']                 = str_replace('&amp;', '&', $this->ocfilter->link());

    $data['params']               = $this->ocfilter->getParams();

    $data['index']   							= $this->config->get('module_ocfilter_url_index');
    $data['show_counter']         = $this->config->get('module_ocfilter_show_counter');
    $data['search_button']        = $this->config->get('module_ocfilter_search_button');
    $data['show_values_limit']   	= $this->config->get('module_ocfilter_show_values_limit');
    $data['manual_price']         = $this->config->get('module_ocfilter_manual_price');

    $data['text_show_all']        = $this->language->get('text_show_all');
    $data['text_hide']          	= $this->language->get('text_hide');
    $data['button_select']        = $this->language->get('button_select');
    $data['text_load']            = $this->language->get('text_load');
    $data['text_price']           = $this->language->get('text_price');
    $data['text_any']           	= $this->language->get('text_any');
    $data['text_cancel_all']      = $this->language->get('text_cancel_all');

    $data['symbol_left']      		= $this->currency->getSymbolLeft($this->session->data['currency']);
    $data['symbol_right']      		= $this->currency->getSymbolRight($this->session->data['currency']);

    $data['show_options'] = $this->ocfilter->getParams();

    if ($this->config->get('module_ocfilter_show_selected') && $this->ocfilter->getOptionsGet()) {
      $data['selecteds'] = $this->ocfilter->getSelectedOptions();
    } else {
      $data['selecteds'] = array();
    }

		if ($this->config->get('module_ocfilter_show_options_limit') && $this->config->get('module_ocfilter_show_options_limit') < count($data['options'])) {
    	$data['show_options_limit'] = $this->config->get('module_ocfilter_show_options_limit');
		} else {
      $data['show_options_limit'] = false;
		}

    $this->document->addStyle('catalog/view/javascript/ocfilter/nouislider.min.css');
    $this->document->addStyle('catalog/view/theme/default/stylesheet/ocfilter/ocfilter.css');

    $this->document->addScript('catalog/view/javascript/ocfilter/nouislider.min.js');
    $this->document->addScript('catalog/view/javascript/ocfilter/ocfilter.js');

		return $this->load->view('extension/module/ocfilter/module', $data);
	}

  public function callback() {
    if (!$this->ocfilter->getPath()) {
    	return;
    }

    $this->load->language('extension/module/ocfilter');

    $json = array();

    if (isset($this->request->get['option_id'])) {
    	$option_id = $this->request->get['option_id'];
    } else {
    	$option_id = 0;
    }

    $filter_data = array(
			'filter_category_id' => $this->ocfilter->getCategoryId(),
      'filter_ocfilter' => $this->ocfilter->getParams(),
      'limit' => 1,
		);

    if ($this->config->get('module_ocfilter_sub_category')) {
    	$filter_data['filter_sub_category'] = true;
    }

		$total_products = $this->model_catalog_product->getTotalProducts($filter_data);

    $json['total'] = $total_products;
    $json['text_total'] = $this->declOfNum($total_products, array(
                                      $this->language->get('button_show_total_1'),
                                      $this->language->get('button_show_total_2'),
                                      $this->language->get('button_show_total_3')
                                    ));

    $json['values'] = array();
    $json['sliders'] = array();

    if ($this->config->get('module_ocfilter_show_price') && $option_id != 'p') {
      $_filter_data = $filter_data;

      $_filter_data['filter_ocfilter'] = $this->ocfilter->cancelOptionParams('p');

      $product_prices = $this->model_extension_module_ocfilter->getProductPrices($_filter_data);

      if ($product_prices) {
        $json['sliders']['p'] = array(
          'min' => $this->currency->format(floor($product_prices['min']), $this->session->data['currency'], '', false),
          'max' => $this->currency->format(ceil($product_prices['max']), $this->session->data['currency'], '', false),
        );
      }
    }

    $options = $this->ocfilter->getOCFilterOptions();

    $options_get = $this->ocfilter->getOptionsGet();

    foreach ($options as $option) {
      if ($option['type'] == 'slide' || $option['type'] == 'slide_dual') {
        if ($option['option_id'] != $option_id) {
          $json['sliders'][$option['option_id']] = $this->model_extension_module_ocfilter->getSliderRange($option['option_id'], $filter_data);
        }

        continue;
      }

      if ($option['type'] == 'select' || $option['type'] == 'radio') {
        $params = $this->ocfilter->cancelOptionParams($option['option_id']);

        $json['values']['cancel-' . $option['option_id']] = array(
          't' => 1,
          'p' => $params,
					's' => false
        );
			}

      foreach ($option['values'] as $value) {
        $json['values'][$value['id']] = array(
          't' => $value['count'],
          'p' => $value['params'],
					's' => isset($options_get[$option['option_id']][$value['value_id']])
        );
      }
    }

    $json['href'] = str_replace('&amp;', '&', $this->ocfilter->link($this->ocfilter->getParams()));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
  }

  public function addHrefs($options){

	    foreach ($options as &$option){
	        foreach ($option['values'] as &$value){
               $value['href'] = str_replace('&amp;', '&', $this->ocfilter->link($value['params']));
               $value['follow'] =$this->makeNoFollow($value['params'])?'nofollow':'';
            }
        }

       return $options;
  }
    private function makeNoFollow($params)
    {

        $options = explode(';',$params);
        if (count($options)>2) return true;
            foreach ($options as $option){
                if (count(explode(',',$option))>1) return true;
            }


        return false;
    }

    public function noIndexForPage()
    {
	    $params=$this->ocfilter->getParams();

	    return $this->makeNoFollow($params);
    }
}