# ocfilter_value_seo

#catalog/controller/product/product.php

 $ocValueSeoData =[];
            $ocValueSeoData['type']='product';
            $ocValueSeoData['category'] =$category_info;
            $ocValueSeoData['product'] = $product_id;
            $ocValueSeoData['description'] =$data['description'];

            $data['description'] = $this->load->controller('extension/module/ocfilter_value_seo_front',$ocValueSeoData); 

#catalog/controller/product/category.php

          $ocValueSeoData =[];
            $ocValueSeoData['type']='category';
            $ocValueSeoData['category'] =$category_info;
            $ocValueSeoData['description'] =$data['description'];

            $data['description'] = $this->load->controller('extension/module/ocfilter_value_seo_front',$ocValueSeoData);
            
#admin/controller/catalog/category.php

if (isset($this->request->get['category_id'])) {
            $categoryId = $this->request->get['category_id'];
        }
		$ocFilterSeoData=[];
		$ocFilterSeoData['languages']=$data['languages'];
		$ocFilterSeoData['categoryId']=$categoryId;
        $data['tab_ocfilter_value_seo']= $this->load->controller('extension/module/ocfilter_value_seo', $ocFilterSeoData);
        
#admin/view/template/catalog/category_form.twig
        <li><a href="#tab-ocf_description" data-toggle="tab">OC Filter Values</a></li>
        {{ tab_ocfilter_value_seo }}                        
        
#storage/modification/admin/model/catalog/category.php


    //add seo data for ocfilter values -for add and edit methods
        $this->addOcFilterSeo($category_id,$data);
        
                
        private function addOcFilterSeo($category_id,$data)
            {
                $this->load->model('extension/module/ocfilter_value_seo');
        
               $this->model_extension_module_ocfilter_value_seo->UpdateOcFilterSeo($category_id,$data);
            }