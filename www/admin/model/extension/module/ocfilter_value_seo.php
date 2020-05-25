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

    public function UpdateOcFilterSeo($category_id,$data)
    {
        $sql = "DELETE FROM " . DB_PREFIX . "ocfilter_value_seo_description 
                where category_id ='".$category_id."'";
        $this->db->query($sql);

        foreach ($data['ocf_description'] as $optionId=>$option){
            foreach ($option as $valueId=>$value){
                foreach ($value as $languageId=>$seo){
                    $sql = "INSERT INTO " . DB_PREFIX . "ocfilter_value_seo_description 
                    SET category_id = '".$category_id."',
                        option_id = '".$optionId."',
                        value_id = '".$valueId."',
                        language_id = '".$languageId."',
                        description = '".$this->db->escape(trim($seo['description']))."'";

                    $this->db->query($sql);
                }
            }
        }
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

}
