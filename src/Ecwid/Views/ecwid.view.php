<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<meta charset="utf-8">
	<link rel="stylesheet" href="https://djqizrxa6f10j.cloudfront.net/ecwid-sdk/css/1.2.5/ecwid-app-ui.css"/>
	<link rel="stylesheet" href="/integration/ecwid/css/estismail-ecwid.css">
    <script src="https://djqizrxa6f10j.cloudfront.net/ecwid-sdk/js/1.2.1/ecwid-app.js"></script>
	<title>Estismail App</title>
</head>
<body>
<div class="app-container">
    <ul class="nav nav-links main-menu">
        <li id='dashboard' class="active estis-links connect"><a href="javascript:;" onclick="menu('connect');"><span>Connection</span></a></li>
        <li id='settings' class="estis-links save-estis"><a href="javascript:;" onclick="menu('save-estis')"><span>Save Setting</span></a></li>
    </ul>
    <div id="estis-app-contet">
        <div class="estimail-main-app estis-section" id="connect">
            <h3>Api connect</h3>
            <form action="/integration/ecwid/api-connect/" method="post" class="api-connect-form">
                <input type="hidden" name="hash">
                <input type="hidden" name="shop_id">
                <div class="alert alert-warning">
                    <div class="title">Not yet connected :|</div>
                    <p>Connect with estismail usin your api-key in estismail account</p>
                </div>

                <div class="input-append">
                    <label for="estis-api-key" class="estis-label">Enter your estis api-key:</label>
                    <input type="text"  class="form-control" id="estis-api-key" name="api_key">
                </div>

                <div class="input-append estis-btn-container">
                    <button class="btn btn-success btn-small estis-but">Connect</button>
                </div>
            </form>
        </div>
        <div class="estis-save-settings estis-section" id="save-estis">
            <h3>Save settings</h3>
            <form action="/integration/ecwid/save-estis-settings/" method="post">
                <div class="input-append">
                    <label for="estis-list" class="estis-label">Please, select your list:</label>
                    <select class="form-control" id="estis-list" name="estis_list" required>
                    </select>
                </div>
                <div class="estis-checkbox-container">
                    <div class="input-append">
                        <span class="estis-checkbox" >Double opt-in:</span>
                        <label class="checkbox tiny">
                            <input type="checkbox" name="double_opt_in" class="double_opt_in_checkbox">
                            <div data-on="enabled" data-off="disabled">
                                <div></div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="estis-checkbox-container">
                    <div class="input-append">
                        <span class="estis-checkbox" >Enable app on store-front:</span>
                        <label class="checkbox tiny">
                            <input type="checkbox"  name="store_front_enable" class="storefront-checkbox" checked>
                            <div data-on="enabled" data-off="disabled">
                                <div></div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="input-append estis-btn-container">
                    <button class="btn btn-success btn-small estis-but">Save settings</button>
                </div>
                <span class="email_statistic"></span>
            </form>
        </div>
    </div>
</div>
<div class="alert alert-error fatal_error">
    <b></b>
</div>

<script>
    if (document.querySelectorAll('.estis-section').length > 0){

        for (i=0; i<document.querySelectorAll('.estis-section').length; i++){
            document.querySelectorAll('.estis-section')[i].style.display = 'none';
        }
        document.querySelectorAll('.estis-section')[0].style.display = 'block';
    }
</script>
<script src="../js/functions.js"></script>
</body>

</html>