EcwidApp.init({
    app_id: "estis-test", // use your application namespace
    autoloadedflag: true,
    autoheight: true
});

let storeData = EcwidApp.getPayload();
let storeId = storeData.store_id;
let accessToken = storeData.access_token;

//data from getMainPageInfo
let responseBody = {}; //store data from db Promices here

const shopData = {
    shopId: storeId,
    token: accessToken
};


function updateInfo() {

    httpPost('../main-app-page', shopData)
        .then(
            res => {
                let body = JSON.parse(res);
                if(body.error){
                    fatalError(body.error);
                }
                responseBody = body;
                responseBodyController();
            },

            err => {
                fatalError(err);
            }

        );
}

window.onload = function () {
    updateInfo();
    
    let apiConnectForm = document.querySelector('.api-connect-form');
    apiConnectForm.onsubmit = (e)=>{
        e.preventDefault();
        const apiConnectData = {
            id: responseBody.db.id,
            api_key: apiConnectForm.elements.api_key.value
        }

        httpPost('../api-connect', apiConnectData)
            .then(
                res => {
                    let body = JSON.parse(res);
                    if(body.error){
                        fatalError(body.error);
                    }

                    if(Number(body.data) === 0){
                       alert('Invalid api key value');
                    }
                    updateInfo();
                },
                err => {
                    fatalError(err);
                }
            );
    };


    let saveEstisFormSubmit = document.querySelector('#save-estis form');
    saveEstisFormSubmit.onsubmit = function (e) {
        e.preventDefault();

        const settingsEstis = {
            id: responseBody.db.id,
            estis_list: saveEstisFormSubmit.elements[0].value,
            double_opt_in: saveEstisFormSubmit.elements[1].checked,
            store_front_enable: saveEstisFormSubmit.elements[2].checked
        };

        httpPost('../save-estis-settings', settingsEstis)
            .then(
                res => {
                    console.log(res);
                    let body = JSON.parse(res);
                    if(body.error){
                        fatalError(body.error);
                    }
                    return body;
                },
                err => {
                    fatalError(err);
                }
            )
            .then( body => {

                if(body !== 1){
                   alert('Invalid list');
                }
                updateInfo();
            })

    }

};

//func for ajaxEx or ajaxLog errors
let errAlert = document.querySelector('.fatal_error');
let main_div = document.querySelector('.app-container');
function fatalError(mes) {
    main_div.remove();
    errAlert.style.display = 'block';
    errAlert.children[0].innerText = mes;
    throw new Error(mes);
}

function httpPost(url, body = []) {
    return new Promise(function (resolve, reject) {
        let xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        if (body !== []) {
            body = 'post_data=' + encodeURIComponent(JSON.stringify(body))
        }

        xhr.onload = function () {
            if (this.status === 200) {
                resolve(this.response);
            } else {
                let error = new Error(this.statusText);
                error.code = this.status;
                reject(error);
            }
        };

        xhr.onerror = function () {
            reject(new Error("Network Error"));
        };

        xhr.send(body);
    });
    
}


let save_settings_tab = document.querySelector('#settings');
let api_key_input = document.querySelector('#estis-api-key');
let html_alert = document.querySelector('.api-connect-form .alert');
let email_statistic = document.querySelector('.email_statistic');
function responseBodyController() {

    //setting api_key
    if(responseBody.db.api_key){
        api_key_input.value = responseBody.db.api_key;
    }

    //setting bootstrap alert params
    html_alert.children[0].innerText = responseBody.alert_data[responseBody.alert_status].head;
    html_alert.children[1].innerText = responseBody.alert_data[responseBody.alert_status].mes;
    html_alert.setAttribute('class', 'alert ' + responseBody.alert_data[responseBody.alert_status].class);

    //lists section
    save_settings_tab.style.display = 'none'; //for callbacks
    if(responseBody.estis){
        save_settings_tab.style.display = 'block';
        renderLists();
        setCheckboxValue();
    }

    //check, if is set storefront
    if(responseBody.db.storefront === "" || Number(responseBody.db.storefront) === 1){
        storeFrontInitialize(responseBody.db.storefront);
    };

    //show email statistic
    if(responseBody.db.last_subscriber_email){
        let statistic = JSON.parse(responseBody.db.last_subscriber_email);
        let mes = 'Subscribtion error with email ' + statistic.email;
        if(Number(statistic.success) === 1){
            mes = 'Succefully subscripted ' + statistic.email;
        }
        email_statistic.innerText = mes;
    };
}

let select = document.querySelector('#estis-list');
function renderLists() {
    select.options.length = 0;//drop all select elements
    for(let i=0; i<=(responseBody.estis.lists.length-1); i++){
        let option = document.createElement('option');
        option.innerText = responseBody.estis.lists[i].title;
        option.value = responseBody.estis.lists[i].id;
        select.appendChild(option);
    }
    if(responseBody.db.list_id){
        select.value = responseBody.db.list_id;
    }
}

let double_opt_in_checkbox = document.querySelector('.double_opt_in_checkbox');
let storefront_checkbox = document.querySelector('.storefront-checkbox');
function setCheckboxValue() {
    double_opt_in_checkbox.checked = Boolean(responseBody.db.double_opt_in);
    storefront_checkbox.checked = Boolean(responseBody.db.storefront);
}





function storeFrontInitialize(storefront) {
    let data = {
        storefront: storefront
    };
    data = JSON.stringify(data);
    EcwidApp.setAppPublicConfig(data, () => {
        console.log('Wohhoooo, estismail app public config saved :)');
    });
}


function menu(item) {
    const links = document.querySelectorAll('.estis-links');
    links.forEach((item) => {
        item.classList.remove('active');
    });
    const link = document.querySelector('.' + item);
    link.classList.add('active');

    const elems = document.querySelectorAll('.estis-section');
    elems.forEach((item) => {
        item.style.display = 'none';
    });

    const elem = document.querySelector('#' + item);
    elem.style.display = 'block';
}



















