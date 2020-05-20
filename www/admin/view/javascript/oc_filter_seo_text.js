$('#add-seo-value').on('click',function(){
    addValueItem();
});

function addValueItem() {
    let valueNumber = ++ocFilterValues.valuecount;
    addValueToList(valueNumber);
    addValueTab(valueNumber);
    listenerUpdate();
}

function addValueToList(valueNumber) {

    let valueItem=document.createElement('li');

    valueItem.innerHTML = `<a href="#tab-ocf_description_value${valueNumber}" 
                                class = "value-${valueNumber}" 
                                data-toggle="tab">${valueNumber} значение
                             </a>`;
    ocFilterValues.valueList.appendChild(valueItem);
}

function addValueTab(valueNumber) {
    let ocfilterOptionsData =getOcfilterOptionsHtml();

    let ocfilterOptions = `<div class="ocfilter-option">
                                <span class="options">название опции</span>
                                <select name="option-name" class="options">
                                    ${ocfilterOptionsData}
                                </select>`;


    let ocfilterValues = `<span class="values">Значение опции</span>
                          <select name="value-name" class="values">                            
                          </select>
                          </div>`;

    let valueTab= document.createElement('div');
        valueTab.classList.add(`tab-pane`);
        valueTab.id = 'tab-ocf_description_value'+valueNumber;

        valueTab.innerHTML = `                
                        ${ocfilterOptions}${ocfilterValues}
                        <ul class="nav nav-tabs" id="languages${valueNumber}">
                                                    <li class="active"><a href="#language1tab-ocf_description_value${valueNumber}" data-toggle="tab" aria-expanded="true"><img src="language/ru_UA/ru_UA.png" title="Russian"> Russian</a></li>
                                                    <li class=""><a href="#language3tab-ocf_description_value${valueNumber}" data-toggle="tab" aria-expanded="true"><img src="language/uk_UA/uk_UA.png" title="Ukrainian"> Ukrainian</a></li>
                                            </ul>
                        
                        <div class="tab-content">
                                                            <div class="tab-pane active" id="language1tab-ocf_description_value${valueNumber}">
                                    <div class="form-group">
                                      <label class="col-sm-2 control-label" for="input-ocf_description-${valueNumber}-1">Описание</label>
                                        <div class="col-sm-10">
                                            <textarea 
                                            
                                            name="ocf_description[option_id][value_id][1][description]" 
                                            placeholder="Описание" 
                                            id="input-ocf_description-${valueNumber}-1" 
                                            data-toggle="summernote" 
                                            data-lang="ru_UA" 
                                            class="form-control value-seo-area summernote"></textarea>
                                        </div>
                                    </div>
                                </div>
                                                            <div class="tab-pane" id="language3tab-ocf_description_value${valueNumber}">
                                    <div class="form-group">
                                      <label class="col-sm-2 control-label" for="input-ocf_description-${valueNumber}-3">Описание</label>
                                        <div class="col-sm-10">
                                             <textarea                                             
                                            name="ocf_description[option_id][value_id][3][description]" 
                                            placeholder="Описание" 
                                            id="input-ocf_description-${valueNumber}-1" 
                                            data-toggle="summernote" 
                                            data-lang="ru_UA" 
                                            class="form-control value-seo-area summernote"></textarea>
                                        </div>
                                    </div>
                                </div>
                                                    
                    </div>
                    
                    `;
    ocFilterValues.valueTabs.appendChild(valueTab);

    $('.summernote').summernote();

}

function getOcFilterOptions(){
    params = window
        .location
        .search
        .replace('?','')
        .split('&')
        .reduce(
            function(p,e){
                var a = e.split('=');
                p[ decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
                return p;
            },
            {}
        );

    fetch('index.php?route=extension/module/ocfilter_value_seo/getocfilteroptions&user_token='+params['user_token'])
        .then((response) => {
            return response.json();
        })
        .then((data) => {
            ocFilterValues.ocfilterOptions = data;
        });
}

//make option fo select.options
function getOcfilterOptionsHtml(){

    let html='';

    for (let option of ocFilterValues.ocfilterOptions){
        html+=`<option value="${option.option_id}">${option.name}</option>`;
    }
    return html;
}


function getOtionValues(optionId){
    for (let option of ocFilterValues.ocfilterOptions){
        if (option.option_id == optionId){
            return option.values;
        }
    }
    return false;
}

//make option fo select.values
function getHtmlForValuesSelect(values) {
    let html='';
    for (let valuedata of values){

        html+=`<option value="${valuedata.value_id}">${valuedata.name}</option>`;
    }
    return html;
}

function updateOptionValues(event) {
    let option = this.options[this.selectedIndex].value;

    let values = getOtionValues(option);
    let valuesHtml='';
    if (values){
        valuesHtml = getHtmlForValuesSelect(values);
    }
    console.log($(this).siblings('select.values').first());
    $(this).siblings('select.values')[0].innerHTML = valuesHtml;
}

function updateTabName(){
    let optionsdata = this.closest('.ocfilter-option')
                        .querySelector('select.options');
    let optionId = optionsdata.options[optionsdata.selectedIndex].value;
    let valueId = this.options[this.selectedIndex].value;
    let optionText = optionsdata.options[optionsdata.selectedIndex].innerHTML;
    let valueText = this.options[this.selectedIndex].innerHTML;
    let currentTabName = document.querySelector('#value-list li.active a')
    currentTabName.innerHTML = optionText + ' </br> ' + valueText;
    let currentTab =  $(this.closest('.tab-pane')).find('.value-seo-area');
    currentTab[0].name = `ocf_description[${optionId}][${valueId}][1][description]`;
    currentTab[1].name = `ocf_description[${optionId}][${valueId}][3][description]`;
}

const ocFilterValues = {
  values:{},
  valuecount:1,
  valueList:document.getElementById('value-list'),
  valueTabs:document.getElementById('value-tabs'),
  ocfilterOptions:{},
};

//events
document.addEventListener('DOMContentLoaded',getOcFilterOptions);
listenerUpdate();
function listenerUpdate(){
    $('.options').on(        'change',updateOptionValues);
    $('.values').on('change',updateTabName);
}

