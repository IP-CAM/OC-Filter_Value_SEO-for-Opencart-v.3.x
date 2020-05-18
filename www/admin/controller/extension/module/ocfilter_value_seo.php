<?php
class ControllerExtensionModuleOcfilterValueSeo extends Controller {

	public function index($languages)
    {

        $data['languages'] = $languages;

        $this->load->model('extension/module/ocfilter_value_seo');


        return $this->load->view('extension/module/ocfilter_value_seo', $data);
    }

    public function getOcfilterOptions()
    {
        $ocfilterSeo =$this->model_extension_module_ocfilter_value_seo->getOcfilterOptions();

        $result = json_encode($ocfilterSeo);

        return $result;
    }
}