<?php

class ModelExtensionModuleCognate extends Model
{
    private $cognateProducts=[];
    private $currentProductId = '';
    private $currentCategory = '';

    public function getCognateProducts($productId)
    {
        $this->currentProductId = $productId;
        $data = $this->getProductModelCategory($productId);
        $data['products'] = $this->getProductsByModel($data['model']);

        $this->cognateProducts = $data['products'];
        $this->currentCategory = $data['category_id'];

        $data['oc_filer_options'] = $this->getOcFilterOptions($data['category_id']);
        $currentProductOptions = $this->getProductOcFilterOptions($productId);

        $data['proopt']=$currentProductOptions;

        $productsforOptions = $this->getProductsForOptions($data['oc_filer_options'],$currentProductOptions);
        $data['variants'] =  $productsforOptions;

        return $data;

    }

    /**
     * @param $productId
     * @return  array [model,category_id]
     *
     */
    private function getProductModelCategory($productId)
    {
        $sql = "SELECT model, category_id FROM " . DB_PREFIX . "product p
                left join " . DB_PREFIX . "product_to_category pc 
                on p.product_id = pc.product_id
                WHERE p.product_id = '$productId'";
        $query = $this->db->query($sql)->row;
        return $query;
    }

    /**
     * @param $model
     * @return array of all products for current Model
     *
     */
    private function getProductsByModel($model)
    {
        $sql = "SELECT product_id, price FROM " . DB_PREFIX . "product WHERE model = '".$model."'";
        $products = $this->db->query($sql)->rows;

        $data=[];
        foreach ($products as $product){
            $options = $this->getProductOcFilterOptions($product['product_id']);
            $sortedOptions =$this->sortOptions($options);
            $data[$product['product_id']] =[
                'product_id' =>$product['product_id'],
                'options'=> $sortedOptions,
                'price'=> $product['price'],
            ];
        }

        $data = $this->sortProductsByPrice($data);
        return $data;
    }


    /**
     * @param $categoryId
     * @return array of OC filter options for current category
     */
    private function getOcFilterOptions($categoryId)
    {
        $this->load->model('extension/module/ocfilter');

        $query = $this->model_extension_module_ocfilter->getOCFilterOptionsByCategoryId($categoryId);

        $options = [];
        foreach ($query as $option ){
            $values = [];
            foreach ($option['values'] as $value){
                $isAttribute = ($value['is_attribute'] == '1')? true: false;
                $values[]=[
                    'value_id' => $value['value_id'],
                    'name' => $value['name'],
                    'option_id' => $value['option_id'],
                    'is_attribute'=>$isAttribute
                ];
            }

            $options[]=[
                'option_id' => $option['option_id'],
                'name' => $option['name'].', '.  $option['postfix'],
                'values' => $values,
            ];
        }

        return $options;

    }


    /**
     * @param $productId
     * @return mixed OC filter options for product
     *
     */
    private function getProductOcFilterOptions($productId,$valueId = false)
    {
        $sql = "SELECT option_id, value_id FROM " . DB_PREFIX . "ocfilter_option_value_to_product
                where product_id ='".$productId."' ";
                $options =$this->db->query($sql)->rows;
        return $options;
    }

    private function getProductOcFilterRangeOptions($productId)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "ocfilter_option_value_to_product
                where product_id ='".$productId."' ";

            $sql.= " AND value_id='0'";

        $options =$this->db->query($sql)->rows;
        return $options;
    }

    private function getProductOcFilterOptionsForAtributes($productId)
    {

        $languageId = (int)$this->config->get('config_language_id');
        $sql = "SELECT po.option_id, od.name as option_name , po.value_id , 
                vd.name as value_name, vd.is_attribute as is_attribute
                FROM " . DB_PREFIX . "ocfilter_option_value_to_product po
                left join " . DB_PREFIX . "ocfilter_option_description od
                on po.option_id = od.option_id                
                left join " . DB_PREFIX . "ocfilter_option_value_description vd 
                on po.value_id = vd.value_id                
                where product_id ='".$productId."'
                and od.language_id ='".$languageId."'
                and vd.language_id ='".$languageId."'";

        $options =$this->db->query($sql)->rows;


        return $options;
    }

    /**
     * @param $oc_filer_options
     * @param $currentOptions
     * @return array of OC filter options with Product for every option value if exist
     *  get all cognates product by Model
     */
    private function getProductsForOptions($oc_filer_options,$currentOptions)

    {
        $products=[];

        foreach ($oc_filer_options as $option){
            $variants =[];
            $countCurrentSelectedValues=0;
            $CurrentOptionName = $option['name'];
            $optionId = $option['option_id'];

            $products[$optionId]=[];
            $data = [];

            $OcFilterCategoryOptionsWOCurrent = $this->filterCurrentOptions($currentOptions,$option['option_id']);

            foreach ($option['values'] as $value){

                $valueId = $value['value_id'];
                $selected = false;
                $lookingOption = [
                    'option_id'=>$optionId,
                    'value_id'=>$valueId,
                ];

                $productId = $this->getProductIdByOptions($value,$OcFilterCategoryOptionsWOCurrent);

                $productUrl = $productId ? $this->url->link('product/product', 'product_id=' . $productId):'';
                if ($productId) $variants[]=$productId;

                $selected = ($productId == $this->currentProductId)? 'selected':'';

                if (in_array($lookingOption, $currentOptions)) {
                    $selected = 'selected';
                    $countCurrentSelectedValues++;
                }


                $data[] =[
                    'value_id' => $valueId,
                    'value_name' => $value['name'],
                    'product_id' => $productId,
                    'url'=> $productUrl,
                    'selected' => $selected
                ];
            }

            // show OC product values only if exist variants of products with other set of values
            $countValues = count($variants);
            $showOption = $countCurrentSelectedValues !== $countValues && !empty($variants);

            $products[$optionId]=[
                'option_id'=> $optionId,
                'option_name'=> $CurrentOptionName,
                'values'=> $data,
                'show_option' => $showOption
            ];

        }

        return $products;
    }


    /**
     * @param $currentOptions
     * @param $optionId
     * @return array
     *
     * get array of current product options without one option =  '$optionId'
     */
    private function  filterCurrentOptions($currentOptions,$optionId)
    {
        $options = array_filter ($currentOptions, function($option) use ($optionId){
            return $option['option_id'] != $optionId;
        });
        return $options;
    }


    /**
     * @param $value
     * @param $otherOptions
     * @return mixed|null
     *  get product Id if exist for every  options or
     */
    private function getProductIdByOptions($value,$otherOptions)
    {
        $addCurrentOption = [
            'option_id' => $value['option_id'],
            'value_id' => $value['value_id'],
        ];

        $otherOptions[] =$addCurrentOption;
        $currentoptions = $this->sortOptions($otherOptions);
        $productId = null;

        foreach ($this->cognateProducts as $product){
            $isoptionsEqual = $this->compareOptions($product['options'],$currentoptions);

            if ($isoptionsEqual) {
                $productId =  $product['product_id'];
                return $productId;
            }
            // if not found product for all options mix get product with  option value = $value
            $productId = $this->getMinimalProductPrice($addCurrentOption);
        }

        return $productId;
    }


    /**
     * @param $value
     * @return mixed
     */
    private function getMinimalProductPrice($value)
    {

        for ($i=1; $i<count($this->cognateProducts);$i++){
            $product = $this->cognateProducts[$i];

            $isOptionsEqual = $this->compareUniqueOptions($product['options'],$value,$product['product_id']);

            if ($isOptionsEqual){
                return $product['product_id'];
            }
        }

    }

    /**
     * @param $options
     * @return mixed
     * sort array of options ASC
     */
    private function sortOptions($options)
    {
        usort($options, "cmpOptionId");
        return $options;
    }

    /**
     * @param $products
     * @return mixed
     * sort array of products for current model  ASC
     */
    private function sortProductsByPrice($products)
    {
        usort($products, "cmpPrice");
        return $products;
    }

    /**
     * @param $options
     * @return bool
     */
    private function compareOptions($productOptions, $options)
    {
        foreach ($options as $option){
            if (!in_array($option,$productOptions)) return false;
        }
        return true;
    }

    /**
     * @param $productOptions
     * @param $currentOption
     * @param $productId
     * @return bool
     */
    private function compareUniqueOptions($productOptions, $currentOption, $productId){

        foreach ($productOptions as $option){
            if ($currentOption ==  $option) {
                return true;
            }
        }
        return false;
    }

    public function getParams($product)
    {

        $productId = $product['product_id'];

        $categoryId = $this->model_catalog_product->getCategories($productId)[0]['category_id'];

        $categoryPath= trim($this->url->link('product/category', 'path=' . $this->request->get['path']),'/');

        $params = $this->getProductOcFilterOptionsForAtributes($productId);

        foreach ($params as $key => $option) {
            $params[$key]['path'] = $categoryPath;
            $option_id = $option['option_id'];
            if ($option_id == 'p') {
                $path .= '/price';
            } else if ($option_id == 's') {
                $path .= '/sklad';
            } else if ($option_id != 'm') {
                $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "ocfilter_option WHERE option_id = '" . (int)$option_id . "'");

                if ($query->num_rows && $query->row['keyword']) {
                    $params[$key]['path'] .= '/' . $query->row['keyword'];
                } else {
                    $params[$key]['path'] .= '/' . $option_id;
                }
            }


            $query = false;
            $value_id = $option['value_id'];
            if ($option_id == 'm') {
                $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "url_alias WHERE `query` = 'manufacturer_id=" . (int)$value_id . "'");
            } else  {
                $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "ocfilter_option_value WHERE value_id = '" . $this->db->escape((string)$value_id) . "'");
            }

            if ($query && $query->num_rows && $query->row['keyword']) {
                $params[$key]['path'] .= '/' . $query->row['keyword'];
            } else {
                $params[$key]['path'] .= '/' . $value_id;
            }

        }
        $options = [];
        foreach ($params as $param){
            $options[$param['option_id']]['name']=$param ['option_name'];
            $options[$param['option_id']]['values'][] = $param;
        }

        $rangeParams = $this->getRangeOption($productId);
        $options =array_merge($options,$rangeParams);


        return $options;
    }
    public function getRangeOption($productId){
        $rangeOptions=[];
        $options = $this->getProductOcFilterRangeOptions($productId);

        foreach ($options as $option){
            if ($option['slide_value_min'] != 0 || $option['slide_value_max'] != 0){
                $optionId = $option['option_id'];
                $optionData= $this->getOcFilterOption($optionId);
                $optionName = $optionData['name'];
                if ($option['slide_value_min'] == $option['slide_value_max'] ){
                    $valueName = round($option['slide_value_min'],2).$optionData['postfix'];
                }else{
                    $valueName = round($option['slide_value_min'],2).'-'.round($option['slide_value_min'],2).$optionData['postfix'];
                }
                $rangeOptions [$optionId] =[
                    'name' =>$optionName,
                    'values'=>[
                        '0'=>[
                            'option_id'=>$optionId,
                            'option_name'=>$optionName,
                            'value_id'=>0,
                            'value_name'=>$valueName,
                            'is_attribute' => 1,
                            'path'=>''
                        ]
                    ]
                ];
            }
        }

        return $rangeOptions;
    }


    private function getOcFilterOption($optionId){

        return $this->db->query("
                SELECT * 
                FROM " . DB_PREFIX . "ocfilter_option_description 
                where option_id='".(int)$optionId."'
                and language_id ='".(int)$this->config->get('config_language_id')."'
            ")->row;
    }
}



//helper functions
/**
 * @param $a
 * @param $b
 * @return int|lt
 * callback for array  user sort
 * sort option by ID ASC
 */
function cmpOptionId($a, $b)
{
    return strcmp($a["option_id"], $b["option_id"]);
}
/**
 * @param $a
 * @param $b
 * @return int|lt
 * callback for array  user sort
 * sort products by PRICE ASC
 */
function cmpPrice($a, $b)
{
    return strcmp($a["price"], $b["price"]);
}
