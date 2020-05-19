<?php
class ControllerExtensionModuleOcfilterValueSeo extends Controller {

	public function index($languages)
    {

        $data['languages'] = $languages;

        $this->load->model('extension/module/ocfilter_value_seo');

        $data['ocfilterOptions'] =$this->model_extension_module_ocfilter_value_seo->getOcfilterOptions();
//dd($data['ocfilterOptions']);
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