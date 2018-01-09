define(['jquery'], function($){
    var CustomWidget = function () {
    	let estismail_data = localStorage.getItem('estismailApp');//data from estismail auth
    	let self = this;


    	let lists = []; //get lists from estismail api

        
        self.errorMes = function (mes, callbacks) {
            let date_now = Math.ceil(Date.now()/1000),
                header = self.get_settings().widget_code,
                text = 'error',
                n_data = {
                    header: header, //код виджета
                    text:'<p>'+mes+'</p>',//текст уведомления об ошибке
                    date: date_now //дата
                };

                if(!callbacks){
                    callbacks = {
                        done: function(){console.log('done');}, //успешно добавлено и сохранено AJAX done
                        fail: function(){console.log('fail');}, //AJAX fail
                        always: function(){console.log('always');} //вызывается всегда
                    };
                }

            AMOCRM.notifications.add_error(n_data,callbacks);
        };

    	this.callbacks = {
			render: function(){

                if (typeof(AMOCRM.data.current_card) != 'undefined') {
                    if (AMOCRM.data.current_card.id == 0) {
                        return false;
                    } // не рендерить на contacts/add || leads/add
                }
                self.render_template({
                    caption: {
                        class_name: 'js-estismail-caption',
                        html: ''
                    },
                    body: '',
                    render: '\
                    <div class="estismail-dark-magic-main-form">\
                		<div id="sub-subs-container">\
                		<select id="estismail-lists-select">\
                		</select>\
                		</div>\
                    	<div id="js-sub-lists-container">\
                    		<select id="amocrm-emails-select">\
                    		\
                    		</select>\
                    	</div>\
                    	<div class="estismail-feel-the-power-of-darkside-button">' + 'Subscribe' + '</div>\
                    	</div>\
                		<div class="already-subs"></div>\
                	</div>\
                    <link type="text/css" rel="stylesheet" href="https://proger.estiscloud.pro/integration/amoCRM/css/estismail-widget-storefront.css" >'
                });

                let post_data = JSON.parse(estismail_data);
                console.log(post_data);

                if(estismail_data){
                    self.crm_post(
                        'https://proger.estiscloud.pro/integration/amoCRM/get-lists',
                        {
                            // Передаем POST данные
                            id: post_data.estismail.id,
                            hash_id: post_data.estismail.hash
                        },
                        (res) => {
                            if(res.error){
                                self.errorMes(res.error);
                            }
                            lists = res;
                            console.log(res);

                            let select = $('#estismail-lists-select');
                            for(let i=0; i<= res.lists.length -1; i++){
                                let option = $.parseHTML( '<option value="'+ res.lists[i].id + '">'+ res.lists[i].title +'</option>' );
                                select.append(option);
                            }

                        },
                        'json'
                    );
                }




                let emails = $('input[data-type=email].control--suggest--input-inline');
                let emails_arr = [];

                for (let i=0; i<=emails.length - 1; i++){
                    if(emails[i].value === '') continue;
                    emails_arr.push(emails[i].value);
                }


                let amo_emails_select = $('#amocrm-emails-select');
                for( let i =0; i<= emails_arr.length -1; i++){
                    let option = $.parseHTML( '<option value="'+ emails_arr[i] + '">'+ emails_arr[i] +'</option>' );
                    amo_emails_select.append(option);
                }
                return true;
			},
			init: function(){
			    if(!estismail_data){
                    self.set_status('error');
                }
                console.log(self.system());
                console.log(self.get_settings());
				return true;
			},
			bind_actions: function(){


				$('.estismail-feel-the-power-of-darkside-button').click(function (e) {
				    e.preventDefault();
				    let values = $('.estismail-dark-magic-main-form select');
                    let post_ident_array = JSON.parse(estismail_data);

                    console.log(post_ident_array);
				    self.crm_post(
                        'https://proger.estiscloud.pro/integration/amoCRM/subscribe',
                        {
                            // Передаем POST данные
                            id: post_ident_array.estismail.id,
                            hash_id: post_ident_array.estismail.hash,
                            list_id: values[0].value,
                            email: values[1].value
                        },
                        (res) => {

                            if(res.error){
                                self.errorMes(res.error);
                            }

                            if(res.success){
                                alert(res.success);
                            }
                        },
                        'json'
                    );

                })
			},
			settings: function(){
			},

			onSave: function(){

			    localStorage.removeItem('estismailApp');

                let system = self.system();
                let form_params = self.get_settings();

                let active = 1;
                if(form_params.widget_active === 'N'){
                	active = 0;
				}

                let post_params_obj = {
                	api_key: form_params.api_key,
					user: system.amouser,
                    user_id: system.amouser_id,
					active: !active,
				};
                ;

				self.crm_post(
                    'https://proger.estiscloud.pro/integration/amoCRM/login',
                    post_params_obj,
                    (res)=>{
                        let parsedRes = JSON.parse(res);
                        if(parsedRes.estismail){
                            localStorage.setItem('estismailApp', res)
                        }

                        if(parsedRes.error){
                            self.errorMes(parsedRes.error);
                            self.set_status('error');
                            let checkbox = $('.switcher__on');
                            if(checkbox){
                                checkbox.removeClass('switcher__on');
                                checkbox.addClass('switcher__off');
                            }
                        }
                        if(parsedRes.mes){
                            alert(parsedRes.mes);
                        }

                    }

                )
                return true;

			},
			destroy: function(){

			},
			contacts: {
					//select contacts in list and clicked on widget name
					selected: function(){
					}
				},
			leads: {
					//select leads in list and clicked on widget name
					selected: function(){

					}
				},
			tasks: {
					//select taks in list and clicked on widget name
					selected: function(){
					}
				}
		};
		return this;
    };

return CustomWidget;
});


