<?php
class ModelExtensionModuleOcfilterValueSeoFront extends Model {

        public function getOcfilterOptions($categoryId)
        {

            $this->load->model('extension/module/ocfilter');


               $options = $this->model_extension_module_ocfilter->getOCFilterOptionsByCategoryId($categoryId);


            return $options;
        }



    public function getOcValuesSeo($categoryId=null)
    {
        if (!$categoryId) return null;

        $sql = "SELECT distinct vs.option_id,vs.value_id, vs.language_id, 
                                vs.description, od.name as option_name, ov.name as value_name 
                FROM " . DB_PREFIX . "ocfilter_value_seo_description vs
                LEFT JOIN " . DB_PREFIX . "ocfilter_option_description od 
                                            on vs.option_id = od.option_id 
                                            and vs.language_id = od.language_id
                LEFT JOIN " . DB_PREFIX . "ocfilter_option_value_description ov 
                                            on ov.value_id = vs.value_id 
                                            and ov.language_id = od.language_id
                WHERE category_id='".$categoryId."'
                ORDER BY language_id
                ";

        $optionsData =  $this->db->query($sql)->rows;
        $options = [];
        $values=[];
        foreach ($optionsData as $option){

            $options [$option['value_id']][$option['language_id']]= $option;

        }

        return $options;

    }

    public function getOcValuesDescriptions($ocFilterOptions,$categoryId)
    {
        $description=[];
        $SeoOptionsForCategory = $this->getOcValuesSeo($categoryId);
        $selectedValues=[];

        foreach ($ocFilterOptions as $optionId=>$option){
                foreach ($option['values'] as $value){
                    $optionIdLength = strlen($optionId);
                    $valueId = substr($value['id'],$optionIdLength);
                    $selectedValues[]= $valueId;
                }
        }


        $languageId=(int)$this->config->get('config_language_id');

        foreach ($SeoOptionsForCategory as $valueId=>$seoValue){
            if (in_array($valueId, $selectedValues)){
                $description[]=htmlspecialchars_decode($seoValue[$languageId]['description']);
            }
        }

        return $description;
    }

    public function getProductOcOptions($productId)
    {
        $sql = "SELECT option_id, value_id FROM " . DB_PREFIX . "ocfilter_option_value_to_product
                where product_id ='".$productId."' ";
        $options =$this->db->query($sql)->rows;
        return $options;
    }

    public function getOcProductDescriptions($ocfilterOptions, $categoryId)
    {
        $description=[];

        $SeoOptionsForCategory = $this->getOcValuesSeo($categoryId);

        $selectedValues=[];

        foreach ($ocfilterOptions as $option) {
            $selectedValues[] = $option['value_id'];
        }


        $languageId=(int)$this->config->get('config_language_id');

        foreach ($SeoOptionsForCategory as $valueId=>$seoValue){
            if (in_array($valueId, $selectedValues)){
                if(isset($seoValue[$languageId])) {
                    $description[] = htmlspecialchars_decode($seoValue[$languageId]['description']);
                }
            }
        }

        return $description;

    }

}
