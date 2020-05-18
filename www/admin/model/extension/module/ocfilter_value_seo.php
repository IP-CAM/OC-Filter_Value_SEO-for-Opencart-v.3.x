<?php
class ModelExtensionModuleOcfilterValueSeo extends Model {

        public function getOcfilterOptions($categoryId=null)
        {

            $this->load->model('extension/ocfilter');

            if ($categoryId){
               $options = $this->model_extension_ocfilter->getOptionsByCategoryId($categoryId);
            }else{
                $options = $this->model_extension_ocfilter->getOptions();
            }

            return $options;
        }

}
