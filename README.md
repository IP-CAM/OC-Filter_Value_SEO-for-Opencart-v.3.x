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
        
        {{ tab_ocfilter_value_seo }}                        