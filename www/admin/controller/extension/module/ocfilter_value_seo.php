<?php
class ControllerExtensionModuleOcfilterValueSeo extends Controller {

	public function index($data)
    {
        $this->load->model('extension/module/ocfilter_value_seo');

        $data['ocfilterOptions'] =$this->model_extension_module_ocfilter_value_seo->getOcfilterOptions();
        $data['ocf_descriptions'] =$this->model_extension_module_ocfilter_value_seo->getOcValuesSeo($data['categoryId']);
        $this->document->addScript('view/javascript/oc_filter_seo_text.js');

        return $this->load->view('extension/module/ocfilter_value_seo', $data);
    }

    public function getOcfilterOptions()
    {
         $this->load->model('extension/module/ocfilter_value_seo');

        $ocfilterOptions =$this->model_extension_module_ocfilter_value_seo->getOcfilterOptions();

        $result = json_encode($ocfilterOptions);

        echo $result;
    }
}