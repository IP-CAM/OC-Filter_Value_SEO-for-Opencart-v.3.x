<?php
class ControllerExtensionModuleCognate extends Controller {
    public function index($product) {

        $this->load->model('extension/module/cognate');

        $data['cognates'] = $this->model_extension_module_cognate->getCognateProducts($product['product_id']);
        $data['params'] = $this->model_extension_module_cognate->getParams($product);

        return $data;

    }
}