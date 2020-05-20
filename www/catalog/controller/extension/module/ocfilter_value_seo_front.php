<?php
class ControllerExtensionModuleOcfilterValueSeoFront extends Controller {

	public function index($inputData)
    {
        $this->load->model('extension/module/ocfilter_value_seo_front');

        if ( $inputData['type'] =='category') {

            $ocfilterOptions = $this->ocfilter->getSelectedOptions();
            if (empty($ocfilterOptions)) return $inputData['description'];

            $categoryId= $inputData['category']['category_id'];
            $seoValuesDescription = $this->model_extension_module_ocfilter_value_seo_front->getOcValuesDescriptions($ocfilterOptions,$categoryId);

        }else if ($inputData['type'] =='product') {

            $ocfilterOptions =  $this->model_extension_module_ocfilter_value_seo_front->getProductOcOptions($inputData['product']);

            if (empty($ocfilterOptions)) return $inputData['description'];;

            $categoryId= $inputData['category']['category_id'];

            $seoValuesDescription = $this->model_extension_module_ocfilter_value_seo_front->getOcProductDescriptions($ocfilterOptions, $categoryId);

        }


        $data = [];

        $data['descriptions'][] = $inputData['description'];

        $data['descriptions'] = array_merge($data['descriptions'],$seoValuesDescription);

        $description = $this->load->view('extension/module/ocfilter_value_seo_front', $data);

        return $description;
    }


}