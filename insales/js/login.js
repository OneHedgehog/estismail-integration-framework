$(document).ready(function() {
    const modal = $('#api-key-value-control');
    const modalMes = $('#api-key-value-control .modal-body');
    //check api_key range
    apiKeyAlert(modal, modalMes);
    //check, if the list. we have is valid
    estisListValidation(modal, modalMes);

    saveSettingAlert();

    let i = 0;
    $(window).on('beforeunload', (e)=>{
        let select = $('#lists')[0].value;

        if( i === 1 || select != 0){
            return;
        }
        i++;
        return true;
    })
});

//func for have api_key in range 0..40
function  apiKeyAlert(modal, modalMes) {

    const form = $('.estis_form');
    form.submit(function (e) {
        const api_key_input_value  = $('input[name=api_key]')[0].value;
        if(api_key_input_value.length > 40 || api_key_input_value === ''){
            //bootstrap modals
            const options = {
                focus: true
            };
            //mes we use in all modals
            modalMes[0].innerText = 'api key should be less then 40 symbols';
            modal.modal(options);
            e.preventDefault();
            return false;
        }
    });
}



//check, if user don't change list id in HTML
function estisListValidation(modal, modalMes){
    const form = $('.estis_form_lists');
    const selectOptions = $('#lists')[0].children;
    let  listsValues = [];

    for(let i =0; i <= (selectOptions.length - 1); i++){
        listsValues.push(selectOptions[i].value);
    }


    form.submit(function (e) {
        //we need select one more time during form submitting
        curListValue = $('#lists')[0].value;

        let valid = false;
        //compare with another lists
        for(let i=0; i<=(listsValues.length-1); i++){
            console.log(listsValues[i]);
            if(listsValues[i] === curListValue){
                valid = true;
            }

        }
        if(!valid){
            //bootstrap modals
            const options = {
                focus: true
            };
            //mes we use in all modals
            modalMes[0].innerText = 'Invalid list id';
            modal.modal(options);
            e.preventDefault();
            return false;
        }
    });
}

function saveSettingAlert() {
    //alert('Please, save youre changes');
}

