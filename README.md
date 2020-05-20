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